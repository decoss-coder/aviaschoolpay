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

        $classe = $this->classeCouranteDepuisRoute();

        if ($classe && ! $this->classeUtiliseSousDisciplines($classe)) {
            $relation->whereRaw('1 = 0');
            return $relation;
        }

        if ($classe && $this->classeUtiliseSousDisciplines($classe) && $this->estFrancaisRacine()) {
            $this->creerSousDisciplinesFrancaisPremierCycleSiAbsentes();
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

    private function classeCouranteDepuisRoute(): ?Classe
    {
        if (! app()->bound('request') || ! request()->route()) {
            return null;
        }

        $classeParam = request()->route('classe');

        if (! $classeParam) {
            return null;
        }

        if ($classeParam instanceof Classe) {
            return $classeParam;
        }

        if (is_numeric($classeParam)) {
            return Classe::with('niveau')->find((int) $classeParam);
        }

        return null;
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

        return preg_match('/(^|\s)(6|5|4|3)\s*(e|eme|eme)?(\s|$)/', $codeOuLibelle) === 1;
    }

    private function estFrancaisRacine(): bool
    {
        if ($this->parent_matiere_id !== null) {
            return false;
        }

        $text = $this->normaliserTexte(trim((string) $this->code . ' ' . (string) $this->nom));

        return str_contains($text, 'francais')
            || in_array($text, ['fr', 'fra', 'fran', 'franc'], true);
    }

    private function creerSousDisciplinesFrancaisPremierCycleSiAbsentes(): void
    {
        if (! $this->exists || ! $this->etablissement_id || $this->parent_matiere_id !== null) {
            return;
        }

        $presets = [
            ['code' => 'CF', 'nom' => 'Composition française', 'poids' => 3, 'ordre' => 1],
            ['code' => 'OG', 'nom' => 'Orthographe et grammaire', 'poids' => 1, 'ordre' => 2],
            ['code' => 'EO', 'nom' => 'Expression orale', 'poids' => 1, 'ordre' => 3],
        ];

        foreach ($presets as $preset) {
            $existsForParent = self::query()
                ->where('etablissement_id', $this->etablissement_id)
                ->where('parent_matiere_id', $this->id)
                ->where(function ($query) use ($preset) {
                    $query->where('code', $preset['code'])
                        ->orWhere('nom', $preset['nom']);
                })
                ->exists();

            if ($existsForParent) {
                continue;
            }

            self::create([
                'etablissement_id' => $this->etablissement_id,
                'parent_matiere_id' => $this->id,
                'code' => $this->codeSousDisciplineDisponible($preset['code']),
                'nom' => $preset['nom'],
                'coefficient_defaut' => $this->coefficient_defaut ?? 1,
                'poids_dans_parent' => $preset['poids'],
                'ordre' => $preset['ordre'],
                'groupe' => 'francais_premier_cycle',
                'active' => true,
            ]);
        }
    }

    private function codeSousDisciplineDisponible(string $base): string
    {
        $candidate = $base;
        $counter = 1;

        while (self::query()
            ->where('etablissement_id', $this->etablissement_id)
            ->where('code', $candidate)
            ->where('parent_matiere_id', '!=', $this->id)
            ->exists()) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }

        return $candidate;
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
