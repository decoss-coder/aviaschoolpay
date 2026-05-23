<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Eleve;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherClassController extends Controller
{
    use ResolvesTeacherContext;

    public function index(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);

        $rows = Affectation::where('enseignant_id', $ens->id)
            ->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etabId))
            ->with(['classe.niveau', 'classe.etablissement:id,nom', 'matiere:id,nom,code'])
            ->get()
            ->groupBy('classe_id')
            ->map(function ($group) {
                $classe = $group->first()->classe;
                if (! $classe) {
                    return null;
                }
                $matieres = $group->pluck('matiere')->filter()->unique('id')->values();
                $vol = $group->sum(fn ($a) => (float) ($a->volume_horaire_hebdo ?? 0));
                $nbEleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->count();

                return [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                    'niveau' => $classe->niveau?->only(['id', 'code', 'libelle']),
                    'etablissement' => $classe->etablissement?->only(['id', 'nom']),
                    'matieres' => $matieres,
                    'matiere_principale' => $matieres->first()?->only(['id', 'nom', 'code']),
                    'nombre_eleves' => $nbEleves,
                    'volume_horaire_hebdo' => $vol > 0 ? round($vol, 1) : null,
                ];
            })
            ->filter()
            ->values();

        return ApiEnvelope::success(['classes' => $rows], 'Liste des classes affectées.');
    }

    public function show(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $classe->loadMissing(['niveau', 'etablissement']);
        $etabId = $this->etablissementId($request);
        $annee = $this->anneeCourante($etabId);
        $ens = $this->enseignant($request);

        $group = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->with(['matiere:id,nom,code'])
            ->get();

        $matieres = $group->pluck('matiere')->filter()->unique('id')->values();
        $vol = $group->sum(fn ($a) => (float) ($a->volume_horaire_hebdo ?? 0));
        $nbEleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->count();

        $payload = [
            'id' => $classe->id,
            'nom' => $classe->nom,
            'niveau' => $classe->niveau?->only(['id', 'code', 'libelle']),
            'etablissement' => $classe->etablissement?->only(['id', 'nom']),
            'matieres' => $matieres,
            'matiere_principale' => $matieres->first()?->only(['id', 'nom', 'code']),
            'nombre_eleves' => $nbEleves,
            'volume_horaire_hebdo' => $vol > 0 ? round($vol, 1) : null,
        ];

        return ApiEnvelope::success($payload, 'Détail de la classe.');
    }

    public function students(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->map(fn (Eleve $e) => [
                'id' => $e->id,
                'matricule' => $e->matricule_interne ?? $e->matricule_desps,
                'matricule_interne' => $e->matricule_interne,
                'matricule_desps' => $e->matricule_desps,
                'nom' => $e->nom,
                'prenom' => $e->prenom,
                'sexe' => $e->sexe,
                'photo' => $e->photo_path,
                'statut_scolaire' => $e->statut_eleve ?? null,
            ]);

        return ApiEnvelope::success([
            'classe' => $classe->only(['id', 'nom']),
            'eleves' => $eleves,
        ], 'Élèves de la classe.');
    }
}
