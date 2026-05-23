<?php

namespace App\Http\Controllers\Api\V1\Director;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Scolarite\AnneeScolaireContext;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API mobile direction — gestion / consultation des élèves.
 */
class DirectorElevesApiController extends Controller
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
     * Liste paginée des élèves avec filtres.
     */
    public function index(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $query = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->when($annee, fn ($q) => $q->pourAnneeScolaire($annee))
            ->with(['classe:id,nom,niveau_id', 'contactPrincipal:id,nom,prenom,telephone']);

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nom', 'like', "%{$q}%")
                  ->orWhere('prenom', 'like', "%{$q}%")
                  ->orWhere('matricule_interne', 'like', "%{$q}%")
                  ->orWhere('matricule_desps', 'like', "%{$q}%");
            });
        }
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut_eleve', $request->statut);
        }
        if ($request->filled('sexe')) {
            $query->where('sexe', $request->sexe);
        }
        if (! $request->boolean('inclure_inactifs')) {
            $query->where('actif', true);
        }

        $perPage = min(100, max(10, (int) $request->get('per_page', 30)));
        $eleves = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage);

        $baseTotaux = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->when($annee, fn ($q) => $q->pourAnneeScolaire($annee));

        return ApiEnvelope::success([
            'eleves' => $eleves->toArray(),
            'totaux' => [
                'total' => (clone $baseTotaux)->count(),
                'filles' => (clone $baseTotaux)->where('sexe', 'F')->count(),
                'garcons' => (clone $baseTotaux)->where('sexe', 'M')->count(),
                'affectes' => (clone $baseTotaux)->where('statut_eleve', 'AFF')->count(),
                'non_affectes' => (clone $baseTotaux)->where('statut_eleve', 'NAFF')->count(),
            ],
        ], 'Liste des élèves.');
    }

    /**
     * Détail d'un élève (fiche).
     */
    public function show(Request $request, Eleve $eleve): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($eleve->etablissement_id === $etab->id, 403);

        $eleve->load([
            'classe.niveau',
            'parents',
            'contactPrincipal',
            'inscriptionEnCours',
            'etablissement:id,nom',
        ]);

        $finances = EleveScolariteService::resumePourEleve($eleve);

        return ApiEnvelope::success([
            'eleve' => $eleve,
            'finances' => $finances,
        ], 'Fiche élève.');
    }

    /**
     * Filtres pour l'interface mobile : classes, niveaux, statuts disponibles.
     */
    public function filtres(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau:id,libelle,cycle')
                ->orderBy('niveau_id')
                ->orderBy('nom')
                ->get(['id', 'nom', 'niveau_id'])
            : collect();

        return ApiEnvelope::success([
            'classes' => $classes,
            'statuts' => ['AFF' => 'Affecté', 'NAFF' => 'Non affecté'],
            'sexes' => ['M' => 'Masculin', 'F' => 'Féminin'],
        ], 'Filtres élèves.');
    }
}
