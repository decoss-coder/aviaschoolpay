<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Affectation;
use App\Models\Devoir;
use App\Models\Eleve;
use App\Models\EmploiDuTemps;
use App\Models\Evaluation;
use App\Models\Pointage;
use App\Support\ApiEnvelope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    use ResolvesTeacherContext;

    public function show(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);
        $etab = $ens->etablissement;

        $today = $this->todayFrenchWeekday();

        $affectations = Affectation::where('enseignant_id', $ens->id)
            ->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etabId));

        $classeIds = (clone $affectations)->pluck('classe_id')->unique()->filter()->values();

        $nbClasses = $classeIds->count();
        $nbEleves = $classeIds->isEmpty()
            ? 0
            : Eleve::whereIn('classe_id', $classeIds)->where('actif', true)->count();

        $coursAujourdHui = collect();
        if ($annee && $today) {
            $coursAujourdHui = EmploiDuTemps::where('enseignant_id', $ens->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('jour', $today)
                ->where('actif', true)
                ->with(['classe:id,nom', 'matiere:id,nom,code', 'salle:id,nom', 'creneau:id,heure_debut,heure_fin,ordre'])
                ->orderBy('creneau_id')
                ->get();
        }

        $prochainCours = $this->resolveProchainCours($coursAujourdHui);

        $arr = Pointage::where('enseignant_id', $ens->id)->where('date', today())->where('type_scan', 'arrivee')->first();
        $dep = Pointage::where('enseignant_id', $ens->id)->where('date', today())->where('type_scan', 'depart')->first();

        $pointageStatut = 'non_pointe';
        if ($arr && $dep) {
            $pointageStatut = 'complet';
        } elseif ($arr) {
            $pointageStatut = 'arrivee_seulement';
        }

        $dernieresEvaluations = Evaluation::where('enseignant_id', $ens->id)
            ->where('etablissement_id', $etabId)
            ->with(['classe:id,nom', 'matiere:id,nom,code', 'trimestre:id,libelle'])
            ->latest('date_evaluation')
            ->limit(5)
            ->get();

        $devoirsRecents = Devoir::where('enseignant_id', $ens->id)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->with(['classe:id,nom', 'matiere:id,nom,code'])
            ->latest('date_publication')
            ->limit(5)
            ->get();

        $nbEvals = Evaluation::where('enseignant_id', $ens->id)->where('etablissement_id', $etabId)->count();
        $nbDevoirs = Devoir::where('enseignant_id', $ens->id)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->count();

        $alertes = [];
        if ($coursAujourdHui->isNotEmpty() && ! $arr) {
            $alertes[] = ['type' => 'pointage', 'message' => 'Pointage d\'arrivée non enregistré pour aujourd\'hui.'];
        }

        return ApiEnvelope::success([
            'enseignant' => $ens->only(['id', 'nom', 'prenom', 'email', 'photo_path']),
            'etablissement' => $etab ? $etab->only(['id', 'nom', 'code_desps']) : new \stdClass,
            'stats' => [
                'classes' => $nbClasses,
                'eleves' => $nbEleves,
                'cours_aujourdhui' => $coursAujourdHui->count(),
                'evaluations' => $nbEvals,
                'devoirs' => $nbDevoirs,
            ],
            'cours_aujourdhui' => $coursAujourdHui,
            'prochain_cours' => $prochainCours,
            'pointage' => [
                'statut' => $pointageStatut,
                'arrivee' => $arr?->heure_scan,
                'sortie' => $dep?->heure_scan,
            ],
            'dernieres_evaluations' => $dernieresEvaluations,
            'devoirs_recents' => $devoirsRecents,
            'alertes' => $alertes,
        ], 'Dashboard enseignant chargé');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\EmploiDuTemps>  $seancesJour
     */
    private function resolveProchainCours($seancesJour): ?array
    {
        if ($seancesJour->isEmpty()) {
            return null;
        }

        $now = Carbon::now()->format('H:i:s');

        foreach ($seancesJour as $s) {
            $debut = $s->creneau?->heure_debut;
            $fin = $s->creneau?->heure_fin;
            if (! $debut) {
                continue;
            }
            $fin = $fin ?? $debut;
            if ($now <= $fin) {
                return [
                    'id' => $s->id,
                    'jour' => $s->jour,
                    'heure_debut' => substr((string) $debut, 0, 8),
                    'heure_fin' => substr((string) $fin, 0, 8),
                    'classe' => $s->classe?->only(['id', 'nom']),
                    'matiere' => $s->matiere?->only(['id', 'nom', 'code']),
                    'salle' => $s->salle?->only(['id', 'nom']),
                ];
            }
        }

        return null;
    }
}
