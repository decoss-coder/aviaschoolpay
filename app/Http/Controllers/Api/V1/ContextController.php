<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiEnvelope;
use App\Services\Scolarite\AnneeScolaireContext;
use App\Services\Scolarite\AnneeScolaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContextController extends Controller
{
    /**
     * Définit l'établissement actif pour un enseignant (équivalent mobile du sélecteur web).
     */
    public function setEtablissement(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isEnseignant(), 403);

        $data = $request->validate([
            'etablissement_id' => 'required|integer|exists:etablissements,id',
        ]);

        $ok = $user->enseignants()
            ->where('actif', true)
            ->where('etablissement_id', $data['etablissement_id'])
            ->exists();

        abort_unless($ok, 422, 'Vous n\'êtes pas enseignant dans cet établissement.');

        $user->forceFill(['active_etablissement_id' => $data['etablissement_id']])->save();

        AnneeScolaireService::initialiserContexte((int) $data['etablissement_id']);

        return ApiEnvelope::success([
            'active_etablissement_id' => $user->active_etablissement_id,
            'enseignant_id' => $user->enseignantActif()?->id,
            'annee_scolaire_courante' => AnneeScolaireContext::toApiPayload(),
        ], 'Établissement actif mis à jour.');
    }

    public function anneeScolaire(Request $request): JsonResponse
    {
        $user = $request->user();
        $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;

        if ($etabId) {
            AnneeScolaireService::initialiserContexte((int) $etabId);
        }

        return ApiEnvelope::success([
            'annee_scolaire_courante' => AnneeScolaireContext::toApiPayload(),
            'annees' => $etabId
                ? collect(AnneeScolaireService::listePourEtablissement((int) $etabId))->map(fn ($a) => [
                    'id' => $a->id,
                    'libelle' => $a->libelle,
                    'date_debut' => $a->date_debut?->toDateString(),
                    'date_fin' => $a->date_fin?->toDateString(),
                    'en_cours' => $a->en_cours,
                    'cloturee' => $a->cloturee,
                    'archivee' => $a->archivee,
                ])
                : [],
        ], 'Contexte année scolaire.');
    }
}
