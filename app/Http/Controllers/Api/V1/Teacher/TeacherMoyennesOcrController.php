<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\MoyenneMatiere;
use App\Services\FeuilleDeNote\FeuilleDeNoteOcrService;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Import OCR des moyennes par matière (photo de fiche de notes).
 *
 * Endpoints :
 *   POST /api/v1/teacher/classes/{classe}/moyennes/ocr-preview
 *     Body multipart : image (file), matiere_id (int)
 *     Réponse : { eleves: [{ matricule_ocr, eleve_id, eleve_label, moyenne_detectee, ... }], confidence }
 *
 *   POST /api/v1/teacher/classes/{classe}/moyennes/ocr-confirm
 *     Body JSON : { matiere_id, trimestre_id, lignes: [{ eleve_id, moyenne }] }
 *     Réponse : { count }
 */
class TeacherMoyennesOcrController extends Controller
{
    use ResolvesTeacherContext;

    public function preview(Request $request, Classe $classe, FeuilleDeNoteOcrService $ocr): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);

        $request->validate([
            'image'      => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            'matiere_id' => 'required|exists:matieres,id',
        ]);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $request->matiere_id);

        // Stockage temporaire
        $disk = 'local';
        $path = $request->file('image')->store('ocr-moyennes', $disk);

        try {
            $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->get();
            $result = $ocr->extract($disk, $path, $eleves);

            // Mapper le résultat → moyenne détectée pour chaque élève
            $lignes = collect($result['eleves'] ?? [])->map(function ($row) use ($eleves) {
                $eleveId = $row['eleve_id'] ?? null;
                $eleve = $eleveId ? $eleves->firstWhere('id', $eleveId) : null;

                // Première note détectée = moyenne (ou la plus grande si plusieurs)
                $notes = collect($row['notes'] ?? [])
                    ->filter(fn ($n) => is_numeric($n) || strtoupper((string) $n) === 'ABS')
                    ->values();

                $moyDetected = null;
                if ($notes->isNotEmpty() && is_numeric($notes->first())) {
                    $moyDetected = (float) $notes->first();
                }

                return [
                    'matricule_ocr'   => $row['matricule_ocr']   ?? null,
                    'matricule_match' => $row['matricule_match'] ?? null,
                    'eleve_id'        => $eleveId,
                    'eleve_label'     => $eleve
                        ? "{$eleve->prenom} {$eleve->nom}"
                        : ($row['nom_ocr'] ?? ''),
                    'matricule_desps' => $eleve?->matricule_desps,
                    'nom_ocr'         => $row['nom_ocr'] ?? null,
                    'moyenne_detectee' => $moyDetected,
                    'notes_brutes'    => $notes->all(),
                    'matched'         => $eleveId !== null,
                ];
            })->values();

            return ApiEnvelope::success([
                'image_path'  => $path,
                'confidence'  => $result['confidence'] ?? 0,
                'lignes'      => $lignes,
                'detectees'   => $lignes->filter(fn ($l) => $l['moyenne_detectee'] !== null)->count(),
                'matched'     => $lignes->filter(fn ($l) => $l['matched'])->count(),
                'total_eleves' => $eleves->count(),
            ], 'Analyse OCR terminée.');
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            return ApiEnvelope::fail('Échec OCR : ' . $e->getMessage(), [], 500);
        }
    }

    public function confirm(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens = $this->enseignant($request);

        $data = $request->validate([
            'matiere_id'         => 'required|exists:matieres,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'image_path'         => 'nullable|string',
            'lignes'             => 'required|array|min:1',
            'lignes.*.eleve_id'  => 'required|exists:eleves,id',
            'lignes.*.moyenne'   => 'nullable|numeric|min:0|max:20',
        ]);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        // Vérifier élèves de la classe
        $eleveIds = collect($data['lignes'])->pluck('eleve_id');
        $okIds = Eleve::whereIn('id', $eleveIds)
            ->where('classe_id', $classe->id)
            ->where('actif', true)
            ->pluck('id');
        if ($okIds->count() !== $eleveIds->unique()->count()) {
            return ApiEnvelope::fail('Certains élèves ne sont pas dans cette classe.', [], 422);
        }

        $count = 0;
        foreach ($data['lignes'] as $ligne) {
            if ($ligne['moyenne'] === null) continue;
            MoyenneMatiere::updateOrCreate(
                [
                    'eleve_id'     => $ligne['eleve_id'],
                    'matiere_id'   => $data['matiere_id'],
                    'trimestre_id' => $data['trimestre_id'],
                ],
                [
                    'classe_id'      => $classe->id,
                    'enseignant_id'  => $ens->id,
                    'moyenne'        => $ligne['moyenne'],
                    'saisie_par'     => $request->user()->id,
                    'date_saisie'    => now(),
                    'saisie_directe' => true,
                ]
            );
            $count++;
        }

        // Nettoyage fichier temporaire
        if (!empty($data['image_path'])) {
            Storage::disk('local')->delete($data['image_path']);
        }

        return ApiEnvelope::success(
            ['count' => $count],
            "{$count} moyenne(s) importée(s) avec succès."
        );
    }
}
