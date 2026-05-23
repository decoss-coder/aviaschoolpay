<?php

// ══════════════════════════════════════════════════════════════
// app/Models/SnapshotFinancier.php
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotFinancier extends Model
{
    protected $table = 'snapshots_financiers';
    protected $fillable = [
        'etablissement_id', 'date_snapshot', 'solde_caisse', 'solde_banque', 'solde_mobile_money',
        'tresorerie_totale', 'revenus_jour', 'depenses_jour', 'revenus_mois_cumul', 'depenses_mois_cumul',
        'revenus_exercice_cumul', 'depenses_exercice_cumul', 'resultat_exercice', 'creances_totales', 'nb_impayes',
    ];
    protected $casts = ['date_snapshot' => 'date'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
}
