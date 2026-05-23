<?php

// ══════════════════════════════════════════════════════════════
// app/Models/AnalyseRentabilite.php — MODULE 15
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyseRentabilite extends Model
{
    protected $table = 'analyses_rentabilite';
    protected $fillable = [
        'etablissement_id', 'exercice_id', 'mois', 'niveau_analyse', 'cible_label', 'cible_id',
        'revenus', 'couts_directs', 'couts_indirects', 'cout_total', 'marge_brute', 'marge_nette',
        'taux_marge', 'rentable', 'nb_eleves', 'revenu_par_eleve', 'cout_par_eleve',
        'marge_par_eleve', 'nb_enseignants', 'cout_par_enseignant', 'details',
    ];
    protected $casts = ['rentable' => 'boolean', 'details' => 'json'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
}