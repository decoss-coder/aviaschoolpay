<?php

// ══════════════════════════════════════════════════════════════
// app/Models/LigneBudgetaire.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneBudgetaire extends Model
{
    protected $table = 'lignes_budgetaires';
    protected $fillable = ['budget_id', 'categorie_depense_id', 'compte_comptable_numero', 'libelle', 'type', 'service', 'mois', 'montant_prevu', 'montant_reel', 'ecart', 'taux_realisation', 'alerte_depassement', 'seuil_alerte_pourcent', 'observations'];
    protected $casts = ['alerte_depassement' => 'boolean'];

    public function budget(): BelongsTo { return $this->belongsTo(Budget::class); }
    public function categorieDepense(): BelongsTo { return $this->belongsTo(CategorieDepense::class, 'categorie_depense_id'); }

    public function recalculer(): void
    {
        $this->ecart = $this->montant_reel - $this->montant_prevu;
        $this->taux_realisation = $this->montant_prevu > 0 ? round(($this->montant_reel / $this->montant_prevu) * 100, 2) : 0;
        $this->alerte_depassement = $this->taux_realisation >= $this->seuil_alerte_pourcent;
        $this->save();
    }
}
