<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvenementScolaire extends Model
{
    protected $table = 'evenements_scolaires';

    protected $fillable = [
        'etablissement_id', 'annee_scolaire_id', 'titre', 'type',
        'date_debut', 'date_fin', 'description', 'lieu', 'couleur',
        'toute_journee', 'heure_debut', 'heure_fin', 'publie', 'cree_par',
    ];

    protected $casts = [
        'date_debut'    => 'date',
        'date_fin'      => 'date',
        'toute_journee' => 'boolean',
        'publie'        => 'boolean',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function creePar(): BelongsTo { return $this->belongsTo(User::class, 'cree_par'); }
}
