<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Niveau extends Model
{
    protected $fillable = [
        'etablissement_id',
        'code',
        'libelle',
        'cycle',
        'ordre',
        'frais_scolarite_defaut',
        'frais_inscription_defaut',
        'frais_reinscription_defaut',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'frais_scolarite_defaut' => 'integer',
        'frais_inscription_defaut' => 'integer',
        'frais_reinscription_defaut' => 'integer',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(Matiere::class, 'matiere_niveau')
            ->withPivot('coefficient', 'volume_horaire_hebdo', 'obligatoire');
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}