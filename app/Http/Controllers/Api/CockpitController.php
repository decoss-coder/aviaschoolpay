<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlerteFinanciere;
use App\Models\Depense;
use App\Models\ExerciceComptable;
use App\Models\Inscription;
use App\Models\ScoreFinancier;
use App\Models\SnapshotFinancier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CockpitController extends Controller
{
    public function historiqueSnapshots(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $snapshots = SnapshotFinancier::where('etablissement_id', $etab)
            ->orderByDesc('date_snapshot')
            ->limit(24)
            ->get();

        return response()->json([
            'snapshots' => $snapshots,
            'nombre'    => $snapshots->count(),
        ]);
    }

    public function tendances(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $scores = ScoreFinancier::where('etablissement_id', $etab)
            ->orderByDesc('date_calcul')
            ->limit(12)
            ->get(['score_global', 'indicateur', 'date_calcul', 'fonds_roulement_mois'])
            ->sortBy('date_calcul')
            ->values();

        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $request->user()->etablissement_id);

        $depensesMois = [];
        $revenusMois  = [];
        for ($i = 5; $i >= 0; $i--) {
            $mois = now()->subMonths($i)->format('Y-m');
            $depensesMois[$mois] = Depense::where('etablissement_id', $etab)
                ->approuvees()
                ->where('date_depense', 'like', "$mois%")
                ->sum('montant');
        }

        return response()->json([
            'scores'           => $scores,
            'depenses_6_mois'  => $depensesMois,
            'tendance_score'   => $this->calculerTendance($scores->pluck('score_global')->toArray()),
        ]);
    }

    public function diagnosticIA(Request $request): JsonResponse
    {
        $etab  = $request->user()->etablissement_id;
        $etabM = $request->user()->etablissement;

        $dernierScore  = ScoreFinancier::where('etablissement_id', $etab)->latest('date_calcul')->first();
        $alertesCrit   = AlerteFinanciere::where('etablissement_id', $etab)->where('traitee', false)->where('gravite', 'critique')->count();
        $alertesWarn   = AlerteFinanciere::where('etablissement_id', $etab)->where('traitee', false)->where('gravite', 'warning')->count();
        $exercice      = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        $annee         = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etabM->id);

        $revenusMois   = 0;
        $depensesMois  = Depense::where('etablissement_id', $etab)->approuvees()->mois(now()->format('Y-m'))->sum('montant');

        if ($annee) {
            $revenusMois = Inscription::where('etablissement_id', $etab)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'validee')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('montant_net');
        }

        $recommandations = [];

        if ($alertesCrit > 0) {
            $recommandations[] = [
                'priorite' => 'critique',
                'message'  => "$alertesCrit alerte(s) critique(s) non traitée(s). Action immédiate requise.",
                'action'   => 'Consulter les alertes financières.',
            ];
        }

        if ($depensesMois > $revenusMois && $revenusMois > 0) {
            $depassement = $depensesMois - $revenusMois;
            $recommandations[] = [
                'priorite' => 'warning',
                'message'  => "Les dépenses dépassent les revenus de ".number_format($depassement, 0, ',', ' ')." FCFA ce mois.",
                'action'   => 'Revoir les dépenses non essentielles ou accélérer les encaissements.',
            ];
        }

        if ($dernierScore && $dernierScore->score_global < 50) {
            $recommandations[] = [
                'priorite' => 'warning',
                'message'  => "Score de santé financière faible ({$dernierScore->score_global}/100).",
                'action'   => 'Analyser les composantes du score et établir un plan d\'action.',
            ];
        }

        if (empty($recommandations)) {
            $recommandations[] = [
                'priorite' => 'info',
                'message'  => 'Situation financière globalement saine. Continuez le suivi régulier.',
                'action'   => 'Maintenir le cap et anticiper les prochaines échéances.',
            ];
        }

        return response()->json([
            'score_actuel'     => $dernierScore?->score_global,
            'alertes_critiques' => $alertesCrit,
            'alertes_warning'  => $alertesWarn,
            'recommandations'  => $recommandations,
            'genere_le'        => now()->toIso8601String(),
        ]);
    }

    private function calculerTendance(array $scores): string
    {
        if (count($scores) < 2) {
            return 'stable';
        }
        $debut = $scores[0];
        $fin   = end($scores);
        if ($fin > $debut + 5) return 'hausse';
        if ($fin < $debut - 5) return 'baisse';
        return 'stable';
    }
}
