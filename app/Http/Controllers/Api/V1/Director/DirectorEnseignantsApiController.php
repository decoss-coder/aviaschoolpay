<?php

namespace App\Http\Controllers\Api\V1\Director;

use App\Http\Controllers\Controller;
use App\Models\AlertePointage;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Pointage;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API mobile direction — gestion / consultation des enseignants.
 */
class DirectorEnseignantsApiController extends Controller
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
     * Liste paginée des enseignants.
     */
    public function index(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);

        $query = Enseignant::query()
            ->where('etablissement_id', $etab->id);

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nom', 'like', "%{$q}%")
                  ->orWhere('prenom', 'like', "%{$q}%")
                  ->orWhere('matricule_mena', 'like', "%{$q}%")
                  ->orWhere('telephone', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if (! $request->boolean('inclure_inactifs')) {
            $query->where('actif', true);
        }

        $perPage = min(100, max(10, (int) $request->get('per_page', 30)));
        $enseignants = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage);

        // Présence du jour
        $pointesIds = Pointage::where('etablissement_id', $etab->id)
            ->where('date', today())
            ->where('type_scan', 'arrivee')
            ->pluck('enseignant_id')
            ->unique()
            ->all();

        return ApiEnvelope::success([
            'enseignants' => $enseignants->toArray(),
            'pointes_aujourdhui_ids' => $pointesIds,
            'totaux' => [
                'total' => Enseignant::where('etablissement_id', $etab->id)->where('actif', true)->count(),
                'presents_aujourdhui' => count($pointesIds),
            ],
        ], 'Liste des enseignants.');
    }

    /**
     * Fiche détaillée d'un enseignant.
     */
    public function show(Request $request, Enseignant $enseignant): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($enseignant->etablissement_id === $etab->id, 403);

        $enseignant->load([
            'affectations.classe:id,nom',
            'affectations.matiere:id,nom,code',
            'affectations.anneeScolaire:id,libelle',
        ]);

        // Stats pointage : 30 derniers jours
        $pointages30j = Pointage::where('enseignant_id', $enseignant->id)
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('heure_scan')
            ->limit(60)
            ->get();

        $alertes = AlertePointage::whereHas('pointage', fn ($q) =>
            $q->where('enseignant_id', $enseignant->id))
            ->where('traitee', false)
            ->latest()
            ->take(20)
            ->get();

        return ApiEnvelope::success([
            'enseignant' => $enseignant,
            'pointages_recents' => $pointages30j,
            'alertes_non_traitees' => $alertes,
            'pointage_aujourdhui' => $enseignant->pointageDuJour(),
            'est_present_aujourdhui' => $enseignant->estPresentAujourdhui(),
            'est_en_retard_aujourdhui' => $enseignant->estEnRetardAujourdhui(),
            'nb_alertes_non_traitees' => $enseignant->nbAlertesNonTraitees(),
        ], 'Fiche enseignant.');
    }

    /**
     * Pointages d'un enseignant sur une période.
     */
    public function pointages(Request $request, Enseignant $enseignant): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($enseignant->etablissement_id === $etab->id, 403);

        $dateDebut = $request->date_debut ?? now()->subDays(30)->toDateString();
        $dateFin = $request->date_fin ?? today()->toDateString();

        $pointages = Pointage::where('enseignant_id', $enseignant->id)
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->with('salle:id,nom')
            ->orderByDesc('date')
            ->orderBy('heure_scan')
            ->get();

        return ApiEnvelope::success([
            'enseignant' => $enseignant->only(['id', 'nom', 'prenom']),
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'pointages' => $pointages,
            'stats' => [
                'total' => $pointages->count(),
                'retards' => $pointages->where('statut', 'retard')->count(),
                'hors_zone' => $pointages->where('statut', 'hors_zone')->count(),
            ],
        ], 'Historique des pointages.');
    }
}
