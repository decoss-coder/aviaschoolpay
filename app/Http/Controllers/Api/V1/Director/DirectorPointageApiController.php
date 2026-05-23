<?php

namespace App\Http\Controllers\Api\V1\Director;

use App\Http\Controllers\Controller;
use App\Models\AlertePointage;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Pointage;
use App\Models\Salle;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API mobile direction — supervision du pointage enseignants + alertes + QR.
 */
class DirectorPointageApiController extends Controller
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
     * Vue d'ensemble pointage du jour (KPI + listes).
     */
    public function aujourdhui(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);

        $pointages = Pointage::where('etablissement_id', $etab->id)
            ->where('date', today())
            ->where('type_scan', 'arrivee')
            ->with([
                'enseignant:id,nom,prenom,photo_path,specialite,telephone',
                'salle:id,nom',
            ])
            ->orderBy('heure_scan')
            ->get();

        $totalEnseignants = Enseignant::where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->count();

        $enseignantsPointes = $pointages->pluck('enseignant_id')->unique();

        $absents = Enseignant::where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->whereNotIn('id', $enseignantsPointes)
            ->get(['id', 'nom', 'prenom', 'photo_path', 'telephone', 'specialite']);

        return ApiEnvelope::success([
            'date' => today()->toDateString(),
            'kpi' => [
                'total_enseignants' => $totalEnseignants,
                'presents' => $pointages->whereIn('statut', ['present', 'retard'])->unique('enseignant_id')->count(),
                'retards' => $pointages->where('statut', 'retard')->unique('enseignant_id')->count(),
                'hors_zone' => $pointages->where('statut', 'hors_zone')->count(),
                'absents' => $absents->count(),
                'taux_presence' => $totalEnseignants > 0
                    ? round(($pointages->unique('enseignant_id')->count() / $totalEnseignants) * 100, 1)
                    : 0,
            ],
            'pointages' => $pointages,
            'absents' => $absents,
        ], 'Pointage du jour.');
    }

    /**
     * Liste des pointages avec filtres (date, statut, enseignant).
     */
    public function liste(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);

        $query = Pointage::where('etablissement_id', $etab->id)
            ->with(['enseignant:id,nom,prenom', 'salle:id,nom']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }
        if ($request->filled('type_scan')) {
            $query->where('type_scan', $request->type_scan);
        }

        $perPage = min(100, max(10, (int) $request->get('per_page', 30)));
        $pointages = $query->orderByDesc('date')
            ->orderByDesc('heure_scan')
            ->paginate($perPage);

        return ApiEnvelope::success([
            'pointages' => $pointages->toArray(),
        ], 'Liste des pointages.');
    }

    /**
     * Détail d'un pointage.
     */
    public function show(Request $request, Pointage $pointage): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($pointage->etablissement_id === $etab->id, 403);

        $pointage->load([
            'enseignant:id,nom,prenom,photo_path,telephone,specialite',
            'salle:id,nom',
            'alertes',
        ]);

        return ApiEnvelope::success([
            'pointage' => $pointage,
            'est_anormal' => $pointage->estAnormal(),
            'a_cahier_texte' => $pointage->aCahierTexte(),
            'selfie_url' => $pointage->selfie_path
                ? route('pointages.selfie', $pointage)
                : null,
        ], 'Détail pointage.');
    }

    /**
     * Liste alertes pointage avec filtres.
     */
    public function alertes(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);

        $query = AlertePointage::where('etablissement_id', $etab->id)
            ->with([
                'pointage.enseignant:id,nom,prenom',
                'pointage.salle:id,nom',
            ]);

        if ($request->filled('statut')) {
            if ($request->statut === 'non_traitees') {
                $query->where('traitee', false);
            } elseif ($request->statut === 'non_lues') {
                $query->where('lue', false);
            } elseif ($request->statut === 'traitees') {
                $query->where('traitee', true);
            }
        }
        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        $perPage = min(100, max(10, (int) $request->get('per_page', 30)));
        $alertes = $query->latest()->paginate($perPage);

        return ApiEnvelope::success([
            'alertes' => $alertes->toArray(),
            'totaux' => [
                'non_lues' => AlertePointage::where('etablissement_id', $etab->id)->where('lue', false)->count(),
                'non_traitees' => AlertePointage::where('etablissement_id', $etab->id)->where('traitee', false)->count(),
            ],
        ], 'Alertes pointage.');
    }

    /**
     * Marquer une alerte comme lue.
     */
    public function lireAlerte(Request $request, AlertePointage $alerte): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($alerte->etablissement_id === $etab->id, 403);

        $alerte->update([
            'lue' => true,
            'lue_at' => now(),
            'lue_par' => $request->user()->id,
        ]);

        return ApiEnvelope::success(['alerte' => $alerte->fresh()], 'Alerte marquée comme lue.');
    }

    /**
     * Traiter une alerte (clôture).
     */
    public function traiterAlerte(Request $request, AlertePointage $alerte): JsonResponse
    {
        $etab = $this->etablissement($request);
        abort_unless($alerte->etablissement_id === $etab->id, 403);

        $data = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
            'action_prise' => 'nullable|string|max:500',
        ]);

        $alerte->update([
            'traitee' => true,
            'traitee_at' => now(),
            'traitee_par' => $request->user()->id,
            'commentaire_traitement' => $data['commentaire'] ?? null,
            'action_prise' => $data['action_prise'] ?? null,
        ]);

        return ApiEnvelope::success(['alerte' => $alerte->fresh()], 'Alerte traitée.');
    }

    /**
     * QR codes des salles de l'établissement.
     */
    public function qrCodes(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);

        $salles = Salle::where('etablissement_id', $etab->id)
            ->with(['qrCodes' => fn ($q) => $q->where('actif', true)->latest()])
            ->orderBy('nom')
            ->get(['id', 'nom']);

        return ApiEnvelope::success([
            'salles' => $salles,
        ], 'QR codes salles.');
    }
}
