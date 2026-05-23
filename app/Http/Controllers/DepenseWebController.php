<?php

namespace App\Http\Controllers;

use App\Models\CategorieDepense;
use App\Models\CompteComptable;
use App\Models\CompteTresorerie;
use App\Models\Depense;
use App\Models\EcritureComptable;
use App\Models\ExerciceComptable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepenseWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $query = Depense::where('etablissement_id', $etab)
            ->with(['categorie:id,nom,couleur,code', 'soumisePar:id,nom,prenom', 'approuveePar:id,nom,prenom']);

        if ($request->filled('statut'))      $query->where('statut', $request->statut);
        if ($request->filled('categorie'))   $query->where('categorie_id', $request->categorie);
        if ($request->filled('mois'))        $query->where('date_depense', 'like', $request->mois.'%');
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('libelle', 'like', "%$q%")
                  ->orWhere('reference', 'like', "%$q%")
                  ->orWhere('beneficiaire', 'like', "%$q%");
            });
        }

        $depenses = $query->latest('date_depense')->paginate(20)->withQueryString();

        $moisCourant = now()->format('Y-m');
        $totalMois     = Depense::where('etablissement_id', $etab)->where('statut', 'approuvee')->where('date_depense', 'like', "$moisCourant%")->sum('montant');
        $totalAnnee    = Depense::where('etablissement_id', $etab)->where('statut', 'approuvee')->whereYear('date_depense', now()->year)->sum('montant');
        $enAttente     = Depense::where('etablissement_id', $etab)->where('statut', 'soumise')->count();
        $approuvees    = Depense::where('etablissement_id', $etab)->where('statut', 'approuvee')->count();
        $rejetees      = Depense::where('etablissement_id', $etab)->where('statut', 'rejetee')->count();
        $montantAttente = Depense::where('etablissement_id', $etab)->where('statut', 'soumise')->sum('montant');

        $categories = CategorieDepense::where('etablissement_id', $etab)->where('active', true)->orderBy('nom')->get();

        // Top categories du mois
        $topCategories = Depense::where('etablissement_id', $etab)
            ->where('statut', 'approuvee')
            ->where('date_depense', 'like', "$moisCourant%")
            ->select('categorie_id', DB::raw('SUM(montant) as total'))
            ->groupBy('categorie_id')
            ->orderByDesc('total')
            ->with('categorie:id,nom,couleur')
            ->limit(5)
            ->get();

        return view('depenses.index', compact(
            'depenses', 'totalMois', 'totalAnnee', 'enAttente', 'approuvees', 'rejetees',
            'montantAttente', 'categories', 'topCategories'
        ));
    }

    public function create(Request $request)
    {
        $etab = $request->user()->etablissement_id;
        $categories = CategorieDepense::where('etablissement_id', $etab)->where('active', true)->orderBy('nom')->get();

        return view('depenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categorie_id'   => 'required|exists:categories_depenses,id',
            'libelle'        => 'required|string|max:300',
            'montant'        => 'required|integer|min:1',
            'date_depense'   => 'required|date',
            'mode_paiement'  => 'required|in:especes,cheque,virement,mobile_money,carte',
            'beneficiaire'   => 'nullable|string|max:200',
            'numero_facture' => 'nullable|string|max:50',
            'frequence'      => 'nullable|in:ponctuelle,quotidienne,hebdomadaire,mensuelle,trimestrielle,annuelle',
            'description'    => 'nullable|string',
        ]);

        $etab = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->firstOrFail();

        $depense = Depense::create([
            ...$validated,
            'etablissement_id' => $etab,
            'exercice_id'      => $exercice->id,
            'reference'        => Depense::genererReference($etab),
            'statut'           => 'soumise',
            'frequence'        => $validated['frequence'] ?? 'ponctuelle',
            'soumise_par'      => $request->user()->id,
        ]);

        return redirect()->route('depenses.show', $depense->id)
            ->with('success', "Dépense {$depense->reference} créée et soumise pour validation.");
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $depense = Depense::with(['categorie', 'soumisePar:id,nom,prenom', 'approuveePar:id,nom,prenom', 'exercice:id,libelle', 'ecriture'])
            ->where('etablissement_id', $etab)
            ->findOrFail($id);

        return view('depenses.show', compact('depense'));
    }

    public function approuver(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $depense = Depense::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless($depense->statut === 'soumise', 422, 'Seules les dépenses soumises peuvent être approuvées.');

        DB::transaction(function () use ($depense, $request) {
            $depense->update([
                'statut'           => 'approuvee',
                'approuvee_par'    => $request->user()->id,
                'date_approbation' => now(),
            ]);

            $comptePrincipal = CompteTresorerie::where('etablissement_id', $depense->etablissement_id)
                ->where('principal', true)
                ->first();

            if ($comptePrincipal) {
                $comptePrincipal->enregistrerMouvement('sortie', $depense->montant, $depense->libelle, 'depense', $depense->id);
                if (! $depense->comptabilisee) {
                    $this->comptabiliserDepense($depense, $comptePrincipal);
                }
            }
        });

        return redirect()->route('depenses.show', $depense->id)
            ->with('success', "Dépense {$depense->reference} approuvée et comptabilisée.");
    }

    public function rejeter(Request $request, $id)
    {
        $validated = $request->validate(['motif_rejet' => 'required|string|max:500']);
        $etab = $request->user()->etablissement_id;
        $depense = Depense::where('etablissement_id', $etab)->findOrFail($id);
        abort_unless($depense->statut === 'soumise', 422);

        $depense->update([
            'statut'      => 'rejetee',
            'motif_rejet' => $validated['motif_rejet'],
            'approuvee_par' => $request->user()->id,
            'date_approbation' => now(),
        ]);

        return redirect()->route('depenses.show', $depense->id)
            ->with('error', "Dépense {$depense->reference} rejetée.");
    }

    private function comptabiliserDepense(Depense $depense, CompteTresorerie $compteTresorerie): void
    {
        $depense->loadMissing('categorie');

        $exercice = ExerciceComptable::where('etablissement_id', $depense->etablissement_id)->enCours()->first();
        if (! $exercice) return;

        $numeroDepense = $depense->categorie?->compte_comptable_numero ?: '604000';
        $compteDebit = CompteComptable::where('etablissement_id', $depense->etablissement_id)
            ->where('numero', $numeroDepense)->first();

        $numeroTresorerie = $compteTresorerie->compte_comptable_numero ?: match ($compteTresorerie->type) {
            'banque' => '521000', 'mobile_money' => '533000', default => '571000',
        };
        $compteCredit = CompteComptable::where('etablissement_id', $depense->etablissement_id)
            ->where('numero', $numeroTresorerie)->first();

        if (! $compteDebit || ! $compteCredit) return;

        $ecriture = EcritureComptable::create([
            'etablissement_id' => $depense->etablissement_id,
            'exercice_id'      => $exercice->id,
            'numero_piece'     => EcritureComptable::genererNumero($depense->etablissement_id),
            'date_ecriture'    => $depense->date_depense,
            'libelle'          => 'Dépense - '.$depense->libelle,
            'compte_debit_id'  => $compteDebit->id,
            'compte_credit_id' => $compteCredit->id,
            'montant'          => $depense->montant,
            'type_piece'       => 'depense',
            'reference_externe' => $depense->reference,
            'reference_type'   => 'depense',
            'reference_id'     => $depense->id,
            'saisie_par'       => $depense->soumise_par,
            'valide_par'       => $depense->approuvee_par,
            'valide'           => true,
            'observations'     => 'Comptabilisation automatique après approbation.',
        ]);

        $compteDebit->recalculerSolde();
        $compteCredit->recalculerSolde();

        $depense->update(['ecriture_id' => $ecriture->id, 'comptabilisee' => true]);
    }

    // ─── Catégories ────────────────────────────────────────────
    public function categories(Request $request)
    {
        $etab = $request->user()->etablissement_id;
        $categories = CategorieDepense::where('etablissement_id', $etab)
            ->withCount(['depenses' => fn($q) => $q->where('statut', 'approuvee')])
            ->orderBy('nom')->get();

        return view('depenses.categories', compact('categories'));
    }

    public function categoriesStore(Request $request)
    {
        $validated = $request->validate([
            'nom'                     => 'required|string|max:100',
            'code'                    => 'required|string|max:20',
            'type'                    => 'required|in:fixe,variable,exceptionnelle',
            'couleur'                 => 'nullable|string|max:7',
            'compte_comptable_numero' => 'nullable|string|max:20',
        ]);

        CategorieDepense::create([
            ...$validated,
            'etablissement_id' => $request->user()->etablissement_id,
            'active'           => true,
        ]);

        return redirect()->route('depenses.categories')->with('success', 'Catégorie créée.');
    }

    public function categoriesDestroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $cat = CategorieDepense::where('etablissement_id', $etab)->findOrFail($id);
        $cat->update(['active' => false]);

        return redirect()->route('depenses.categories')->with('success', 'Catégorie désactivée.');
    }
}
