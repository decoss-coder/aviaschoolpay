<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Enseignant;
use App\Models\ExerciceComptable;
use App\Models\LigneBudgetaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::where('etablissement_id', $request->user()->etablissement_id)
            ->with('exercice')
            ->latest()
            ->get();

        return response()->json($budgets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'periodicite' => 'required|string|max:100',
            'total_prevu_revenus' => 'required|integer|min:0',
            'total_prevu_depenses' => 'required|integer|min:0',
            'statut' => 'nullable|in:brouillon,en_cours,termine',
        ]);

        $etabId = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etabId)->enCours()->firstOrFail();

        $budget = Budget::create([
            'etablissement_id' => $etabId,
            'exercice_id' => $exercice->id,
            'libelle' => $validated['libelle'],
            'periodicite' => $validated['periodicite'],
            'total_prevu_revenus' => $validated['total_prevu_revenus'],
            'total_prevu_depenses' => $validated['total_prevu_depenses'],
            'total_reel_revenus' => 0,
            'total_reel_depenses' => 0,
            'statut' => $validated['statut'] ?? 'brouillon',
            'cree_par' => $request->user()->id,
        ]);

        return response()->json($budget, 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        return response()->json($budget->load('lignes'));
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        $validated = $request->validate([
            'libelle' => 'sometimes|required|string|max:255',
            'periodicite' => 'sometimes|required|string|max:100',
            'total_prevu_revenus' => 'sometimes|required|integer|min:0',
            'total_prevu_depenses' => 'sometimes|required|integer|min:0',
            'total_reel_revenus' => 'sometimes|required|integer|min:0',
            'total_reel_depenses' => 'sometimes|required|integer|min:0',
            'statut' => 'sometimes|required|in:brouillon,en_cours,termine',
        ]);

        $budget->update($validated);
        $this->recalculerTotaux($budget);

        return response()->json($budget);
    }

    public function valider(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        $budget->update(['statut' => 'en_cours']);

        return response()->json($budget);
    }

    public function ajouterLigne(Request $request, Budget $budget): JsonResponse
    {
        $this->authorizeBudget($request, $budget);

        $validated = $request->validate([
            'categorie_depense_id' => 'nullable|exists:categories_depenses,id',
            'compte_comptable_numero' => 'nullable|string|max:50',
            'libelle' => 'required|string|max:255',
            'type' => 'required|in:revenu,depense',
            'service' => 'nullable|string|max:100',
            'mois' => 'nullable|string|size:7',
            'montant_prevu' => 'required|integer|min:0',
            'montant_reel' => 'nullable|integer|min:0',
            'seuil_alerte_pourcent' => 'nullable|numeric|min:0|max:100',
            'observations' => 'nullable|string',
        ]);

        $ligne = LigneBudgetaire::create([
            'budget_id' => $budget->id,
            'categorie_depense_id' => $validated['categorie_depense_id'] ?? null,
            'compte_comptable_numero' => $validated['compte_comptable_numero'] ?? null,
            'libelle' => $validated['libelle'],
            'type' => $validated['type'],
            'service' => $validated['service'] ?? null,
            'mois' => $validated['mois'] ?? null,
            'montant_prevu' => $validated['montant_prevu'],
            'montant_reel' => $validated['montant_reel'] ?? 0,
            'ecart' => ($validated['montant_reel'] ?? 0) - $validated['montant_prevu'],
            'taux_realisation' => $validated['montant_prevu'] > 0 ? round((($validated['montant_reel'] ?? 0) / $validated['montant_prevu']) * 100, 2) : 0,
            'alerte_depassement' => isset($validated['seuil_alerte_pourcent']) && $validated['montant_prevu'] > 0
                ? (($validated['montant_reel'] ?? 0) / $validated['montant_prevu']) * 100 >= $validated['seuil_alerte_pourcent']
                : false,
            'seuil_alerte_pourcent' => $validated['seuil_alerte_pourcent'] ?? 0,
            'observations' => $validated['observations'] ?? null,
        ]);

        $this->recalculerTotaux($budget);

        return response()->json($ligne, 201);
    }

    public function masseSalariale(Request $request): JsonResponse
    {
        $total = Enseignant::where('etablissement_id', $request->user()->etablissement_id)
            ->where('actif', true)
            ->sum('salaire_base');

        return response()->json(['total' => $total]);
    }

    private function authorizeBudget(Request $request, Budget $budget): void
    {
        abort_unless($budget->etablissement_id === $request->user()->etablissement_id, 403, 'Budget introuvable.');
    }

    private function recalculerTotaux(Budget $budget): void
    {
        $lignes = $budget->lignes;

        $budget->update([
            'total_prevu_revenus' => $lignes->where('type', 'revenu')->sum('montant_prevu'),
            'total_prevu_depenses' => $lignes->where('type', 'depense')->sum('montant_prevu'),
            'total_reel_revenus' => $lignes->where('type', 'revenu')->sum('montant_reel'),
            'total_reel_depenses' => $lignes->where('type', 'depense')->sum('montant_reel'),
        ]);
    }
}
