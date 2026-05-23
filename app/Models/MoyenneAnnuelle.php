<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenneAnnuelle extends Model
{
    protected $table = 'moyennes_annuelles';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'annee_scolaire_id',
        'moyenne_annuelle',
        'rang_annuel',
        'mention',
        'decision_finale',
        'appreciation',
    ];

    protected $casts = [
        'moyenne_annuelle' => 'decimal:2',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}