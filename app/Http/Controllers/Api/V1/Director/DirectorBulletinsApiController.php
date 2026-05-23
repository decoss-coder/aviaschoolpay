<?php

namespace App\Http\Controllers\Api\V1\Director;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\MoyenneGenerale;
use App\Models\MoyenneMatiere;
use App\Models\Trimestre;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API mobile direction — consultation Notes / Moyennes / Bulletins.
 */
class DirectorBulletinsApiController extends Controller
{
    private function etablissement(Request $request): Etablissement
    {
        $etabId = $request->user()->ecoleActiveId();
        $etab = $etabId
            ? Etablissement::find($etabId)
            : $request->user()->etablissement;

        abort_unless($etab, 403, 'Aucun établissement associé.');

        return $etab;
    }

    /**
     * Vue d'ensemble pédagogique : moyenne générale, top, difficulté.
     */
    public function overview(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $trimestre = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
              ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first()
            : null;

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        if (! $trimestre) {
            return ApiEnvelope::success([
                'moyenne_generale' => null,
                'top_eleves' => [],
                'eleves_en_difficulte' => 0,
                'trimestres' => $trimestres,
            ], 'Aucun trimestre actif.');
        }

        $moyenneGenerale = MoyenneGenerale::whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
            ->where('trimestre_id', $trimestre->id)
            ->avg('moyenne_generale');

        $topEleves = MoyenneGenerale::where('trimestre_id', $trimestre->id)
            ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
            ->with(['eleve:id,nom,prenom,matricule_interne', 'classe:id,nom'])
            ->orderByDesc('moyenne_generale')
            ->take(10)
            ->get();

        $elevesEnDifficulte = MoyenneGenerale::where('trimestre_id', $trimestre->id)
            ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
            ->where('moyenne_generale', '<', 10)
            ->with(['eleve:id,nom,prenom,matricule_interne', 'classe:id,nom'])
            ->orderBy('moyenne_generale')
            ->take(20)
            ->get();

        return ApiEnvelope::success([
            'trimestre' => $trimestre->only(['id', 'libelle', 'numero', 'en_cours']),
            'trimestres' => $trimestres,
            'moyenne_generale' => $moyenneGenerale ? round((float) $moyenneGenerale, 2) : null,
            'top_eleves' => $topEleves,
            'eleves_en_difficulte' => $elevesEnDifficulte,
            'kpi' => [
                'top_count' => $topEleves->count(),
                'difficulte_count' => $elevesEnDifficulte->count(),
            ],
        ], 'Vue d\'ensemble pédagogique.');
    }

    /**
     * Moyennes d'une classe sur un trimestre (toutes matières).
     */
    public function moyennesClasse(Request $request, Classe $classe): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($classe->etablissement_id === $etab->id, 403);

        $trimestreId = (int) $request->input('trimestre_id', 0);
        if (! $trimestreId) {
            $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
            $trimestre = $annee
                ? Trimestre::where('annee_scolaire_id', $annee->id)
                    ->where('en_cours', true)->first()
                  ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first()
                : null;
            $trimestreId = $trimestre?->id;
        }

        if (! $trimestreId) {
            return ApiEnvelope::fail('Aucun trimestre disponible.', [], 422);
        }

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne']);

        $moyennesG = MoyenneGenerale::where('trimestre_id', $trimestreId)
            ->where('classe_id', $classe->id)
            ->get()
            ->keyBy('eleve_id');

        $moyennesM = MoyenneMatiere::where('trimestre_id', $trimestreId)
            ->whereIn('eleve_id', $eleves->pluck('id'))
            ->matierePrincipaleOnly()
            ->with('matiere:id,nom,code')
            ->get()
            ->groupBy('eleve_id');

        $data = $eleves->map(function ($e) use ($moyennesG, $moyennesM) {
            $mg = $moyennesG->get($e->id);
            return [
                'eleve' => $e,
                'moyenne_generale' => $mg?->moyenne_generale,
                'rang' => $mg?->rang,
                'moyennes_matieres' => $moyennesM->get($e->id, collect())->values(),
            ];
        });

        return ApiEnvelope::success([
            'classe' => $classe->only(['id', 'nom']),
            'trimestre_id' => $trimestreId,
            'eleves' => $data,
        ], 'Moyennes de la classe.');
    }

    /**
     * Téléchargement du PDF d'un bulletin (réutilise BulletinAdminController).
     */
    public function bulletinPdf(Request $request, Eleve $eleve, int $trimestre)
    {
        $etab = $this->etablissement($request);
        abort_unless($eleve->etablissement_id === $etab->id, 403);

        // Délègue à la logique web existante en injectant la requête
        return app(\App\Http\Controllers\Admin\BulletinAdminController::class)
            ->pdf($request, $eleve, $trimestre);
    }
}
