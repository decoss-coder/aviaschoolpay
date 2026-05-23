<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenneMatiere extends Model
{
    protected $table = 'moyennes_matieres';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'trimestre_id',
        'moyenne',
        'moyenne_ponderee',
        'rang_classe',
        'note_min_classe',
        'note_max_classe',
        'moyenne_classe',
        'appreciation',
        'saisie_directe',
        'saisie_par',
        'date_saisie',
        'publie',
        'date_publication',
    ];

    protected $casts = [
        'moyenne' => 'decimal:2',
        'moyenne_ponderee' => 'decimal:2',
        'note_min_classe' => 'decimal:2',
        'note_max_classe' => 'decimal:2',
        'moyenne_classe' => 'decimal:2',
        'saisie_directe' => 'boolean',
        'publie' => 'boolean',
        'date_saisie' => 'datetime',
        'date_publication' => 'datetime',
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

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class);
    }

    /** Exclut les sous-disciplines (moyennes rattachées à une matière parente). */
    public function scopeMatierePrincipaleOnly(Builder $query): Builder
    {
        return $query->whereHas('matiere', fn (Builder $q) => $q->whereNull('parent_matiere_id'));
    }
}