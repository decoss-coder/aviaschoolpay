<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploiDuTempsLearningRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'salle_id',
        'creneau_id',
        'jour',
        'rule_type',
        'weight',
        'hits',
        'active',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
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

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(Creneau::class);
    }
}