<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\CategorieDepense;
use App\Models\ExerciceComptable;
use App\Models\LigneBudgetaire;
use App\Services\Budget\BudgetAlimentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $budgets = Budget::where('etablissement_id', $etab)
            ->with(['exercice', 'creePar:id,nom,prenom', 'validePar:id,nom,prenom'])
            ->withCount('lignes')
            ->latest()
            ->get();

        $totalActifs       = $budgets->whereIn('statut', ['valide', 'en_cours'])->count();
        $totalPrevuRevenus = $budgets->whereIn('statut', ['valide', 'en_cours'])->sum('total_prevu_revenus');
        $totalPrevuDepenses = $budgets->whereIn('statut', ['valide', 'en_cours'])->sum('total_prevu_depenses');
        $totalReelRevenus  = $budgets->whereIn('statut', ['valide', 'en_cours'])->sum('total_reel_revenus');
        $totalReelDepenses = $budgets->whereIn('statut', ['valide', 'en_cours'])->sum('total_reel_depenses');

        return view('budgets.index', compact(
            'budgets', 'totalActifs', 'totalPrevuRevenus', 'totalPrevuDepenses',
            'totalReelRevenus', 'totalReelDepenses'
        ));
    }

    public function create(Request $request)
    {
        $etab = $request->user()->etablissement_id;
        $exercices = ExerciceComptable::where('etablissement_id', $etab)
            ->orderByDesc('date_debut')->get();

        return view('budgets.create', compact('exercices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle'              => 'required|string|max:200',
            'periodicite'          => 'required|in:mensuel,trimestriel,annuel',
            'total_prevu_revenus'  => 'required|integer|min:0',
            'total_prevu_depenses' => 'required|integer|min:0',
            'statut'               => 'nullable|in:brouillon,valide,en_cours',
        ]);

        $etab = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->firstOrFail();

        $budget = Budget::create([
            'etablissement_id'     => $etab,
            'exercice_id'          => $exercice->id,
            'libelle'              => $validated['libelle'],
            'periodicite'          => $validated['periodicite'],
            'total_prevu_revenus'  => $validated['total_prevu_revenus'],
            'total_prevu_depenses' => $validated['total_prevu_depenses'],
            'total_reel_revenus'   => 0,
            'total_reel_depenses'  => 0,
            'statut'               => $validated['statut'] ?? 'brouillon',
            'cree_par'             => $request->user()->id,
        ]);

        return redirect()->route('budgets.show', $budget->id)
            ->with('success', "Budget « {$budget->libelle} » créé.");
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $budget = Budget::with(['lignes.categorieDepense', 'exercice', 'creePar:id,nom,prenom', 'validePar:id,nom,prenom'])
            ->where('etablissement_id', $etab)
            ->findOrFail($id);

        $categories = CategorieDepense::where('etablissement_id', $etab)->where('active', true)->orderBy('nom')->get();

        $lignesRevenus = $budget->lignes->where('type', 'revenu')->values();
        $lignesDepenses = $budget->lignes->where('type', 'depense')->values();

        return view('budgets.show', compact('budget', 'categories', 'lignesRevenus', 'lignesDepenses'));
    }

    public function valider(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $budget = Budget::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless($budget->statut === 'brouillon', 422);

        $budget->update([
            'statut'     => 'en_cours',
            'valide_par' => $request->user()->id,
        ]);

        return redirect()->route('budgets.show', $budget->id)->with('success', 'Budget validé et activé.');
    }

    public function recalculer(Request $request, BudgetAlimentationService $service, $id)
    {
        $etab = $request->user()->etablissement_id;
        $budget = Budget::where('etablissement_id', $etab)->findOrFail($id);

        $r = $service->recalculerBudget($budget);

        return redirect()->route('budgets.show', $budget->id)
            ->with('success', "Réel recalculé : {$r['paiements']} ligne(s) revenus + {$r['depenses']} ligne(s) dépenses alimentées.");
    }

    public function cloturer(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $budget = Budget::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless(in_array($budget->statut, ['en_cours', 'valide']), 422);

        $budget->update(['statut' => 'cloture']);

        return redirect()->route('budgets.show', $budget->id)->with('success', 'Budget clôturé.');
    }

    public function ajouterLigne(Request $request, $id)
    {
        $validated = $request->validate([
            'libelle'              => 'required|string|max:200',
            'type'                 => 'required|in:revenu,depense',
            'service'              => 'required|in:scolarite,cantine,transport,activites,salaires,fonctionnement,investissement,autre',
            'montant_prevu'        => 'required|integer|min:0',
            'categorie_depense_id' => 'nullable|exists:categories_depenses,id',
            'compte_comptable_numero' => 'nullable|string|max:20',
            'seuil_alerte_pourcent' => 'nullable|integer|min:1|max:200',
            'observations'         => 'nullable|string',
        ]);

        $etab = $request->user()->etablissement_id;
        $budget = Budget::where('etablissement_id', $etab)->findOrFail($id);
        abort_if($budget->statut === 'cloture', 422, 'Budget clôturé.');

        DB::transaction(function () use ($budget, $validated) {
            LigneBudgetaire::create([
                'budget_id'            => $budget->id,
                'libelle'              => $validated['libelle'],
                'type'                 => $validated['type'],
                'service'              => $validated['service'],
                'montant_prevu'        => $validated['montant_prevu'],
                'montant_reel'         => 0,
                'ecart'                => -$validated['montant_prevu'],
                'taux_realisation'     => 0,
                'categorie_depense_id' => $validated['categorie_depense_id'] ?? null,
                'compte_comptable_numero' => $validated['compte_comptable_numero'] ?? null,
                'seuil_alerte_pourcent' => $validated['seuil_alerte_pourcent'] ?? 90,
                'observations'         => $validated['observations'] ?? null,
            ]);

            // Recalculer les totaux du budget
            $this->recalculerTotaux($budget);
        });

        return redirect()->route('budgets.show', $budget->id)->with('success', 'Ligne ajoutée.');
    }

    public function supprimerLigne(Request $request, $id, $ligneId)
    {
        $etab = $request->user()->etablissement_id;
        $budget = Budget::where('etablissement_id', $etab)->findOrFail($id);
        abort_if($budget->statut === 'cloture', 422);

        $ligne = LigneBudgetaire::where('budget_id', $budget->id)->findOrFail($ligneId);
        $ligne->delete();
        $this->recalculerTotaux($budget);

        return redirect()->route('budgets.show', $budget->id)->with('success', 'Ligne supprimée.');
    }

    private function recalculerTotaux(Budget $budget): void
    {
        $budget->loadMissing('lignes');
        $budget->update([
            'total_prevu_revenus'  => $budget->lignes->where('type', 'revenu')->sum('montant_prevu'),
            'total_prevu_depenses' => $budget->lignes->where('type', 'depense')->sum('montant_prevu'),
            'total_reel_revenus'   => $budget->lignes->where('type', 'revenu')->sum('montant_reel'),
            'total_reel_depenses'  => $budget->lignes->where('type', 'depense')->sum('montant_reel'),
        ]);
    }
}
