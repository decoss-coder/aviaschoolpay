<?php
// ══════════════════════════════════════════════════════════════
// app/Http/Controllers/Api/DepenseController.php — MODULE 13
// ══════════════════════════════════════════════════════════════
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CategorieDepense, CompteComptable, CompteTresorerie, Depense, ExerciceComptable, EcritureComptable};
use Illuminate\Http\{JsonResponse, Request};

class DepenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $query = Depense::where('etablissement_id', $etab)->with(['categorie:id,nom,code,couleur', 'soumisePar:id,nom,prenom']);

        if ($request->filled('mois'))       $query->where('date_depense', 'like', "{$request->mois}%");
        if ($request->filled('categorie'))  $query->where('categorie_id', $request->categorie);
        if ($request->filled('statut'))     $query->where('statut', $request->statut);

        $depenses = $query->latest('date_depense')->paginate(25);
        $totalMois = Depense::where('etablissement_id', $etab)->approuvees()->mois(now()->format('Y-m'))->sum('montant');

        return response()->json(['depenses' => $depenses, 'total_mois' => $totalMois]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categorie_id' => 'required|exists:categories_depenses,id',
            'libelle' => 'required|string|max:300',
            'montant' => 'required|integer|min:1',
            'date_depense' => 'required|date',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money,carte',
            'beneficiaire' => 'nullable|string|max:200',
            'numero_facture' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'frequence' => 'nullable|in:ponctuelle,quotidienne,hebdomadaire,mensuelle,trimestrielle,annuelle',
        ]);

        $etab = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->firstOrFail();

        $depense = Depense::create([
            ...$validated,
            'etablissement_id' => $etab,
            'exercice_id' => $exercice->id,
            'reference' => Depense::genererReference($etab),
            'statut' => 'soumise',
            'soumise_par' => $request->user()->id,
        ]);

        return response()->json($depense->load('categorie'), 201);
    }

    public function approuver(Request $request, Depense $depense): JsonResponse
    {
        $depense->update([
            'statut' => 'approuvee',
            'approuvee_par' => $request->user()->id,
            'date_approbation' => now(),
        ]);

        // Mouvement de trésorerie
        $comptePrincipal = CompteTresorerie::where('etablissement_id', $depense->etablissement_id)->where('principal', true)->first();
        if ($comptePrincipal) {
            $comptePrincipal->enregistrerMouvement('sortie', $depense->montant, $depense->libelle, 'depense', $depense->id);
            if (! $depense->comptabilisee) {
                $this->comptabiliserDepense($depense, $comptePrincipal);
            }
        }

        return response()->json(['message' => 'Dépense approuvée.', 'depense' => $depense]);
    }

    private function comptabiliserDepense(Depense $depense, CompteTresorerie $compteTresorerie): void
    {
        $depense->loadMissing('categorie');

        $exercice = ExerciceComptable::where('etablissement_id', $depense->etablissement_id)->enCours()->first();
        if (! $exercice) {
            return;
        }

        $numeroDepense = $depense->categorie?->compte_comptable_numero ?: '604000';
        $compteDebit = CompteComptable::where('etablissement_id', $depense->etablissement_id)
            ->where('numero', $numeroDepense)
            ->first();

        $numeroTresorerie = $compteTresorerie->compte_comptable_numero ?: match ($compteTresorerie->type) {
            'banque' => '521000',
            'mobile_money' => '533000',
            default => '571000',
        };
        $compteCredit = CompteComptable::where('etablissement_id', $depense->etablissement_id)
            ->where('numero', $numeroTresorerie)
            ->first();

        if (! $compteDebit || ! $compteCredit) {
            return;
        }

        $ecriture = EcritureComptable::create([
            'etablissement_id' => $depense->etablissement_id,
            'exercice_id' => $exercice->id,
            'numero_piece' => EcritureComptable::genererNumero($depense->etablissement_id),
            'date_ecriture' => $depense->date_depense,
            'libelle' => 'Dépense approuvée - '.$depense->libelle,
            'compte_debit_id' => $compteDebit->id,
            'compte_credit_id' => $compteCredit->id,
            'montant' => $depense->montant,
            'type_piece' => 'depense',
            'reference_externe' => $depense->reference,
            'reference_type' => 'depense',
            'reference_id' => $depense->id,
            'saisie_par' => $depense->soumise_par,
            'valide_par' => $depense->approuvee_par,
            'valide' => true,
            'observations' => 'Comptabilisation automatique après approbation de dépense.',
        ]);

        $compteDebit->recalculerSolde();
        $compteCredit->recalculerSolde();

        $depense->update(['ecriture_id' => $ecriture->id, 'comptabilisee' => true]);
    }

    public function rejeter(Request $request, Depense $depense): JsonResponse
    {
        $request->validate(['motif' => 'required|string']);
        $depense->update(['statut' => 'rejetee', 'motif_rejet' => $request->motif]);
        return response()->json(['message' => 'Dépense rejetée.']);
    }

    public function show(Request $request, Depense $depense): JsonResponse
    {
        abort_unless($depense->etablissement_id === $request->user()->etablissement_id, 403);
        return response()->json($depense->load(['categorie', 'soumisePar:id,nom,prenom', 'approuveePar:id,nom,prenom']));
    }

    public function update(Request $request, Depense $depense): JsonResponse
    {
        abort_unless($depense->etablissement_id === $request->user()->etablissement_id, 403);
        abort_unless($depense->statut === 'soumise', 422, 'Seules les dépenses en statut soumise peuvent être modifiées.');

        $validated = $request->validate([
            'categorie_id'   => 'sometimes|exists:categories_depenses,id',
            'libelle'        => 'sometimes|string|max:300',
            'montant'        => 'sometimes|integer|min:1',
            'date_depense'   => 'sometimes|date',
            'mode_paiement'  => 'sometimes|in:especes,cheque,virement,mobile_money,carte',
            'beneficiaire'   => 'nullable|string|max:200',
            'numero_facture' => 'nullable|string|max:50',
            'description'    => 'nullable|string',
        ]);

        $depense->update($validated);
        return response()->json($depense->load('categorie'));
    }

    public function parCategorie(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $mois = $request->get('mois', now()->format('Y-m'));

        $stats = CategorieDepense::where('etablissement_id', $etab)->where('active', true)->get()
            ->map(fn($cat) => [
                'categorie' => $cat->nom,
                'code'      => $cat->code,
                'type'      => $cat->type,
                'couleur'   => $cat->couleur,
                'total'     => $cat->totalMois($mois),
            ])->sortByDesc('total')->values();

        return response()->json(['mois' => $mois, 'categories' => $stats, 'total' => $stats->sum('total')]);
    }

    public function parMois(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $mois = $request->get('mois', now()->format('Y-m'));

        // 12 derniers mois
        $debut = now()->subMonths(11)->startOfMonth()->toDateString();

        $depensesParMois = Depense::where('etablissement_id', $etab)
            ->where('statut', 'approuvee')
            ->where('date_depense', '>=', $debut)
            ->get()
            ->groupBy(fn($d) => substr($d->date_depense, 0, 7))
            ->map(fn($items) => [
                'total'     => $items->sum('montant'),
                'nombre'    => $items->count(),
                'par_categorie' => $items->groupBy('categorie_id')->map(fn($g) => $g->sum('montant')),
            ]);

        return response()->json(['historique' => $depensesParMois]);
    }

    public function recurrentes(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $recurrentes = Depense::where('etablissement_id', $etab)
            ->whereNotNull('frequence')
            ->where('frequence', '!=', 'ponctuelle')
            ->with('categorie:id,nom,code,couleur')
            ->latest('date_depense')
            ->get()
            ->groupBy('libelle')
            ->map(fn($groupe) => [
                'libelle'    => $groupe->first()->libelle,
                'frequence'  => $groupe->first()->frequence,
                'montant'    => $groupe->first()->montant,
                'categorie'  => $groupe->first()->categorie,
                'occurrences' => $groupe->count(),
                'total_cumule' => $groupe->sum('montant'),
                'derniere'   => $groupe->first()->date_depense,
            ])->values();

        return response()->json(['recurrentes' => $recurrentes, 'nombre' => $recurrentes->count()]);
    }
}
