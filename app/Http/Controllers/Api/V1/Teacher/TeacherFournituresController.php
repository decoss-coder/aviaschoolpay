<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\FournitureItem;
use App\Models\ListeFournitures;
use App\Services\Scolarite\AnneeScolaireService;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherFournituresController extends Controller
{
    /**
     * Liste des classes où l'enseignant connecté peut éditer les fournitures.
     * (Toutes ses classes affectées, ou pour les PP : leur classe principale)
     */
    public function mesClasses(Request $request): JsonResponse
    {
        $user = $request->user();
        $enseignant = $user->enseignants()->where('actif', true)->first();
        abort_unless($enseignant, 403, 'Aucun profil enseignant actif.');

        $annee = AnneeScolaireService::couranteOuEchec($enseignant->etablissement_id);

        $classes = $enseignant->affectations()
            ->where('active', true)
            ->where('annee_scolaire_id', $annee->id)
            ->with('classe:id,nom,niveau_id')
            ->get()
            ->pluck('classe')
            ->unique('id')
            ->filter()
            ->map(function ($c) use ($annee) {
                $liste = ListeFournitures::where('classe_id', $c->id)
                    ->where('annee_scolaire_id', $annee->id)->first();
                return [
                    'id'      => $c->id,
                    'nom'     => $c->nom,
                    'liste'   => $liste ? [
                        'id'      => $liste->id,
                        'titre'   => $liste->titre,
                        'publie'  => (bool) $liste->publie,
                        'nb_items' => $liste->items()->count(),
                    ] : null,
                ];
            })->values();

        return ApiEnvelope::ok(['classes' => $classes]);
    }

    public function indexListe(Request $request, int $classeId): JsonResponse
    {
        $enseignant = $request->user()->enseignants()->where('actif', true)->first();
        abort_unless($enseignant, 403);
        $annee = AnneeScolaireService::couranteOuEchec($enseignant->etablissement_id);

        // Vérifier qu'il a une affectation à cette classe
        $autorise = $enseignant->affectations()
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('active', true)->exists();
        abort_unless($autorise, 403, 'Non autorisé pour cette classe.');

        $liste = ListeFournitures::with('items')
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $annee->id)
            ->first();

        return ApiEnvelope::ok(['liste' => $liste]);
    }

    public function creerOuMaj(Request $request, int $classeId): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        $enseignant = $request->user()->enseignants()->where('actif', true)->first();
        abort_unless($enseignant, 403);
        $annee = AnneeScolaireService::couranteOuEchec($enseignant->etablissement_id);

        $autorise = $enseignant->affectations()
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('active', true)->exists();
        abort_unless($autorise, 403);

        $liste = ListeFournitures::firstOrCreate(
            ['classe_id' => $classeId, 'annee_scolaire_id' => $annee->id],
            [
                'etablissement_id' => $enseignant->etablissement_id,
                'titre'            => $validated['titre'] ?? 'Liste de fournitures',
                'notes'            => $validated['notes'] ?? null,
                'publie'           => false,
                'cree_par'         => $request->user()->id,
            ]
        );

        if (isset($validated['titre'])) $liste->titre = $validated['titre'];
        if (array_key_exists('notes', $validated)) $liste->notes = $validated['notes'];
        $liste->save();

        return ApiEnvelope::ok(['liste' => $liste->fresh()]);
    }

    public function ajouterItem(Request $request, int $listeId): JsonResponse
    {
        $validated = $request->validate([
            'libelle'         => 'required|string|max:200',
            'categorie'       => 'nullable|string|max:60',
            'quantite'        => 'required|integer|min:1|max:1000',
            'unite'           => 'nullable|string|max:20',
            'marque_suggeree' => 'nullable|string|max:100',
            'obligatoire'     => 'nullable|boolean',
            'observations'    => 'nullable|string',
        ]);

        $enseignant = $request->user()->enseignants()->where('actif', true)->first();
        abort_unless($enseignant, 403);
        $liste = ListeFournitures::where('etablissement_id', $enseignant->etablissement_id)->findOrFail($listeId);

        $item = FournitureItem::create([
            ...$validated,
            'liste_id'    => $liste->id,
            'obligatoire' => $validated['obligatoire'] ?? true,
            'ordre'       => FournitureItem::where('liste_id', $liste->id)->max('ordre') + 1,
        ]);

        return ApiEnvelope::ok(['item' => $item], 'Fourniture ajoutée.', 201);
    }

    public function supprimerItem(Request $request, int $listeId, int $itemId): JsonResponse
    {
        $enseignant = $request->user()->enseignants()->where('actif', true)->first();
        abort_unless($enseignant, 403);
        $liste = ListeFournitures::where('etablissement_id', $enseignant->etablissement_id)->findOrFail($listeId);
        FournitureItem::where('liste_id', $liste->id)->findOrFail($itemId)->delete();
        return ApiEnvelope::ok([], 'Fourniture supprimée.');
    }

    public function publier(Request $request, int $listeId): JsonResponse
    {
        $enseignant = $request->user()->enseignants()->where('actif', true)->first();
        abort_unless($enseignant, 403);
        $liste = ListeFournitures::where('etablissement_id', $enseignant->etablissement_id)->findOrFail($listeId);
        $liste->update(['publie' => ! $liste->publie]);
        return ApiEnvelope::ok(['liste' => $liste->fresh()]);
    }
}
