<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompteTresorerie;
use App\Models\ExerciceComptable;
use App\Models\MouvementTresorerie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TresorerieController extends Controller
{
    public function comptes(Request $request): JsonResponse
    {
        $comptes = CompteTresorerie::where('etablissement_id', $request->user()->etablissement_id)
            ->where('actif', true)
            ->get();

        return response()->json($comptes);
    }

    public function creerCompte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:banque,mobile_money,caisse,autre',
            'numero_compte' => 'nullable|string|max:100',
            'banque' => 'nullable|string|max:255',
            'operateur' => 'nullable|string|max:255',
            'solde_initial' => 'required|integer|min:0',
            'compte_comptable_numero' => 'nullable|string|max:50',
            'principal' => 'nullable|boolean',
        ]);

        $compte = CompteTresorerie::create([
            'etablissement_id' => $request->user()->etablissement_id,
            'nom' => $validated['nom'],
            'type' => $validated['type'],
            'numero_compte' => $validated['numero_compte'] ?? null,
            'banque' => $validated['banque'] ?? null,
            'operateur' => $validated['operateur'] ?? null,
            'solde_initial' => $validated['solde_initial'],
            'solde_actuel' => $validated['solde_initial'],
            'compte_comptable_numero' => $validated['compte_comptable_numero'] ?? null,
            'actif' => true,
            'principal' => $validated['principal'] ?? false,
        ]);

        return response()->json($compte, 201);
    }

    public function mouvements(Request $request, CompteTresorerie $compte): JsonResponse
    {
        $this->authorizeCompte($request, $compte);

        $mouvements = $compte->mouvements()->latest('date_mouvement')->paginate(25);

        return response()->json($mouvements);
    }

    public function soldesTempsReel(Request $request): JsonResponse
    {
        $comptes = CompteTresorerie::where('etablissement_id', $request->user()->etablissement_id)
            ->where('actif', true)
            ->get();

        return response()->json([
            'total' => $comptes->sum('solde_actuel'),
            'par_type' => $comptes->groupBy('type')->map(fn($items) => $items->sum('solde_actuel')),
        ]);
    }

    public function virement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compte_source_id' => 'required|exists:comptes_tresorerie,id',
            'compte_destination_id' => 'required|exists:comptes_tresorerie,id|different:compte_source_id',
            'montant' => 'required|integer|min:1',
            'libelle' => 'required|string|max:255',
            'date_mouvement' => 'nullable|date',
        ]);

        $source = CompteTresorerie::findOrFail($validated['compte_source_id']);
        $destination = CompteTresorerie::findOrFail($validated['compte_destination_id']);

        $this->authorizeCompte($request, $source);
        $this->authorizeCompte($request, $destination);

        DB::transaction(function () use ($validated, $source, $destination) {
            $date = $validated['date_mouvement'] ?? now()->toDateString();
            $libelle = $validated['libelle'];
            $montant = $validated['montant'];

            $source->mouvements()->create([
                'etablissement_id' => $source->etablissement_id,
                'sens' => 'sortie',
                'montant' => $montant,
                'solde_avant' => $source->solde_actuel,
                'solde_apres' => $source->solde_actuel - $montant,
                'date_mouvement' => $date,
                'libelle' => $libelle,
                'reference_type' => 'virement',
                'reference_id' => $destination->id,
                'saisie_par' => $request->user()->id,
            ]);

            $source->update(['solde_actuel' => $source->solde_actuel - $montant]);

            $destination->mouvements()->create([
                'etablissement_id' => $destination->etablissement_id,
                'sens' => 'entree',
                'montant' => $montant,
                'solde_avant' => $destination->solde_actuel,
                'solde_apres' => $destination->solde_actuel + $montant,
                'date_mouvement' => $date,
                'libelle' => $libelle,
                'reference_type' => 'virement',
                'reference_id' => $source->id,
                'saisie_par' => $request->user()->id,
            ]);

            $destination->update(['solde_actuel' => $destination->solde_actuel + $montant]);
        });

        return response()->json(['message' => 'Virement réalisé.']);
    }

    public function fluxEntreesSorties(Request $request): JsonResponse
    {
        $etabId = $request->user()->etablissement_id;
        $periodeDebut = now()->subMonths(6)->startOfMonth();

        $mouvements = MouvementTresorerie::where('etablissement_id', $etabId)
            ->whereBetween('date_mouvement', [$periodeDebut->toDateString(), now()->endOfMonth()->toDateString()])
            ->get();

        $parSens = $mouvements->groupBy('sens')->map(fn($items) => $items->sum('montant'));

        return response()->json([
            'entrees' => $parSens->get('entree', 0),
            'sorties' => $parSens->get('sortie', 0),
            'historique' => $mouvements->groupBy(fn($item) => $item->date_mouvement->format('Y-m'))->map(fn($items) => [
                'entrees' => $items->where('sens', 'entree')->sum('montant'),
                'sorties' => $items->where('sens', 'sortie')->sum('montant'),
            ]),
        ]);
    }

    public function previsions(Request $request): JsonResponse
    {
        $etabId = $request->user()->etablissement_id;
        $periodeDebut = now()->subMonths(6)->startOfMonth();

        $mouvements = MouvementTresorerie::where('etablissement_id', $etabId)
            ->whereBetween('date_mouvement', [$periodeDebut->toDateString(), now()->endOfMonth()->toDateString()])
            ->get();

        $parMois = $mouvements->groupBy(fn($item) => $item->date_mouvement->format('Y-m'))
            ->map(fn($items) => $items->where('sens', 'entree')->sum('montant') - $items->where('sens', 'sortie')->sum('montant'));

        $moyenne = $parMois->count() > 0 ? round($parMois->avg(), 2) : 0;
        $previsions = [];

        for ($i = 1; $i <= 3; $i++) {
            $date = now()->addMonths($i);
            $previsions[$date->format('Y-m')] = ['solde_net_prevu' => $moyenne];
        }

        return response()->json([
            'moyenne_netto' => $moyenne,
            'previsions' => $previsions,
        ]);
    }

    private function authorizeCompte(Request $request, CompteTresorerie $compte): void
    {
        abort_unless($compte->etablissement_id === $request->user()->etablissement_id, 403, 'Compte de trésorerie introuvable.');
    }
}
