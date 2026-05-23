<?php

// ══════════════════════════════════════════════════════════════
// app/Models/SimulationFinanciere.php — MODULE 16
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationFinanciere extends Model
{
    protected $table = 'simulations_financieres';
    protected $fillable = [
        'etablissement_id', 'cree_par', 'nom', 'description', 'type', 'horizon',
        'parametres', 'resultats', 'impact_revenus', 'impact_depenses', 'impact_marge',
        'impact_tresorerie', 'roi_pourcent', 'delai_rentabilite_mois', 'statut', 'favori',
    ];
    protected $casts = ['parametres' => 'json', 'resultats' => 'json', 'favori' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function creePar(): BelongsTo { return $this->belongsTo(User::class, 'cree_par'); }
}

