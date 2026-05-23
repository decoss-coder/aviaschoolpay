<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\TypeEvaluation;
use App\Models\Trimestre;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherEvaluationController extends Controller
{
    use ResolvesTeacherContext;

    public function index(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);

        $q = Evaluation::query()
            ->where('enseignant_id', $ens->id)
            ->where('etablissement_id', $etabId)
            ->with(['classe:id,nom', 'matiere:id,nom,code', 'trimestre:id,libelle', 'typeEvaluation:id,nom,code']);

        if ($request->filled('classe_id')) {
            $q->where('classe_id', (int) $request->classe_id);
        }
        if ($request->filled('trimestre_id')) {
            $q->where('trimestre_id', (int) $request->trimestre_id);
        }

        $rows = $q->latest('date_evaluation')->paginate((int) $request->get('per_page', 25));

        $items = collect($rows->items())->map(fn ($e) => $this->serialize($e))->all();

        return ApiEnvelope::success([
            'data' => $items,
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
        ], 'Liste des évaluations.');
    }

    public function store(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);

        $validated = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'titre' => 'required|string|max:200',
            'type' => 'required_without:type_evaluation_id|string|max:50',
            'type_evaluation_id' => 'required_without:type|exists:types_evaluation,id',
            'date_evaluation' => 'required|date',
            'coefficient' => 'nullable|numeric|min:0.5|max:10',
            'bareme' => 'nullable|numeric|min:1|max:100',
            'trimestre_id' => 'required|exists:trimestres,id',
            'description' => 'nullable|string',
            'fichier_sujet' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:20480',
        ]);

        $classe = Classe::findOrFail($validated['classe_id']);
        $this->assertClasseAssignable($request, $classe);
        $this->authorizeMatierePourClasse($request, $classe->id, (int) $validated['matiere_id']);

        $trimestre = Trimestre::findOrFail($validated['trimestre_id']);
        if ((int) $trimestre->annee_scolaire_id !== (int) $classe->annee_scolaire_id) {
            return ApiEnvelope::fail('Le trimestre ne correspond pas à l\'année scolaire de la classe.', [], 422);
        }

        $typeEvalId = $this->resolveTypeEvaluationId($etabId, $validated, $request);
        if (! $typeEvalId) {
            return ApiEnvelope::fail('Type d\'évaluation inconnu ou inactif pour cet établissement.', ['type' => ['Type d\'évaluation introuvable.']], 422);
        }

        $bareme = (float) ($validated['bareme'] ?? 20);
        $coeff = (float) ($validated['coefficient'] ?? 1);

        $fichierPath = null;
        if ($request->hasFile('fichier_sujet')) {
            $fichierPath = $request->file('fichier_sujet')->store(
                "evaluations/{$etabId}/{$classe->id}",
                'public'
            );
        }

        $evaluation = Evaluation::create([
            'etablissement_id' => $etabId,
            'classe_id' => $classe->id,
            'matiere_id' => $validated['matiere_id'],
            'enseignant_id' => $ens->id,
            'trimestre_id' => $validated['trimestre_id'],
            'type_evaluation_id' => $typeEvalId,
            'titre' => $validated['titre'],
            'date_evaluation' => $validated['date_evaluation'],
            'note_sur' => $bareme,
            'coefficient' => $coeff,
            'description' => $validated['description'] ?? null,
            'statut' => 'en_saisie',
            'fichier_sujet_path' => $fichierPath,
        ]);

        return ApiEnvelope::success(
            $this->serialize($evaluation->fresh()->load(['classe:id,nom', 'matiere:id,nom,code', 'typeEvaluation:id,nom,code'])),
            'Évaluation créée.',
            201
        );
    }

    public function show(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->assertEvaluationOwned($request, $evaluation);

        return ApiEnvelope::success(
            $this->serialize($evaluation->load(['classe:id,nom', 'matiere:id,nom,code', 'trimestre:id,libelle', 'typeEvaluation:id,nom,code'])),
            'Détail de l\'évaluation.'
        );
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->assertEvaluationOwned($request, $evaluation);

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:200',
            'date_evaluation' => 'sometimes|date',
            'coefficient' => 'nullable|numeric|min:0.5|max:10',
            'bareme' => 'nullable|numeric|min:1|max:100',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:50',
            'type_evaluation_id' => 'sometimes|exists:types_evaluation,id',
            'trimestre_id' => 'sometimes|exists:trimestres,id',
        ]);

        if (isset($validated['trimestre_id'])) {
            $trim = Trimestre::findOrFail($validated['trimestre_id']);
            if ((int) $trim->annee_scolaire_id !== (int) $evaluation->classe->annee_scolaire_id) {
                return ApiEnvelope::fail('Le trimestre ne correspond pas à l\'année scolaire de la classe.', [], 422);
            }
        }

        if (isset($validated['type_evaluation_id'])) {
            $te = TypeEvaluation::where('id', $validated['type_evaluation_id'])
                ->where('etablissement_id', $evaluation->etablissement_id)
                ->first();
            if (! $te) {
                return ApiEnvelope::fail('Type d\'évaluation invalide.', [], 422);
            }
            $evaluation->type_evaluation_id = $te->id;
        } elseif (isset($validated['type'])) {
            $tid = $this->resolveTypeEvaluationId($evaluation->etablissement_id, ['type' => $validated['type']] + $validated, $request);
            if ($tid) {
                $evaluation->type_evaluation_id = $tid;
            }
        }

        if (isset($validated['bareme'])) {
            $evaluation->note_sur = $validated['bareme'];
        }
        if (array_key_exists('coefficient', $validated)) {
            $evaluation->coefficient = $validated['coefficient'];
        }
        if (isset($validated['titre'])) {
            $evaluation->titre = $validated['titre'];
        }
        if (isset($validated['date_evaluation'])) {
            $evaluation->date_evaluation = $validated['date_evaluation'];
        }
        if (array_key_exists('description', $validated)) {
            $evaluation->description = $validated['description'];
        }
        if (isset($validated['trimestre_id'])) {
            $evaluation->trimestre_id = $validated['trimestre_id'];
        }

        $evaluation->save();

        return ApiEnvelope::success(
            $evaluation->fresh()->load(['classe:id,nom', 'matiere:id,nom,code', 'typeEvaluation:id,nom,code'])->toArray(),
            'Évaluation mise à jour.'
        );
    }

    public function destroy(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->assertEvaluationOwned($request, $evaluation);

        if ($evaluation->fichier_sujet_path) {
            Storage::disk('public')->delete($evaluation->fichier_sujet_path);
        }
        if ($evaluation->fichier_corrige_path) {
            Storage::disk('public')->delete($evaluation->fichier_corrige_path);
        }

        $evaluation->delete();

        return ApiEnvelope::success(new \stdClass, 'Évaluation supprimée.');
    }

    /**
     * Téléchargement du sujet d'une évaluation (enseignant ou élève autorisé).
     */
    public function downloadSujet(Request $request, Evaluation $evaluation): StreamedResponse
    {
        $user = $request->user();

        $ens = $user->enseignantActif();
        $isOwner = $ens && (int) $evaluation->enseignant_id === (int) $ens->id;

        if (! $isOwner) {
            // Élève de la classe ET notes publiées (ou évaluation rendue accessible)
            $eleve = $user->eleve;
            $isStudent = $eleve
                && (int) $eleve->classe_id === (int) $evaluation->classe_id;
            abort_unless($isStudent, 403, 'Accès refusé.');
        }

        abort_unless(
            $evaluation->fichier_sujet_path && Storage::disk('public')->exists($evaluation->fichier_sujet_path),
            404
        );

        $slug = \Illuminate\Support\Str::slug($evaluation->titre, '-');
        $ext = pathinfo($evaluation->fichier_sujet_path, PATHINFO_EXTENSION);
        $filename = $slug . ($ext ? '.' . $ext : '');

        return Storage::disk('public')->download($evaluation->fichier_sujet_path, $filename);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function serialize(Evaluation $evaluation): array
    {
        $arr = $evaluation->toArray();
        if ($evaluation->fichier_sujet_path) {
            $arr['fichier_url'] = url("/api/v1/evaluations/{$evaluation->id}/sujet");
            $arr['fichier_nom'] = basename($evaluation->fichier_sujet_path);
            $arr['fichier_size'] = Storage::disk('public')->exists($evaluation->fichier_sujet_path)
                ? Storage::disk('public')->size($evaluation->fichier_sujet_path)
                : null;
        } else {
            $arr['fichier_url'] = null;
            $arr['fichier_nom'] = null;
            $arr['fichier_size'] = null;
        }
        return $arr;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveTypeEvaluationId(int $etabId, array $validated, Request $request): ?int
    {
        if (! empty($validated['type_evaluation_id'])) {
            $t = TypeEvaluation::where('id', $validated['type_evaluation_id'])
                ->where('etablissement_id', $etabId)
                ->first();

            return $t?->id;
        }

        $type = strtolower((string) ($validated['type'] ?? $request->input('type', '')));

        return TypeEvaluation::query()
            ->where('etablissement_id', $etabId)
            ->where(function ($q) use ($type) {
                $q->whereRaw('LOWER(code) = ?', [$type])
                    ->orWhereRaw('LOWER(nom) = ?', [$type]);
            })
            ->when($this->typeEvaluationActifColumn(), fn ($q) => $q->where('actif', true))
            ->value('id');
    }

    private function typeEvaluationActifColumn(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('types_evaluation', 'actif');
    }
}
