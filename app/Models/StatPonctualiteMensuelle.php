<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatPonctualiteMensuelle extends Model
{
    protected $table = 'stats_ponctualite_mensuelles';

    protected $fillable = [
        'enseignant_id',
        'mois',
        'jours_travailles',
        'presents',
        'retards',
        'absents',
        'absents_justifies',
        'score_ponctualite',
        'heure_arrivee_moyenne',
        'alertes_fraude',
    ];

    protected $casts = [
        'score_ponctualite' => 'decimal:2',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function getTauxPresenceAttribute(): float
    {
        if ((int) $this->jours_travailles <= 0) {
            return 0;
        }

        return round(((int) $this->presents / (int) $this->jours_travailles) * 100, 2);
    }

    public function getTauxRetardAttribute(): float
    {
        if ((int) $this->jours_travailles <= 0) {
            return 0;
        }

        return round(((int) $this->retards / (int) $this->jours_travailles) * 100, 2);
    }

    public function scopeDuMois(Builder $query, string $mois): Builder
    {
        return $query->where('mois', $mois);
    }
}