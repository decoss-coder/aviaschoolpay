<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Inscription extends Model
{
    protected $fillable = [
        'eleve_id', 'classe_id', 'annee_scolaire_id', 'etablissement_id', 'date_inscription',
        'type', 'statut', 'montant_inscription', 'montant_scolarite', 'reduction', 'montant_net', 'motif_reduction',
        'dossier_complet', 'observations',
    ];
    protected $casts = ['date_inscription' => 'date', 'dossier_complet' => 'boolean'];

    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
    public function classe(): BelongsTo { return $this->belongsTo(Classe::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function paiements(): HasMany { return $this->hasMany(Paiement::class); }
    public function echeances(): HasMany { return $this->hasMany(Echeance::class); }

    public function montantPaye(): int
    {
        return (int) $this->paiements()->where('statut', 'confirme')->sum('montant');
    }

    public function resteAPayer(): int
    {
        return $this->montant_net - $this->montantPaye();
    }

    public function tauxPaiement(): float
    {
        return $this->montant_net > 0 ? round(($this->montantPaye() / $this->montant_net) * 100, 1) : 0;
    }
}
