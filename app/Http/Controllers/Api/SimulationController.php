<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Models\ExerciceComptable;
use App\Models\Inscription;
use App\Models\SimulationFinanciere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    public function show(Request $request, SimulationFinanciere $simulation): JsonResponse
    {
        abort_unless($simulation->etablissement_id === $request->user()->etablissement_id, 403);
        return response()->json($simulation);
    }

    public function destroy(Request $request, SimulationFinanciere $simulation): JsonResponse
    {
        abort_unless($simulation->etablissement_id === $request->user()->etablissement_id, 403);
        $simulation->delete();
        return response()->json(['message' => 'Simulation supprimée.']);
    }

    public function projections6Mois(Request $request): JsonResponse
    {
        return $this->projections($request, 6);
    }

    public function projectionsAnnee(Request $request): JsonResponse
    {
        return $this->projections($request, 12);
    }

    private function projections(Request $request, int $mois): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $request->user()->etablissement_id);

        $revenuMensuelMoyen = 0;
        $depenseMensuelleMoyenne = 0;

        if ($annee) {
            $nbMoisAnnee = max(1, now()->month);
            $totalRevenus = Inscription::where('etablissement_id', $etab)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'validee')
                ->sum('montant_net');
            $revenuMensuelMoyen = round($totalRevenus / $nbMoisAnnee);
        }

        if ($exercice) {
            $totalDepenses = Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant');
            $nbMoisExercice = max(1, now()->month);
            $depenseMensuelleMoyenne = round($totalDepenses / $nbMoisExercice);
        }

        $projections = [];
        for ($i = 1; $i <= $mois; $i++) {
            $date = now()->addMonths($i);
            $projections[] = [
                'mois'     => $date->format('Y-m'),
                'label'    => $date->translatedFormat('M Y'),
                'revenus'  => $revenuMensuelMoyen,
                'depenses' => $depenseMensuelleMoyenne,
                'resultat' => $revenuMensuelMoyen - $depenseMensuelleMoyenne,
            ];
        }

        return response()->json([
            'horizon_mois'   => $mois,
            'projections'    => $projections,
            'total_revenus'  => array_sum(array_column($projections, 'revenus')),
            'total_depenses' => array_sum(array_column($projections, 'depenses')),
            'total_resultat' => array_sum(array_column($projections, 'resultat')),
            'note'           => 'Projection basée sur les moyennes mensuelles de l\'exercice en cours.',
        ]);
    }
}
