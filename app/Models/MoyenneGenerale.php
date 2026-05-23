<?php

namespace App\Models;

use App\Models\AnneeScolaire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenneGenerale extends Model
{
    protected $table = 'moyennes_generales';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'trimestre_id',
        'annee_scolaire_id',
        'moyenne_generale',
        'total_points',
        'total_coefficients',
        'rang',
        'effectif_classe',
        'moyenne_premier',
        'moyenne_dernier',
        'moyenne_classe',
        'mention',
        'appreciation_generale',
        'decision',
        'total_absences',
        'absences_justifiees',
        'total_retards',
    ];

    protected $casts = [
        'moyenne_generale'   => 'decimal:2',
        'total_points'       => 'decimal:2',
        'total_coefficients' => 'decimal:1',
        'moyenne_premier'    => 'decimal:2',
        'moyenne_dernier'    => 'decimal:2',
        'moyenne_classe'     => 'decimal:2',
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
}