<?php

// ══════════════════════════════════════════════════════════════
// app/Models/ScoreFinancier.php — MODULE 17
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreFinancier extends Model
{
    protected $table = 'scores_financiers';
    protected $fillable = [
        'etablissement_id', 'date_calcul', 'score_global', 'indicateur',
        'score_tresorerie', 'score_recouvrement', 'score_rentabilite', 'score_budget',
        'score_masse_salariale', 'score_endettement', 'ratio_liquidite', 'ratio_ms_revenus',
        'ratio_charges_fixes', 'fonds_roulement_mois', 'risques_detectes', 'recommandations', 'tendances',
    ];
    protected $casts = ['date_calcul' => 'date', 'risques_detectes' => 'json', 'recommandations' => 'json', 'tendances' => 'json'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }

    public static function calculerPourEtablissement(Etablissement $etab): self
    {
        $tresorerie = CompteTresorerie::where('etablissement_id', $etab->id)->where('actif', true)->sum('solde_actuel');
        $depensesMois = Depense::where('etablissement_id', $etab->id)->approuvees()->mois(now()->format('Y-m'))->sum('montant');
        $fonds = $depensesMois > 0 ? round($tresorerie / $depensesMois, 1) : 99;

        $scoreTreso = min(100, max(0, $fonds * 20));
        $scoreRecouv = (float) ($etab->getParametre('taux_recouvrement', 50));
        $scoreGlobal = round(($scoreTreso + $scoreRecouv) / 2, 2);

        $indicateur = $scoreGlobal >= 70 ? 'vert' : ($scoreGlobal >= 40 ? 'orange' : 'rouge');

        return static::create([
            'etablissement_id' => $etab->id,
            'date_calcul' => today(),
            'score_global' => $scoreGlobal,
            'indicateur' => $indicateur,
            'score_tresorerie' => $scoreTreso,
            'score_recouvrement' => $scoreRecouv,
            'fonds_roulement_mois' => $fonds,
        ]);
    }
}

