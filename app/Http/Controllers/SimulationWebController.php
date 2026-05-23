<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\ExerciceComptable;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\SimulationFinanciere;
use Illuminate\Http\Request;

class SimulationWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement_id;
        $sims = SimulationFinanciere::where('etablissement_id', $etab)
            ->with('creePar:id,nom,prenom')
            ->latest()
            ->paginate(20);

        $stats = [
            'total'     => SimulationFinanciere::where('etablissement_id', $etab)->count(),
            'favoris'   => SimulationFinanciere::where('etablissement_id', $etab)->where('favori', true)->count(),
            'rentables' => SimulationFinanciere::where('etablissement_id', $etab)->where('impact_marge', '>', 0)->count(),
        ];

        return view('simulations.index', compact('sims', 'stats'));
    }

    public function create(Request $request)
    {
        return view('simulations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'         => 'required|string|max:200',
            'description' => 'nullable|string',
            'type'        => 'required|in:augmentation_effectif,reduction_effectif,augmentation_tarif,reduction_tarif,ajout_service,suppression_service,recrutement,reduction_personnel,investissement,reduction_couts,scenario_libre',
            'horizon'     => 'required|in:3_mois,6_mois,1_an,2_ans,3_ans',
            'parametres'  => 'required|array',
        ]);

        $etab = $request->user()->etablissement;
        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->where('en_cours', true)->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $revenusActuels = $annee
            ? (int) Paiement::where('etablissement_id', $etab->id)->where('statut', 'confirme')->pourAnnee($annee->id)->sum('montant')
            : 0;
        $depensesActuelles = $exercice
            ? (int) Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant')
            : 0;
        $nbEleves = $annee
            ? Inscription::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->count()
            : 0;

        $params = $validated['parametres'];
        [$impactRevenus, $impactDepenses] = $this->calculerImpact($validated['type'], $params, $revenusActuels, $depensesActuelles, $nbEleves);

        $impactMarge = $impactRevenus - $impactDepenses;
        $roi = $impactDepenses != 0 ? round((($impactRevenus - $impactDepenses) / abs($impactDepenses)) * 100, 2) : null;
        $delaiRentab = ($impactMarge > 0 && $impactDepenses > 0)
            ? (int) ceil($impactDepenses / max(1, $impactMarge / 12))
            : null;

        $simulation = SimulationFinanciere::create([
            'etablissement_id'       => $etab->id,
            'cree_par'               => $request->user()->id,
            'nom'                    => $validated['nom'],
            'description'            => $validated['description'] ?? null,
            'type'                   => $validated['type'],
            'horizon'                => $validated['horizon'],
            'parametres'             => $params,
            'impact_revenus'         => $impactRevenus,
            'impact_depenses'        => $impactDepenses,
            'impact_marge'           => $impactMarge,
            'impact_tresorerie'      => $impactMarge,
            'roi_pourcent'           => $roi,
            'delai_rentabilite_mois' => $delaiRentab,
            'statut'                 => 'calcule',
            'resultats'              => [
                'revenus_actuels'    => $revenusActuels,
                'depenses_actuelles' => $depensesActuelles,
                'nb_eleves'          => $nbEleves,
            ],
        ]);

        return redirect()->route('simulations.show', $simulation->id)->with('success', 'Simulation calculée.');
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $simulation = SimulationFinanciere::where('etablissement_id', $etab)
            ->with('creePar:id,nom,prenom')
            ->findOrFail($id);

        return view('simulations.show', compact('simulation'));
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $simulation = SimulationFinanciere::where('etablissement_id', $etab)->findOrFail($id);
        $simulation->delete();
        return redirect()->route('simulations.index')->with('success', 'Simulation supprimée.');
    }

    public function favori(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $simulation = SimulationFinanciere::where('etablissement_id', $etab)->findOrFail($id);
        $simulation->update(['favori' => ! $simulation->favori]);
        return back();
    }

    private function calculerImpact(string $type, array $params, int $revenusActuels, int $depensesActuelles, int $nbEleves): array
    {
        $impactRevenus = 0;
        $impactDepenses = 0;
        $revMoyen = $nbEleves > 0 ? (int) round($revenusActuels / $nbEleves) : 150000;

        switch ($type) {
            case 'augmentation_effectif':
                $n = (int) ($params['nb_eleves'] ?? 0);
                $impactRevenus = $n * $revMoyen;
                $impactDepenses = $n * (int) ($params['cout_par_eleve'] ?? 30000);
                break;
            case 'reduction_effectif':
                $n = (int) ($params['nb_eleves'] ?? 0);
                $impactRevenus = -1 * $n * $revMoyen;
                $impactDepenses = -1 * $n * (int) ($params['cout_par_eleve'] ?? 30000);
                break;
            case 'augmentation_tarif':
                $pct = (float) ($params['pourcentage'] ?? 0) / 100;
                $impactRevenus = (int) round($revenusActuels * $pct);
                break;
            case 'reduction_tarif':
                $pct = (float) ($params['pourcentage'] ?? 0) / 100;
                $impactRevenus = (int) round(-1 * $revenusActuels * $pct);
                break;
            case 'recrutement':
                $nb = (int) ($params['nb_personnes'] ?? 0);
                $salaire = (int) ($params['salaire_moyen'] ?? 200000);
                $impactDepenses = $nb * $salaire * 12;
                break;
            case 'reduction_personnel':
                $nb = (int) ($params['nb_personnes'] ?? 0);
                $salaire = (int) ($params['salaire_moyen'] ?? 200000);
                $impactDepenses = -1 * $nb * $salaire * 12;
                break;
            case 'reduction_couts':
                $pct = (float) ($params['pourcentage'] ?? 0) / 100;
                $impactDepenses = (int) round(-1 * $depensesActuelles * $pct);
                break;
            case 'ajout_service':
                $impactRevenus = (int) ($params['revenu_annuel'] ?? 0);
                $impactDepenses = (int) ($params['cout_annuel'] ?? 0);
                break;
            case 'investissement':
                $impactDepenses = (int) ($params['cout_investissement'] ?? 0);
                $impactRevenus = (int) ($params['revenu_annuel_genere'] ?? 0);
                break;
            case 'scenario_libre':
                $impactRevenus = (int) ($params['impact_revenus'] ?? 0);
                $impactDepenses = (int) ($params['impact_depenses'] ?? 0);
                break;
        }

        return [$impactRevenus, $impactDepenses];
    }
}
