<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Note;
use App\Services\Mobile\ApiSyncDedupService;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherNoteController extends Controller
{
    use ResolvesTeacherContext;

    public function index(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->assertEvaluationOwned($request, $evaluation);

        // Tous les élèves actifs de la classe + notes existantes
        $eleves = Eleve::where('classe_id', $evaluation->classe_id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps']);

        $notesByEleve = Note::where('evaluation_id', $evaluation->id)
            ->get()
            ->keyBy('eleve_id');

        $rows = $eleves->map(function ($e) use ($notesByEleve) {
            $n = $notesByEleve->get($e->id);
            return [
                'eleve_id'      => $e->id,
                'eleve'         => [
                    'id'                => $e->id,
                    'nom'               => $e->nom,
                    'prenom'            => $e->prenom,
                    'matricule_interne' => $e->matricule_interne,
                ],
                'note'        => $n?->note,
                'absent'      => (bool) ($n?->absent ?? false),
                'observation' => $n?->observation,
            ];
        });

        return ApiEnvelope::success(
            [
                'evaluation' => $evaluation->load(['matiere:id,nom,code'])->only(['id', 'titre', 'note_sur', 'matiere']),
                'notes'      => $rows,
            ],
            'Notes de l\'évaluation.'
        );
    }

    public function storeBulk(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->assertEvaluationOwned($request, $evaluation);

        $request->validate([
            'notes' => 'required|array|min:1',
            'notes.*.eleve_id' => 'required|exists:eleves,id',
            'notes.*.note' => 'nullable|numeric|min:0|max:'.$evaluation->note_sur,
            'notes.*.absent' => 'sometimes|boolean',
            'notes.*.observation' => 'nullable|string|max:2000',
            'client_mutation_id' => 'nullable|string|max:64',
        ]);

        if ($replay = ApiSyncDedupService::replayIfExists($request, 'notes_saisie')) {
            return ApiEnvelope::success($replay, 'Notes (rejoué).');
        }

        $classeId = $evaluation->classe_id;
        foreach ($request->notes as $noteData) {
            $ok = Eleve::where('id', $noteData['eleve_id'])
                ->where('classe_id', $classeId)
                ->where('actif', true)
                ->exists();
            if (! $ok) {
                return ApiEnvelope::fail(
                    'Un élève n\'appartient pas à la classe de cette évaluation.',
                    ['notes' => ['Élève non autorisé.']],
                    422
                );
            }
        }

        foreach ($request->notes as $noteData) {
            $absent = (bool) ($noteData['absent'] ?? false);
            Note::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'eleve_id' => $noteData['eleve_id']],
                [
                    'note' => $absent ? null : ($noteData['note'] ?? null),
                    'absent' => $absent,
                    'observation' => $noteData['observation'] ?? null,
                    'saisie_par' => $request->user()->id,
                    'date_saisie' => now(),
                ]
            );
        }

        $payload = [
            'count' => count($request->notes),
            'moyenne_classe' => $evaluation->fresh()->moyenneClasse(),
        ];

        ApiSyncDedupService::store($request, 'notes_saisie', $evaluation->id, $payload);

        return ApiEnvelope::success($payload, count($request->notes).' note(s) enregistrée(s).');
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $eval = $note->evaluation;
        abort_if(! $eval, 404);
        $evaluation = $this->assertEvaluationOwned($request, $eval);

        $validated = $request->validate([
            'note' => 'nullable|numeric|min:0|max:'.$evaluation->note_sur,
            'absent' => 'sometimes|boolean',
            'observation' => 'nullable|string|max:2000',
        ]);

        $ok = Eleve::where('id', $note->eleve_id)
            ->where('classe_id', $evaluation->classe_id)
            ->where('actif', true)
            ->exists();
        if (! $ok) {
            abort(404);
        }

        $absent = array_key_exists('absent', $validated) ? (bool) $validated['absent'] : $note->absent;
        $note->fill([
            'note' => $absent ? null : ($validated['note'] ?? $note->note),
            'absent' => $absent,
            'observation' => array_key_exists('observation', $validated) ? $validated['observation'] : $note->observation,
            'saisie_par' => $request->user()->id,
            'date_saisie' => now(),
        ]);
        $note->save();

        return ApiEnvelope::success($note->fresh()->load('eleve:id,nom,prenom')->toArray(), 'Note mise à jour.');
    }
}
