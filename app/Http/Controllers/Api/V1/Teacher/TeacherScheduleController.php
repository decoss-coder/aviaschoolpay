<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\EmploiDuTemps;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherScheduleController extends Controller
{
    use ResolvesTeacherContext;

    public function index(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);
        abort_if(! $annee, 422, 'Aucune année scolaire en cours.');

        $rows = EmploiDuTemps::where('enseignant_id', $ens->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('actif', true)
            ->with(['classe:id,nom', 'matiere:id,nom,code', 'salle:id,nom', 'etablissement:id,nom', 'creneau:id,heure_debut,heure_fin,ordre'])
            ->orderByRaw("CASE jour WHEN 'lundi' THEN 1 WHEN 'mardi' THEN 2 WHEN 'mercredi' THEN 3 WHEN 'jeudi' THEN 4 WHEN 'vendredi' THEN 5 WHEN 'samedi' THEN 6 ELSE 7 END")
            ->orderBy('creneau_id')
            ->get()
            ->map(fn ($e) => $this->mapSeance($e));

        return ApiEnvelope::success(['emploi_du_temps' => $rows], 'Emploi du temps.');
    }

    public function today(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);
        abort_if(! $annee, 422, 'Aucune année scolaire en cours.');

        $jour = $this->todayFrenchWeekday();
        $rows = $jour
            ? EmploiDuTemps::where('enseignant_id', $ens->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('jour', $jour)
                ->where('actif', true)
                ->with(['classe:id,nom', 'matiere:id,nom,code', 'salle:id,nom', 'etablissement:id,nom', 'creneau:id,heure_debut,heure_fin,ordre'])
                ->orderBy('creneau_id')
                ->get()
                ->map(fn ($e) => $this->mapSeance($e))
            : collect();

        return ApiEnvelope::success(['jour' => $jour, 'cours' => $rows], 'Cours du jour.');
    }

    private function mapSeance(EmploiDuTemps $e): array
    {
        return [
            'id' => $e->id,
            'jour' => $e->jour,
            'heure_debut' => $e->creneau ? substr((string) $e->creneau->heure_debut, 0, 5) : null,
            'heure_fin' => $e->creneau ? substr((string) $e->creneau->heure_fin, 0, 5) : null,
            'classe' => $e->classe?->only(['id', 'nom']),
            'matiere' => $e->matiere?->only(['id', 'nom', 'code']),
            'salle' => $e->salle?->only(['id', 'nom']),
            'etablissement' => $e->etablissement?->only(['id', 'nom']),
            'statut' => $e->actif ? 'actif' : 'inactif',
        ];
    }
}
