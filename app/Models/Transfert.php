<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfert extends Model
{
    protected $fillable = [
        'eleve_id', 'etablissement_origine_id', 'etablissement_destination_id',
        'etablissement_destination_nom', 'etablissement_destination_code_desps',
        'annee_scolaire_id', 'type', 'statut', 'date_demande', 'date_effectif',
        'motif', 'fiche_transfert_path', 'quitus_path', 'numero_decision_sigfne',
    ];
    protected $casts = ['date_demande' => 'date', 'date_effectif' => 'date'];

    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
}
