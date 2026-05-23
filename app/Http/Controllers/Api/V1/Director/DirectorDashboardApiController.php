<?php

namespace App\Http\Controllers\Api\V1\Director;

use App\Http\Controllers\Controller;
use App\Models\AlertePointage;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\MoyenneGenerale;
use App\Models\Paiement;
use App\Models\Pointage;
use App\Models\Trimestre;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API mobile pour la direction (directeur / directeur_adjoint / gestionnaire / censeur / secretaire / comptable).
 * Retourne les mêmes données que /dashboard (web), au format JSON pour l'app mobile.
 */
class DirectorDashboardApiController extends Controller
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
     * Vue d'ensemble pour le dashboard mobile direction.
     */
    public function overview(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $trimestre = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
            : null;

        // Effectifs — filtrage strict année courante
        $totalEleves = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('actif', true)->inscritsCetteAnnee($annee->id)->count()
            : 0;
        $totalEnseignants = Enseignant::where('etablissement_id', $etab->id)->where('actif', true)->count();
        $totalClasses = $annee
            ? Classe::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->count()
            : 0;

        $elevesF = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('sexe', 'F')->where('actif', true)->inscritsCetteAnnee($annee->id)->count()
            : 0;
        $elevesM = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('sexe', 'M')->where('actif', true)->inscritsCetteAnnee($annee->id)->count()
            : 0;

        // Pointage du jour
        $pointagesJour = Pointage::where('etablissement_id', $etab->id)
            ->where('date', today())
            ->where('type_scan', 'arrivee')
            ->get(['enseignant_id', 'statut']);

        $presentsCount = $pointagesJour->whereIn('statut', ['present', 'retard'])->unique('enseignant_id')->count();
        $retardsCount = $pointagesJour->where('statut', 'retard')->unique('enseignant_id')->count();

        // Finances
        $totalAttendu = $annee
            ? Inscription::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'validee')
                ->sum('montant_net')
            : 0;

        $totalPaye = $annee
            ? Paiement::where('etablissement_id', $etab->id)
                ->where('statut', 'confirme')
                ->whereHas('inscription', fn ($q) => $q->where('annee_scolaire_id', $annee->id))
                ->sum('montant')
            : 0;

        // Pédagogie
        $moyenneGenerale = $trimestre
            ? MoyenneGenerale::whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
                ->where('trimestre_id', $trimestre->id)
                ->avg('moyenne_generale')
            : null;

        $elevesEnDifficulte = $trimestre
            ? MoyenneGenerale::where('trimestre_id', $trimestre->id)
                ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
                ->where('moyenne_generale', '<', 10)
                ->count()
            : 0;

        // Alertes
        $alertesNonLues = AlertePointage::where('etablissement_id', $etab->id)
            ->where('traitee', false)
            ->count();

        // Paiements Wave en attente (action requise)
        $waveEnAttente = Paiement::where('etablissement_id', $etab->id)
            ->where('mode', 'wave')
            ->where('statut', 'en_attente')
            ->count();

        return ApiEnvelope::success([
            'etablissement' => $etab->only(['id', 'nom', 'sigle', 'adresse', 'telephone']),
            'annee_scolaire' => $annee?->only(['id', 'libelle', 'en_cours']),
            'trimestre' => $trimestre?->only(['id', 'libelle', 'numero']),
            'effectifs' => [
                'eleves_total' => $totalEleves,
                'eleves_filles' => $elevesF,
                'eleves_garcons' => $elevesM,
                'enseignants_total' => $totalEnseignants,
                'classes_total' => $totalClasses,
            ],
            'pointage_aujourdhui' => [
                'enseignants_presents' => $presentsCount,
                'enseignants_absents' => max(0, $totalEnseignants - $presentsCount),
                'enseignants_retards' => $retardsCount,
                'taux_presence' => $totalEnseignants > 0
                    ? round(($presentsCount / $totalEnseignants) * 100, 1)
                    : 0,
            ],
            'finances' => [
                'total_attendu' => (int) $totalAttendu,
                'total_paye' => (int) $totalPaye,
                'reste' => (int) max(0, $totalAttendu - $totalPaye),
                'taux_recouvrement' => $totalAttendu > 0
                    ? round(($totalPaye / $totalAttendu) * 100, 1)
                    : 0,
                'wave_en_attente' => $waveEnAttente,
            ],
            'pedagogie' => [
                'moyenne_generale' => $moyenneGenerale ? round((float) $moyenneGenerale, 2) : null,
                'eleves_en_difficulte' => $elevesEnDifficulte,
            ],
            'alertes' => [
                'pointage_non_lues' => $alertesNonLues,
                'total' => $alertesNonLues,
            ],
        ], 'Tableau de bord direction.');
    }

    /**
     * Listes des enseignants pointés / absents aujourd'hui.
     */
    public function pointageJour(Request $request): JsonResponse
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

        $enseignantsPointes = $pointages->pluck('enseignant_id')->unique();

        $absents = Enseignant::where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->whereNotIn('id', $enseignantsPointes)
            ->get(['id', 'nom', 'prenom', 'photo_path', 'telephone', 'specialite']);

        return ApiEnvelope::success([
            'date' => today()->toDateString(),
            'pointages' => $pointages,
            'absents' => $absents,
            'totaux' => [
                'pointes' => $enseignantsPointes->count(),
                'absents' => $absents->count(),
            ],
        ], 'Pointage du jour.');
    }

    /**
     * Statistiques financières pour graphiques mobile.
     */
    public function financesGraphiques(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);

        $paiementsParMode = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->select('mode', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as nombre'))
            ->groupBy('mode')
            ->get();

        $paiementsMensuels = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->where('date_paiement', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(date_paiement, "%Y-%m") as mois'),
                DB::raw('SUM(montant) as total')
            )
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $derniersPaiements = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->with('eleve:id,nom,prenom,matricule_interne')
            ->latest('date_paiement')
            ->take(10)
            ->get();

        return ApiEnvelope::success([
            'paiements_par_mode' => $paiementsParMode,
            'paiements_mensuels' => $paiementsMensuels,
            'derniers_paiements' => $derniersPaiements,
        ], 'Statistiques financières.');
    }

    /**
     * Activité récente : dernières inscriptions, derniers paiements, derniers événements.
     */
    public function activiteRecente(Request $request): JsonResponse
    {
        $etab = $this->etablissement($request);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $inscriptions = $annee
            ? Inscription::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('eleve:id,nom,prenom,matricule_interne')
                ->latest()
                ->take(10)
                ->get()
            : collect();

        $paiementsRecents = Paiement::where('etablissement_id', $etab->id)
            ->with('eleve:id,nom,prenom')
            ->latest('created_at')
            ->take(10)
            ->get();

        $alertes = AlertePointage::where('etablissement_id', $etab->id)
            ->where('traitee', false)
            ->latest()
            ->take(10)
            ->get();

        return ApiEnvelope::success([
            'inscriptions_recentes' => $inscriptions,
            'paiements_recents' => $paiementsRecents,
            'alertes' => $alertes,
        ], 'Activité récente.');
    }
}
