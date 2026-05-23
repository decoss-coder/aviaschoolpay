<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlerteFinanciere;
use App\Models\AnalyseRentabilite;
use App\Models\Budget;
use App\Models\CompteTresorerie;
use App\Models\Depense;
use App\Models\Enseignant;
use App\Models\ExerciceComptable;
use App\Models\Inscription;
use App\Models\LigneBudgetaire;
use App\Models\Paiement;
use App\Models\ScoreFinancier;
use App\Models\SimulationFinanciere;
use App\Models\SnapshotFinancier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CockpitFinancierController extends Controller
{
    public function cockpit360(Request $request): JsonResponse
    {
        $etab   = $request->user()->etablissement;
        $etabId = $etab->id;

        $comptesTreso = CompteTresorerie::where('etablissement_id', $etabId)->where('actif', true)->get();
        $soldeCaisse  = $comptesTreso->where('type', 'caisse')->sum('solde_actuel');
        $soldeBanque  = $comptesTreso->where('type', 'banque')->sum('solde_actuel');
        $soldeMM      = $comptesTreso->where('type', 'mobile_money')->sum('solde_actuel');
        $tresoTotale  = $soldeCaisse + $soldeBanque + $soldeMM;

        $moisActuel  = now()->format('Y-m');
        $depensesMois = Depense::where('etablissement_id', $etabId)->approuvees()->mois($moisActuel)->sum('montant');
        $revenusMois  = Paiement::where('etablissement_id', $etabId)
            ->where('statut', 'confirme')
            ->where('date_paiement', 'like', "$moisActuel%")
            ->sum('montant');

        $dernierScore    = ScoreFinancier::where('etablissement_id', $etabId)->latest('date_calcul')->first();
        $alertesCritiques = AlerteFinanciere::where('etablissement_id', $etabId)->where('traitee', false)->where('gravite', 'critique')->count();

        $exercice         = ExerciceComptable::where('etablissement_id', $etabId)->enCours()->first();
        $budgetActif      = $exercice ? Budget::where('exercice_id', $exercice->id)->where('statut', 'en_cours')->first() : null;
        $depassementsBudget = $budgetActif ? LigneBudgetaire::where('budget_id', $budgetActif->id)->where('alerte_depassement', true)->count() : 0;

        $totalSalaires = Enseignant::where('etablissement_id', $etabId)->where('actif', true)->sum('salaire_base');
        $ratioMS       = $revenusMois > 0 ? round(($totalSalaires / $revenusMois) * 100, 1) : 0;

        return response()->json([
            'tresorerie' => [
                'caisse'       => $soldeCaisse,
                'banque'       => $soldeBanque,
                'mobile_money' => $soldeMM,
                'total'        => $tresoTotale,
            ],
            'mois_en_cours' => [
                'revenus'      => $revenusMois,
                'depenses'     => $depensesMois,
                'resultat'     => $revenusMois - $depensesMois,
                'beneficiaire' => $revenusMois > $depensesMois,
            ],
            'score_financier' => $dernierScore ? [
                'score'                => $dernierScore->score_global,
                'indicateur'           => $dernierScore->indicateur,
                'date'                 => $dernierScore->date_calcul->format('d/m/Y'),
                'fonds_roulement_mois' => $dernierScore->fonds_roulement_mois,
            ] : null,
            'alertes_critiques'  => $alertesCritiques,
            'depassements_budget' => $depassementsBudget,
            'masse_salariale' => [
                'total'        => $totalSalaires,
                'ratio_revenus' => $ratioMS,
                'sain'         => $ratioMS <= 65,
            ],
        ]);
    }

    public function scoreSante(Request $request): JsonResponse
    {
        $etab  = $request->user()->etablissement;
        $score = ScoreFinancier::calculerPourEtablissement($etab);
        return response()->json($score);
    }

    public function alertes(Request $request): JsonResponse
    {
        $alertes = AlerteFinanciere::where('etablissement_id', $request->user()->etablissement_id)
            ->where('traitee', false)
            ->orderByRaw("FIELD(gravite, 'critique', 'warning', 'info')")
            ->latest()
            ->paginate(20);
        return response()->json($alertes);
    }

    public function traiterAlerte(Request $request, AlerteFinanciere $alerte): JsonResponse
    {
        abort_unless($alerte->etablissement_id === $request->user()->etablissement_id, 403);

        $alerte->update([
            'traitee'        => true,
            'traitee_par'    => $request->user()->id,
            'traitee_le'     => now(),
            'commentaire'    => $request->input('commentaire'),
        ]);

        return response()->json(['message' => 'Alerte traitée.', 'alerte' => $alerte]);
    }

    public function rentabiliteParClasse(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement;
        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->enCours()->first();
        if (! $exercice) {
            return response()->json(['error' => "Pas d'exercice en cours"], 404);
        }

        $analyses = AnalyseRentabilite::where('etablissement_id', $etab->id)
            ->where('exercice_id', $exercice->id)
            ->where('niveau_analyse', 'classe')
            ->orderByDesc('taux_marge')
            ->get();

        return response()->json([
            'exercice'           => $exercice->libelle,
            'analyses'           => $analyses,
            'classes_rentables'  => $analyses->where('rentable', true)->count(),
            'classes_deficitaires' => $analyses->where('rentable', false)->count(),
        ]);
    }

    public function rentabiliteParService(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement;
        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->enCours()->first();

        $analyses = AnalyseRentabilite::where('etablissement_id', $etab->id)
            ->where('exercice_id', $exercice?->id)
            ->where('niveau_analyse', 'centre_profit')
            ->orderByDesc('taux_marge')
            ->get();

        return response()->json($analyses);
    }

    public function simuler(Request $request): JsonResponse
    {
        $request->validate([
            'nom'        => 'required|string|max:200',
            'type'       => 'required|in:augmentation_effectif,reduction_effectif,augmentation_tarif,reduction_tarif,ajout_service,recrutement,reduction_couts,scenario_libre',
            'horizon'    => 'required|in:3_mois,6_mois,1_an,2_ans,3_ans',
            'parametres' => 'required|array',
        ]);

        $etab     = $request->user()->etablissement;
        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->enCours()->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $revenuActuel   = $annee ? Inscription::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->sum('montant_net') : 0;
        $depenseActuelle = $exercice ? Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant') : 0;
        $nbEleves       = $annee ? Inscription::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->count() : 0;

        $params          = $request->parametres;
        $impactRevenus   = 0;
        $impactDepenses  = 0;

        switch ($request->type) {
            case 'augmentation_effectif':
                $nbSupp        = $params['nb_eleves'] ?? 0;
                $revMoyen      = $nbEleves > 0 ? $revenuActuel / $nbEleves : 150000;
                $impactRevenus  = $nbSupp * $revMoyen;
                $impactDepenses = $nbSupp * 30000;
                break;
            case 'augmentation_tarif':
                $hausse        = ($params['pourcentage'] ?? 0) / 100;
                $impactRevenus  = $revenuActuel * $hausse;
                break;
            case 'reduction_couts':
                $reduction     = ($params['pourcentage'] ?? 0) / 100;
                $impactDepenses = -($depenseActuelle * $reduction);
                break;
            case 'recrutement':
                $nbPersonnes   = $params['nb_personnes'] ?? 0;
                $salaireMoyen  = $params['salaire_moyen'] ?? 200000;
                $impactDepenses = $nbPersonnes * $salaireMoyen * 12;
                break;
        }

        $simulation = SimulationFinanciere::create([
            'etablissement_id' => $etab->id,
            'cree_par'         => $request->user()->id,
            'nom'              => $request->nom,
            'type'             => $request->type,
            'horizon'          => $request->horizon,
            'parametres'       => $params,
            'impact_revenus'   => $impactRevenus,
            'impact_depenses'  => $impactDepenses,
            'impact_marge'     => $impactRevenus - $impactDepenses,
            'impact_tresorerie' => $impactRevenus - $impactDepenses,
            'roi_pourcent'     => $impactDepenses != 0 ? round((($impactRevenus - $impactDepenses) / abs($impactDepenses)) * 100, 2) : null,
            'statut'           => 'calcule',
        ]);

        return response()->json($simulation);
    }

    public function simulations(Request $request): JsonResponse
    {
        $sims = SimulationFinanciere::where('etablissement_id', $request->user()->etablissement_id)
            ->latest()
            ->paginate(20);
        return response()->json($sims);
    }

    public function budgetVsReel(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        if (! $exercice) {
            return response()->json(['error' => "Pas d'exercice"], 404);
        }

        $budget = Budget::where('exercice_id', $exercice->id)->where('statut', 'en_cours')->with('lignes')->first();
        if (! $budget) {
            return response()->json(['error' => 'Pas de budget actif'], 404);
        }

        return response()->json([
            'budget' => $budget->libelle,
            'prevu'  => [
                'revenus'  => $budget->total_prevu_revenus,
                'depenses' => $budget->total_prevu_depenses,
                'resultat' => $budget->resultatPrevu(),
            ],
            'reel' => [
                'revenus'  => $budget->total_reel_revenus,
                'depenses' => $budget->total_reel_depenses,
                'resultat' => $budget->resultatReel(),
            ],
            'ecart'        => $budget->ecartGlobal(),
            'lignes'       => $budget->lignes->sortByDesc('ecart')->values(),
            'depassements' => $budget->lignes->where('alerte_depassement', true)->count(),
        ]);
    }
}
