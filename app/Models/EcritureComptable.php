<?php

// ══════════════════════════════════════════════════════════════
// app/Models/EcritureComptable.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CompteComptable;

class EcritureComptable extends Model
{
    protected $table = 'ecritures_comptables';
    protected $fillable = [
        'etablissement_id', 'exercice_id', 'numero_piece', 'date_ecriture', 'libelle',
        'compte_debit_id', 'compte_credit_id', 'montant', 'type_piece',
        'reference_externe', 'reference_type', 'reference_id',
        'saisie_par', 'valide_par', 'valide', 'observations',
    ];
    protected $casts = ['date_ecriture' => 'date', 'valide' => 'boolean'];

    public function exercice(): BelongsTo { return $this->belongsTo(ExerciceComptable::class, 'exercice_id'); }
    public function compteDebit(): BelongsTo { return $this->belongsTo(CompteComptable::class, 'compte_debit_id'); }
    public function compteCredit(): BelongsTo { return $this->belongsTo(CompteComptable::class, 'compte_credit_id'); }
    public function saisiePar(): BelongsTo { return $this->belongsTo(User::class, 'saisie_par'); }

    public static function genererNumero(int $etabId): string
    {
        $count = static::where('etablissement_id', $etabId)->whereMonth('date_ecriture', now()->month)->count();
        return sprintf('EC-%s-%04d', now()->format('Y-m'), $count + 1);
    }

    public static function enregistrerPaiement(Paiement $paiement): self
    {
        $exercice = ExerciceComptable::where('etablissement_id', $paiement->etablissement_id)->enCours()->first();
        if (!$exercice) return new self;

        $compteDebitId = CompteComptable::where('etablissement_id', $paiement->etablissement_id)
            ->where('categorie', $paiement->estMobileMoney() ? 'mobile_money' : 'caisse')
            ->first()?->id;

        $compteCreditId = CompteComptable::where('etablissement_id', $paiement->etablissement_id)
            ->where('categorie', 'scolarite')
            ->first()?->id;

        $ecriture = static::create([
            'etablissement_id' => $paiement->etablissement_id,
            'exercice_id' => $exercice->id,
            'numero_piece' => static::genererNumero($paiement->etablissement_id),
            'date_ecriture' => $paiement->date_paiement,
            'libelle' => "Paiement scolarité - {$paiement->reference}",
            'compte_debit_id' => $compteDebitId,
            'compte_credit_id' => $compteCreditId,
            'montant' => $paiement->montant,
            'type_piece' => 'paiement_scolarite',
            'reference_type' => 'paiement',
            'reference_id' => $paiement->id,
            'saisie_par' => $paiement->encaisse_par ?? auth()->id(),
            'valide' => true,
        ]);

        if ($compteDebitId) {
            CompteComptable::find($compteDebitId)?->recalculerSolde();
        }

        if ($compteCreditId) {
            CompteComptable::find($compteCreditId)?->recalculerSolde();
        }

        return $ecriture;
    }
}