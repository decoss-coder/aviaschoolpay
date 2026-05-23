<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devoir extends Model
{
    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'titre',
        'description',
        'type',
        'date_publication',
        'date_limite',
        'fichier_path',
        'fichier_corrige_path',
        'publie',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'date_limite'      => 'date',
        'publie'           => 'boolean',
    ];

    public function classe(): BelongsTo   { return $this->belongsTo(Classe::class); }
    public function matiere(): BelongsTo  { return $this->belongsTo(Matiere::class); }
    public function enseignant(): BelongsTo { return $this->belongsTo(Enseignant::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
}
