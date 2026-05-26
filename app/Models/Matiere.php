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
        return $this->hasMany(Matiere::class, 'parent_matiere_id')
            ->orderBy('ordre')
            ->orderBy('code');
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
}
