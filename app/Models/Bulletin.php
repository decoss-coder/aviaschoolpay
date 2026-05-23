<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    protected $table = 'bulletins';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'trimestre_id',
        'annee_scolaire_id',
        'moyenne_generale',
        'rang',
        'mention',
        'statut',
        'pdf_path',
        'genere_par',
        'date_generation',
    ];

    protected $casts = [
        'moyenne_generale' => 'decimal:2',
        'date_generation' => 'datetime',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function generePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'genere_par');
    }
}