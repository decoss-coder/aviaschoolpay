<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Echeance extends Model
{
    protected $fillable = [
        'inscription_id', 'plan_paiement_id', 'numero_echeance', 'libelle',
        'montant', 'date_echeance', 'montant_paye', 'reste_a_payer',
        'statut', 'nb_relances_envoyees', 'derniere_relance_date',
    ];
    protected $casts = ['date_echeance' => 'date', 'derniere_relance_date' => 'date'];

    public function inscription(): BelongsTo { return $this->belongsTo(Inscription::class); }
    public function paiements(): HasMany { return $this->hasMany(Paiement::class); }

    public function estEnRetard(): bool { return $this->date_echeance->isPast() && $this->reste_a_payer > 0; }
}
