<?php
// ══════════════════════════════════════════════════════════════
// app/Models/CompteComptable.php — MODULE 12
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class CompteComptable extends Model
{
    protected $table = 'comptes_comptables';
    protected $fillable = ['etablissement_id', 'numero', 'libelle', 'type', 'categorie', 'parent_numero', 'solde_initial', 'solde_actuel', 'actif', 'systeme'];
    protected $casts = ['actif' => 'boolean', 'systeme' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function ecrituresDebit(): HasMany { return $this->hasMany(EcritureComptable::class, 'compte_debit_id'); }
    public function ecrituresCredit(): HasMany { return $this->hasMany(EcritureComptable::class, 'compte_credit_id'); }

    public function recalculerSolde(): void
    {
        $debits = $this->ecrituresDebit()->where('valide', true)->sum('montant');
        $credits = $this->ecrituresCredit()->where('valide', true)->sum('montant');
        if (in_array($this->type, ['actif', 'charge', 'tresorerie'], true)) {
            $this->update(['solde_actuel' => $this->solde_initial + $debits - $credits]);
        } else {
            $this->update(['solde_actuel' => $this->solde_initial + $credits - $debits]);
        }
    }
}
