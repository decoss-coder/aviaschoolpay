<?php

namespace App\Http\Controllers;

use App\Models\AlerteFinanciere;
use App\Models\CompteTresorerie;
use App\Models\Depense;
use App\Models\Inscription;
use App\Models\MouvementTresorerie;
use App\Models\Paiement;
use App\Models\ScoreFinancier;
use App\Models\SnapshotFinancier;
use App\Services\Rentabilite\RentabiliteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CockpitWebController extends Controller
{
    public function __construct(private RentabiliteService $rentabilite) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $id = $etab->id;

        $comptes = CompteTresorerie::where('etablissement_id', $id)->where('actif', true)->get();
        $soldeCaisse = (int) $comptes->where('type', 'caisse')->sum('solde_actuel');
        $soldeBanque = (int) $comptes->where('type', 'banque')->sum('solde_actuel');
        $soldeMM     = (int) $comptes->where('type', 'mobile_money')->sum('solde_actuel');
        $tresoTotale = $soldeCaisse + $soldeBanque + $soldeMM;

        $moisCourant = now()->format('Y-m');
        $revenusMois = (int) Paiement::where('etablissement_id', $id)
            ->where('statut', 'confirme')
            ->where('date_paiement', 'like', "$moisCourant%")
            ->sum('montant');
        $depensesMois = (int) Depense::where('etablissement_id', $id)->approuvees()->mois($moisCourant)->sum('montant');

        $score = ScoreFinancier::where('etablissement_id', $id)->latest('date_calcul')->first();
        $alertesCritiques = AlerteFinanciere::where('etablissement_id', $id)->where('traitee', false)->where('gravite', 'critique')->count();
        $alertesWarning   = AlerteFinanciere::where('etablissement_id', $id)->where('traitee', false)->where('gravite', 'warning')->count();
        $alertesRecentes  = AlerteFinanciere::where('etablissement_id', $id)
            ->where('traitee', false)
            ->orderByRaw("FIELD(gravite, 'critique', 'warning', 'info')")
            ->latest()->take(5)->get();

        $synthRenta = $this->rentabilite->syntheseGlobale($etab);

        $evolution = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $snap = SnapshotFinancier::where('etablissement_id', $id)->where('date_snapshot', $date)->first();
            $entrees = (int) MouvementTresorerie::where('etablissement_id', $id)->where('sens', 'entree')->where('date_mouvement', $date)->sum('montant');
            $sorties = (int) MouvementTresorerie::where('etablissement_id', $id)->where('sens', 'sortie')->where('date_mouvement', $date)->sum('montant');
            $evolution[] = [
                'date' => Carbon::parse($date)->format('d/m'),
                'treso' => (int) ($snap?->tresorerie_totale ?? 0),
                'entrees' => $entrees,
                'sorties' => $sorties,
            ];
        }

        // Impayés : inscriptions dont la somme des paiements confirmés < montant_net
        $impayes = Inscription::where('etablissement_id', $id)
            ->where('statut', 'validee')
            ->whereRaw('montant_net > COALESCE((SELECT SUM(montant) FROM paiements WHERE paiements.inscription_id = inscriptions.id AND paiements.statut = ?), 0)', ['confirme'])
            ->count();

        return view('cockpit.index', compact(
            'etab', 'comptes', 'soldeCaisse', 'soldeBanque', 'soldeMM', 'tresoTotale',
            'revenusMois', 'depensesMois', 'score', 'alertesCritiques', 'alertesWarning',
            'alertesRecentes', 'synthRenta', 'evolution', 'impayes'
        ));
    }

    public function score(Request $request)
    {
        $etab = $request->user()->etablissement;
        $dernierScore = ScoreFinancier::where('etablissement_id', $etab->id)->latest('date_calcul')->first();
        $historique = ScoreFinancier::where('etablissement_id', $etab->id)
            ->orderByDesc('date_calcul')
            ->take(12)->get()
            ->sortBy('date_calcul')->values();

        return view('cockpit.score', compact('dernierScore', 'historique', 'etab'));
    }

    public function recalculerScore(Request $request)
    {
        $etab = $request->user()->etablissement;
        $score = ScoreFinancier::calculerPourEtablissement($etab);
        return redirect()->route('cockpit.score')->with('success', "Score recalculé : {$score->score_global}/100 ({$score->indicateur}).");
    }

    public function alertes(Request $request)
    {
        $etab = $request->user()->etablissement;
        $query = AlerteFinanciere::where('etablissement_id', $etab->id);

        if ($request->filled('statut')) {
            $query->where('traitee', $request->statut === 'traitees');
        }
        if ($request->filled('gravite')) {
            $query->where('gravite', $request->gravite);
        }

        $alertes = $query->orderByRaw("FIELD(gravite, 'critique', 'warning', 'info')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'critiques' => AlerteFinanciere::where('etablissement_id', $etab->id)->where('traitee', false)->where('gravite', 'critique')->count(),
            'warnings'  => AlerteFinanciere::where('etablissement_id', $etab->id)->where('traitee', false)->where('gravite', 'warning')->count(),
            'infos'     => AlerteFinanciere::where('etablissement_id', $etab->id)->where('traitee', false)->where('gravite', 'info')->count(),
            'traitees'  => AlerteFinanciere::where('etablissement_id', $etab->id)->where('traitee', true)->count(),
        ];

        return view('cockpit.alertes', compact('alertes', 'stats'));
    }

    public function traiterAlerte(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $alerte = AlerteFinanciere::where('etablissement_id', $etab)->findOrFail($id);
        $alerte->update([
            'traitee' => true,
            'traitee_par' => $request->user()->id,
            'action_prise' => $request->input('action_prise'),
        ]);
        return back()->with('success', 'Alerte traitée.');
    }
}
