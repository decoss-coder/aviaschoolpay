<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class RemonteeSigfne extends Model
{
    protected $table = 'remontees_sigfne';
    protected $fillable = [
        'etablissement_id', 'trimestre_id', 'annee_scolaire_id', 'plateforme', 'type',
        'total_eleves', 'eleves_remontes', 'eleves_en_erreur', 'eleves_sans_matricule',
        'statut', 'fichier_export_path', 'date_envoi', 'date_validation_drena',
        'envoye_par', 'erreurs_detail', 'reponse_sigfne', 'observations',
    ];
    protected $casts = [
        'date_envoi' => 'datetime', 'date_validation_drena' => 'datetime',
        'erreurs_detail' => 'json', 'reponse_sigfne' => 'json',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function trimestre(): BelongsTo { return $this->belongsTo(Trimestre::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function envoyePar(): BelongsTo { return $this->belongsTo(User::class, 'envoye_par'); }
    public function detailEleves(): HasMany { return $this->hasMany(RemonteeEleve::class, 'remontee_sigfne_id'); }

    public function tauxReussite(): float
    {
        return $this->total_eleves > 0 ? round(($this->eleves_remontes / $this->total_eleves) * 100, 1) : 0;
    }
}
