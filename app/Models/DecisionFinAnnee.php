<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionFinAnnee extends Model
{
    protected $table = 'decisions_fin_annee';
    protected $fillable = [
        'etablissement_id', 'eleve_id', 'classe_id', 'annee_scolaire_id', 'moyenne_annuelle',
        'decision', 'classe_proposee', 'serie_proposee_id', 'suggestion_ia',
        'statut_validation', 'valide_par_pp', 'date_validation_pp',
        'valide_par_directeur', 'date_validation_directeur',
        'date_soumission_sigfne', 'date_approbation_drena', 'motif_refus_drena', 'observations',
    ];
    protected $casts = [
        'date_validation_pp' => 'datetime', 'date_validation_directeur' => 'datetime',
        'date_soumission_sigfne' => 'datetime', 'date_approbation_drena' => 'datetime',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
    public function classe(): BelongsTo { return $this->belongsTo(Classe::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function serieProposee(): BelongsTo { return $this->belongsTo(Serie::class, 'serie_proposee_id'); }
}
