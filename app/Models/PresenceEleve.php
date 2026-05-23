<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceEleve extends Model
{
    protected $table = 'presences_eleves';

    public const STATUT_PRESENT = 'present';
    public const STATUT_ABSENT = 'absent';
    public const STATUT_RETARD = 'retard';
    public const STATUT_DISPENSE = 'dispense';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'creneau_id',
        'date',
        'statut',
        'periode',
        'motif',
        'justifie',
        'justification',
        'observation',
        'saisie_par',
        'traite_par',
        'traite_at',
    ];

    protected $casts = [
        'date'      => 'date',
        'justifie'  => 'boolean',
        'traite_at' => 'datetime',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function saisiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisie_par');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(Creneau::class);
    }

    public function scopeDuJour(Builder $query): Builder
    {
        return $query->whereDate('date', today());
    }

    public function scopePresents(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PRESENT);
    }

    public function scopeAbsents(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_ABSENT);
    }

    public function scopeRetards(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_RETARD);
    }
}