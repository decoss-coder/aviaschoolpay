<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnneeScolaireRestaurationDemande extends Model
{
    protected $table = 'annee_scolaire_restauration_demandes';

    protected $fillable = [
        'etablissement_id', 'annee_scolaire_id', 'demandeur_id',
        'montant_fcfa', 'statut', 'reference', 'wave_checkout_url',
        'paye_at', 'cle_livree_at', 'restauree_at',
    ];

    protected $casts = [
        'paye_at' => 'datetime',
        'cle_livree_at' => 'datetime',
        'restauree_at' => 'datetime',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public static function genererReference(): string
    {
        return 'REST-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
    }
}
