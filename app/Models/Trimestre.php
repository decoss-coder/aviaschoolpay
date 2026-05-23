<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trimestre extends Model
{
    protected $table = 'trimestres';

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'numero',
        'libelle',
        'coefficient',
        'date_debut',
        'date_fin',
        'statut',
        'actif',
        'en_cours',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
        'en_cours' => 'boolean',
        'coefficient' => 'decimal:1',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function moyennesMatieres(): HasMany
    {
        return $this->hasMany(MoyenneMatiere::class);
    }

    public function moyennesGenerales(): HasMany
    {
        return $this->hasMany(MoyenneGenerale::class);
    }

    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    public function couvreDate($date): bool
    {
        $date = now()->parse($date)->toDateString();

        return $date >= $this->date_debut?->toDateString()
            && $date <= $this->date_fin?->toDateString();
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}