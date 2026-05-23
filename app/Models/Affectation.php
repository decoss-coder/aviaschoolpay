<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    protected $fillable = [
        'enseignant_id',
        'classe_id',
        'matiere_id',
        'annee_scolaire_id',
        'volume_horaire_hebdo',
        'est_professeur_principal',
        'active',
    ];

    protected $casts = [
        'volume_horaire_hebdo' => 'decimal:2',
        'est_professeur_principal' => 'boolean',
        'active' => 'boolean',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeProfesseursPrincipaux(Builder $query): Builder
    {
        return $query->where('est_professeur_principal', true);
    }

    public function scopeDeAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }
}