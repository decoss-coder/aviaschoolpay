<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaieEnseignant extends Model
{
    protected $table = 'paie_enseignants';

    protected $fillable = [
        'enseignant_id',
        'etablissement_id',
        'mois',
        'salaire_base',
        'primes',
        'retenues',
        'retenue_absence',
        'net_a_payer',
        'jours_presents',
        'jours_absents',
        'jours_retard',
        'statut_paiement',
        'date_paiement',
        'mode_paiement',
        'reference_paiement',
    ];

    protected $casts = [
        'salaire_base' => 'decimal:0',
        'primes' => 'decimal:0',
        'retenues' => 'decimal:0',
        'retenue_absence' => 'decimal:0',
        'net_a_payer' => 'decimal:0',
        'date_paiement' => 'date',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function getMontantTotalRetenuesAttribute(): float
    {
        return (float) $this->retenues + (float) $this->retenue_absence;
    }

    public function scopeDuMois(Builder $query, string $mois): Builder
    {
        return $query->where('mois', $mois);
    }

    public function scopePayees(Builder $query): Builder
    {
        return $query->where('statut_paiement', 'paye');
    }
}