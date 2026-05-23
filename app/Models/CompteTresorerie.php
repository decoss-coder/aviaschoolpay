<?php

// ══════════════════════════════════════════════════════════════
// app/Models/CompteTresorerie.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class CompteTresorerie extends Model
{
    protected $table = 'comptes_tresorerie';
    protected $fillable = ['etablissement_id', 'nom', 'type', 'numero_compte', 'banque', 'operateur', 'solde_initial', 'solde_actuel', 'compte_comptable_numero', 'actif', 'principal'];
    protected $casts = ['actif' => 'boolean', 'principal' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function mouvements(): HasMany { return $this->hasMany(MouvementTresorerie::class); }

    public function enregistrerMouvement(string $sens, int $montant, string $libelle, ?string $refType = null, ?int $refId = null): void
    {
        $soldeAvant = $this->solde_actuel;
        $soldeApres = $sens === 'entree' ? $soldeAvant + $montant : $soldeAvant - $montant;

        $this->mouvements()->create([
            'etablissement_id' => $this->etablissement_id,
            'sens' => $sens,
            'montant' => $montant,
            'solde_avant' => $soldeAvant,
            'solde_apres' => $soldeApres,
            'date_mouvement' => today(),
            'libelle' => $libelle,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'saisie_par' => auth()->id(),
        ]);

        $this->update(['solde_actuel' => $soldeApres]);
    }
}