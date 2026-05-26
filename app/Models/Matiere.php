<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Matiere extends Model
{
    protected $fillable = [
        'etablissement_id',
        'parent_matiere_id',
        'nom',
        'code',
        'coefficient_defaut',
        'poids_dans_parent',
        'ordre',
        'groupe',
        'active',
    ];

    protected $casts = [
        'coefficient_defaut' => 'decimal:2',
        'poids_dans_parent'  => 'decimal:2',
        'active' => 'boolean',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'parent_matiere_id');
    }

    public function sousDisciplines(): HasMany
    {
        $relation = $this->hasMany(Matiere::class, 'parent_matiere_id')
            ->orderBy('ordre')
            ->orderBy('code');

        if ($this->doitMasquerSousDisciplinesPourClasseCourante()) {
            $relation->whereRaw('1 = 0');
        }

        return $relation;
    }

    public function estSousDiscipline(): bool
    {
        return $this->parent_matiere_id !== null;
    }

    public function aSousDisciplines(): bool
    {
        return $this->sousDisciplines()->exists();
    }

    public function niveaux(): BelongsToMany
    {
        return $this->belongsToMany(Niveau::class, 'matiere_niveau')
            ->withPivot('coefficient', 'volume_horaire_hebdo', 'obligatoire');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function emploiDuTemps(): HasMany
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function moyennesMatieres(): HasMany
    {
        return $this->hasMany(MoyenneMatiere::class);
    }

    public function notes(): HasManyThrough
    {
        return $this->hasManyThrough(Note::class, Evaluation::class, 'matiere_id', 'evaluation_id');
    }

    private function doitMasquerSousDisciplinesPourClasseCourante(): bool
    {
        if (! app()->bound('request') || ! request()->route()) {
            return false;
        }

        $classeParam = request()->route('classe');

        if (! $classeParam) {
            return false;
        }

        $classe = null;

        if ($classeParam instanceof Classe) {
            $classe = $classeParam;
        } elseif (is_numeric($classeParam)) {
            $classe = Classe::with('niveau')->find((int) $classeParam);
        }

        if (! $classe) {
            return false;
        }

        return ! $this->classeUtiliseSousDisciplines($classe);
    }

    private function classeUtiliseSousDisciplines(Classe $classe): bool
    {
        $classe->loadMissing('niveau');
        $niveau = $classe->niveau;

        if (! $niveau) {
            return false;
        }

        $cycle = $this->normaliserTexte((string) ($niveau->cycle ?? ''));
        if (in_array($cycle, ['premier_cycle', 'premier cycle', 'college'], true)) {
            return true;
        }

        $codeOuLibelle = $this->normaliserTexte(trim((string) ($niveau->code ?? '') . ' ' . (string) ($niveau->libelle ?? '')));

        return preg_match('/(^|\s)(6|5|4|3)\s*(e|eme)?(\s|$)/', $codeOuLibelle) === 1;
    }

    private function normaliserTexte(string $value): string
    {
        $value = strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }
}
