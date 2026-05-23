<?php

namespace App\Services\Edt;

use App\Http\Requests\UploadVacataireImportRequest;
use App\Models\EdtGenerationScenario;
use App\Models\EdtVacataireImport;
use App\Models\Enseignant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class VacataireAvailabilityService
{
    public function storeImport(EdtGenerationScenario $scenario, UploadVacataireImportRequest $request): EdtVacataireImport
    {
        $path = null;
        /** @var UploadedFile|null $file */
        $file = $request->file('fichier');
        if ($file) {
            $path = $file->store('edt/vacataires', 'public');
        }

        return EdtVacataireImport::create([
            'etablissement_id' => $scenario->etablissement_id,
            'annee_scolaire_id' => $request->input('annee_scolaire_id', $scenario->annee_scolaire_id),
            'enseignant_id' => $request->integer('enseignant_id'),
            'source_type' => $request->string('source_type')->toString(),
            'fichier_path' => $path,
            'original_filename' => $file?->getClientOriginalName(),
            'status' => 'uploaded',
            'created_by' => $request->user()->id,
        ]);
    }

    public function parseImport(EdtVacataireImport $import): EdtVacataireImport
    {
        // Point d’extension : brancher ici un parseur IA PDF/photo
        // En première version on enregistre une structure vide et l’utilisateur valide manuellement.
        $payload = [
            'detected' => [],
            'note' => 'Extraction automatique à brancher ici',
        ];

        $import->update([
            'payload_extrait_json' => $payload,
            'resume_extraction' => 'Analyse initiale créée. Validation manuelle requise.',
            'confidence_score' => 0,
            'status' => 'parsed',
        ]);

        return $import->fresh();
    }

    public function validateImport(EdtVacataireImport $import, array $slots, User $user): void
    {
        $import->slots()->delete();

        foreach ($slots as $slot) {
            $import->slots()->create([
                'enseignant_id' => $import->enseignant_id,
                'jour' => Arr::get($slot, 'jour'),
                'heure_debut' => Arr::get($slot, 'heure_debut'),
                'heure_fin' => Arr::get($slot, 'heure_fin'),
                'creneau_id' => Arr::get($slot, 'creneau_id'),
                'etat' => Arr::get($slot, 'etat', 'indisponible'),
                'site_externe' => Arr::get($slot, 'site_externe'),
                'commentaire' => Arr::get($slot, 'commentaire'),
                'source_confidence' => Arr::get($slot, 'source_confidence', 100),
            ]);
        }

        $import->update([
            'status' => 'validated',
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);
    }

    public function getAvailabilityMap($enseignants): array
{
    $map = [];

    foreach ($enseignants as $enseignant) {
        $slots = $enseignant->vacataireSlots()
            ->whereHas('import', fn ($q) => $q->where('status', 'validated'))
            ->get();

        $map[$enseignant->id] = $slots->map(fn ($s) => [
            'jour' => $s->jour,
            'creneau_id' => $s->creneau_id,
            'heure_debut' => $s->heure_debut,
            'heure_fin' => $s->heure_fin,
            'etat' => $s->etat,
        ])->all();
    }

    return $map;
}
}