<?php

namespace App\Http\Controllers;

use App\Models\EdtVacataireImport;
use App\Models\EdtVacataireSlot;
use App\Models\Enseignant;
use App\Services\Edt\OpenAiVacataireOcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VacataireImportController extends Controller
{
    public function __construct(
        private readonly OpenAiVacataireOcrService $ocrService
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $etabId = $request->user()->etablissement_id;

        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'source_type' => ['required', 'in:photo,image,scan,pdf'],
            'fichier' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $enseignant = Enseignant::query()
            ->where('etablissement_id', $etabId)
            ->where('actif', true)
            ->where('statut', Enseignant::STATUT_VACATAIRE)
            ->findOrFail((int) $validated['enseignant_id']);

        $file = $request->file('fichier');
        $path = $file->store('edt/vacataires', 'public');

        $import = EdtVacataireImport::create([
            'etablissement_id' => $etabId,
            'annee_scolaire_id' => $validated['annee_scolaire_id'],
            'enseignant_id' => $enseignant->id,
            'source_type' => $validated['source_type'],
            'fichier_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'uploaded',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->back()
            ->with('success', "Import #{$import->id} enregistré. Lance maintenant l’analyse OCR.");
    }

    public function parse(Request $request, EdtVacataireImport $import): RedirectResponse
    {
        abort_unless($import->etablissement_id === $request->user()->etablissement_id, 403);

        if (!$import->fichier_path || !Storage::disk('public')->exists($import->fichier_path)) {
            return back()->with('error', 'Fichier introuvable pour cet import.');
        }

        $payload = $this->ocrService->extractFromStoredFile('public', $import->fichier_path);

        $import->update([
            'payload_extrait_json' => $payload,
            'resume_extraction' => $payload['source_notes'] ?? 'Extraction OCR terminée.',
            'confidence_score' => $payload['confidence_score'] ?? 0,
            'status' => 'parsed',
        ]);

        return back()->with('success', "OCR terminé pour l’import #{$import->id}. Vérifie puis valide les créneaux extraits.");
    }

    public function validateImport(Request $request, EdtVacataireImport $import): RedirectResponse
    {
        abort_unless($import->etablissement_id === $request->user()->etablissement_id, 403);

        $validated = $request->validate([
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.jour' => ['required', 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche'],
            'slots.*.heure_debut' => ['required', 'date_format:H:i'],
            'slots.*.heure_fin' => ['required', 'date_format:H:i'],
            'slots.*.etat' => ['required', 'in:indisponible,disponible,prefere,a_eviter'],
            'slots.*.commentaire' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($import, $validated, $request) {
            EdtVacataireSlot::query()->where('import_id', $import->id)->delete();

            foreach ($validated['slots'] as $slot) {
                EdtVacataireSlot::create([
                    'import_id' => $import->id,
                    'enseignant_id' => $import->enseignant_id,
                    'jour' => $slot['jour'],
                    'heure_debut' => $slot['heure_debut'],
                    'heure_fin' => $slot['heure_fin'],
                    'creneau_id' => null,
                    'etat' => $slot['etat'],
                    'site_externe' => null,
                    'commentaire' => $slot['commentaire'] ?? null,
                    'source_confidence' => $import->confidence_score ?? 0,
                ]);
            }

            $import->update([
                'status' => 'validated',
                'validated_by' => $request->user()->id,
                'validated_at' => now(),
            ]);
        });

        return back()->with('success', "Import #{$import->id} validé. Il sera utilisé par la génération IA.");
    }
}