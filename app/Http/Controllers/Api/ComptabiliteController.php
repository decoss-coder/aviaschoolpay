<?php
// ══════════════════════════════════════════════════════════════
// app/Http/Controllers/Api/ComptabiliteController.php
// MODULE 12 — COMPTABILITÉ SCOLAIRE
// ══════════════════════════════════════════════════════════════
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CompteComptable, ExerciceComptable, EcritureComptable};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class ComptabiliteController extends Controller
{
    public function planComptable(Request $request): JsonResponse
    {
        $comptes = CompteComptable::where('etablissement_id', $request->user()->etablissement_id)
            ->where('actif', true)->orderBy('numero')->get()
            ->groupBy('type');
        return response()->json($comptes);
    }

    public function journal(Request $request): JsonResponse
    {
        $request->validate(['mois' => 'nullable|string|size:7']);
        $mois = $request->get('mois', now()->format('Y-m'));
        $etab = $request->user()->etablissement_id;

        $ecritures = EcritureComptable::where('etablissement_id', $etab)
            ->where('date_ecriture', 'like', "$mois%")
            ->with(['compteDebit:id,numero,libelle', 'compteCredit:id,numero,libelle', 'saisiePar:id,nom,prenom'])
            ->orderBy('date_ecriture')->orderBy('id')->get();

        $totalDebit = $ecritures->sum('montant');

        return response()->json([
            'mois' => $mois,
            'ecritures' => $ecritures,
            'total_debit' => $totalDebit,
            'total_credit' => $totalDebit,
            'nombre_ecritures' => $ecritures->count(),
        ]);
    }

    public function grandLivre(Request $request, CompteComptable $compte): JsonResponse
    {
        $ecritures = EcritureComptable::where('etablissement_id', $request->user()->etablissement_id)
            ->where(fn($q) => $q->where('compte_debit_id', $compte->id)->orWhere('compte_credit_id', $compte->id))
            ->where('valide', true)
            ->orderBy('date_ecriture')->get()
            ->map(function ($e) use ($compte) {
                $e->sens = $e->compte_debit_id === $compte->id ? 'debit' : 'credit';
                return $e;
            });

        return response()->json(['compte' => $compte, 'ecritures' => $ecritures, 'solde' => $compte->solde_actuel]);
    }

    public function bilan(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $comptes = CompteComptable::where('etablissement_id', $etab)->where('actif', true)->get();

        $actifs = $comptes->where('type', 'actif');
        $passifs = $comptes->where('type', 'passif');
        $tresorerie = $comptes->where('type', 'tresorerie');

        return response()->json([
            'actif' => ['comptes' => $actifs, 'total' => $actifs->sum('solde_actuel') + $tresorerie->where('categorie', '!=', 'dettes')->sum('solde_actuel')],
            'passif' => ['comptes' => $passifs, 'total' => $passifs->sum('solde_actuel')],
            'tresorerie' => ['comptes' => $tresorerie, 'total' => $tresorerie->sum('solde_actuel')],
            'date' => now()->format('d/m/Y'),
        ]);
    }

    public function compteResultat(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;
        $comptes = CompteComptable::where('etablissement_id', $etab)->where('actif', true)->get();

        $produits = $comptes->where('type', 'produit');
        $charges  = $comptes->where('type', 'charge');
        $totalProduits = $produits->sum('solde_actuel');
        $totalCharges  = $charges->sum('solde_actuel');

        return response()->json([
            'produits'    => ['detail' => $produits->groupBy('categorie'), 'total' => $totalProduits],
            'charges'     => ['detail' => $charges->groupBy('categorie'),  'total' => $totalCharges],
            'resultat_net' => $totalProduits - $totalCharges,
            'beneficiaire' => $totalProduits > $totalCharges,
        ]);
    }

    public function creerCompte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero'    => 'required|string|max:20',
            'libelle'   => 'required|string|max:255',
            'type'      => 'required|in:actif,passif,charge,produit,tresorerie',
            'categorie' => 'nullable|string|max:100',
        ]);

        $etab = $request->user()->etablissement_id;

        $compte = CompteComptable::create([
            'etablissement_id' => $etab,
            'numero'    => $validated['numero'],
            'libelle'   => $validated['libelle'],
            'type'      => $validated['type'],
            'categorie' => $validated['categorie'] ?? null,
            'actif'     => true,
        ]);

        return response()->json($compte, 201);
    }

    public function creerEcriture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_ecriture'    => 'required|date',
            'libelle'          => 'required|string|max:255',
            'compte_debit_id'  => 'required|exists:comptes_comptables,id',
            'compte_credit_id' => 'required|exists:comptes_comptables,id|different:compte_debit_id',
            'montant'          => 'required|integer|min:1',
            'type_piece'       => 'nullable|string|max:50',
            'observations'     => 'nullable|string',
        ]);

        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->firstOrFail();

        $ecriture = DB::transaction(function () use ($validated, $etab, $exercice, $request) {
            $ecriture = EcritureComptable::create([
                'etablissement_id' => $etab,
                'exercice_id'      => $exercice->id,
                'numero_piece'     => EcritureComptable::genererNumero($etab),
                'date_ecriture'    => $validated['date_ecriture'],
                'libelle'          => $validated['libelle'],
                'compte_debit_id'  => $validated['compte_debit_id'],
                'compte_credit_id' => $validated['compte_credit_id'],
                'montant'          => $validated['montant'],
                'type_piece'       => $validated['type_piece'] ?? 'manuel',
                'observations'     => $validated['observations'] ?? null,
                'saisie_par'       => $request->user()->id,
                'valide'           => false,
            ]);

            CompteComptable::find($validated['compte_debit_id'])->recalculerSolde();
            CompteComptable::find($validated['compte_credit_id'])->recalculerSolde();

            return $ecriture->load(['compteDebit:id,numero,libelle', 'compteCredit:id,numero,libelle']);
        });

        return response()->json($ecriture, 201);
    }

    public function exercices(Request $request): JsonResponse
    {
        $exercices = ExerciceComptable::where('etablissement_id', $request->user()->etablissement_id)
            ->orderByDesc('date_debut')
            ->get();
        return response()->json($exercices);
    }

    public function creerExercice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'libelle'           => 'required|string|max:100',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'date_debut'        => 'required|date',
            'date_fin'          => 'required|date|after:date_debut',
        ]);

        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::create([
            'etablissement_id'  => $etab,
            'annee_scolaire_id' => $validated['annee_scolaire_id'],
            'libelle'           => $validated['libelle'],
            'date_debut'        => $validated['date_debut'],
            'date_fin'          => $validated['date_fin'],
            'en_cours'          => false,
            'cloture'           => false,
        ]);

        return response()->json($exercice, 201);
    }

    public function cloturerExercice(Request $request, ExerciceComptable $exercice): JsonResponse
    {
        abort_unless($exercice->etablissement_id === $request->user()->etablissement_id, 403);
        abort_if($exercice->cloture, 409, 'Exercice déjà clôturé.');

        $exercice->update([
            'cloture' => true,
            'en_cours' => false,
        ]);

        return response()->json(['message' => "Exercice « {$exercice->libelle} » clôturé.", 'exercice' => $exercice]);
    }
}
