<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Enseignant;
use App\Models\EnseignantHoraireExterne;
use App\Models\EnseignantHoraireImport;
use App\Services\Edt\ExternalScheduleOcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EnseignantHoraireExterneController extends Controller
{
    public function __construct(
        private readonly ExternalScheduleOcrService $ocrService
    ) {
    }

    // ──────────────────────────────────────────────────────────────
    // Affichage principal
    // ──────────────────────────────────────────────────────────────

    public function index(Enseignant $enseignant)
    {
        $this->gate($enseignant);

        $anneeActive = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $enseignant->etablissement_id);

        // Imports en attente de validation
        $imports = EnseignantHoraireImport::where('enseignant_id', $enseignant->id)
            ->orderByDesc('created_at')
            ->get();

        // Créneaux déjà validés et actifs
        $slots = EnseignantHoraireExterne::where('enseignant_id', $enseignant->id)
            ->when($anneeActive, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('annee_scolaire_id', $anneeActive->id)->orWhereNull('annee_scolaire_id')
                )
            )
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get()
            ->groupBy('jour');

        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

        return view('emploi-du-temps.horaires-externes.index', compact(
            'enseignant', 'anneeActive', 'imports', 'slots', 'jours'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    // Étape 1 : Upload du fichier
    // ──────────────────────────────────────────────────────────────

    public function upload(Request $request, Enseignant $enseignant): RedirectResponse
    {
        $this->gate($enseignant);

        $data = $request->validate([
            'fichier'           => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'source_type'       => 'required|in:photo,image,scan,pdf',
            'annee_scolaire_id' => 'nullable|integer|exists:annees_scolaires,id',
        ]);

        $file = $request->file('fichier');
        $path = $file->store('edt/horaires-externes', 'public');

        $import = EnseignantHoraireImport::create([
            'enseignant_id'     => $enseignant->id,
            'annee_scolaire_id' => $data['annee_scolaire_id'] ?? null,
            'source_type'       => $data['source_type'],
            'fichier_path'      => $path,
            'original_filename' => $file->getClientOriginalName(),
            'statut'            => 'uploade',
            'created_by'        => Auth::id(),
        ]);

        return redirect()
            ->route('emploi-du-temps.horaires-externes.index', $enseignant)
            ->with('success', "Fichier uploadé. Lancez l'analyse IA pour extraire les créneaux.");
    }

    // ──────────────────────────────────────────────────────────────
    // Étape 2 : Analyse OCR par OpenAI
    // ──────────────────────────────────────────────────────────────

    public function analyser(Request $request, Enseignant $enseignant, EnseignantHoraireImport $import): RedirectResponse
    {
        $this->gate($enseignant);
        abort_if($import->enseignant_id !== $enseignant->id, 403);

        if (!$import->fichier_path || !Storage::disk('public')->exists($import->fichier_path)) {
            return back()->with('error', 'Fichier introuvable. Veuillez réuploader le document.');
        }

        try {
            $payload = $this->ocrService->extractFromStoredFile('public', $import->fichier_path);

            $import->update([
                'statut'                => 'analyse',
                'payload_ocr_json'      => $payload,
                'etablissement_detecte' => $payload['etablissement'] ?? null,
                'professeur_detecte'    => $payload['teacher_name'] ?? null,
                'confidence_score'      => $payload['confidence_score'] ?? 0,
                'notes_ocr'             => $payload['source_notes'] ?? null,
            ]);

            $nbSlots = count($payload['slots'] ?? []);

            return redirect()
                ->route('emploi-du-temps.horaires-externes.index', $enseignant)
                ->with('success', "Analyse IA terminée : {$nbSlots} créneau(x) extrait(s). Vérifiez et validez.");
        } catch (\Throwable $e) {
            $import->update(['statut' => 'erreur', 'notes_ocr' => $e->getMessage()]);

            return back()->with('error', "Erreur OCR : {$e->getMessage()}");
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Étape 3 : Validation des créneaux extraits
    // ──────────────────────────────────────────────────────────────

    public function valider(Request $request, Enseignant $enseignant, EnseignantHoraireImport $import): RedirectResponse
    {
        $this->gate($enseignant);
        abort_if($import->enseignant_id !== $enseignant->id, 403);
        abort_unless($import->estAnalyse(), 422, 'L\'import doit d\'abord être analysé par l\'IA.');

        $data = $request->validate([
            'slots'               => 'required|array|min:1',
            'slots.*.jour'        => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'slots.*.heure_debut' => 'required|date_format:H:i',
            'slots.*.heure_fin'   => 'required|date_format:H:i|after:slots.*.heure_debut',
            'slots.*.commentaire' => 'nullable|string|max:255',
            'etablissement'       => 'nullable|string|max:200',
        ]);

        $etablissement = $data['etablissement']
            ?? $import->etablissement_detecte
            ?? 'Autre établissement';

        DB::transaction(function () use ($import, $data, $etablissement, $enseignant) {
            // Supprime les anciens créneaux de cet import
            EnseignantHoraireExterne::where('import_id', $import->id)->delete();

            foreach ($data['slots'] as $slot) {
                EnseignantHoraireExterne::create([
                    'enseignant_id'         => $enseignant->id,
                    'annee_scolaire_id'     => $import->annee_scolaire_id,
                    'etablissement_externe' => $etablissement,
                    'jour'                  => $slot['jour'],
                    'heure_debut'           => $slot['heure_debut'],
                    'heure_fin'             => $slot['heure_fin'],
                    'valide'                => true,
                    'source'                => 'ocr',
                    'commentaire'           => $slot['commentaire'] ?? null,
                    'created_by'            => Auth::id(),
                    'import_id'             => $import->id,
                ]);
            }

            $import->update([
                'statut'       => 'valide',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
            ]);
        });

        $nb = count($data['slots']);

        return redirect()
            ->route('emploi-du-temps.horaires-externes.index', $enseignant)
            ->with('success', "{$nb} créneau(x) validé(s) et actif(s). L'IA les utilisera lors de la prochaine génération.");
    }

    // ──────────────────────────────────────────────────────────────
    // Suppression d'un import + ses créneaux
    // ──────────────────────────────────────────────────────────────

    public function destroyImport(Enseignant $enseignant, EnseignantHoraireImport $import): RedirectResponse
    {
        $this->gate($enseignant);
        abort_if($import->enseignant_id !== $enseignant->id, 403);

        DB::transaction(function () use ($import) {
            EnseignantHoraireExterne::where('import_id', $import->id)->delete();

            if ($import->fichier_path && Storage::disk('public')->exists($import->fichier_path)) {
                Storage::disk('public')->delete($import->fichier_path);
            }

            $import->delete();
        });

        return back()->with('success', 'Import et créneaux associés supprimés.');
    }

    // ──────────────────────────────────────────────────────────────
    // Toggle actif/inactif d'un créneau validé
    // ──────────────────────────────────────────────────────────────

    public function toggleValide(Enseignant $enseignant, EnseignantHoraireExterne $slot): RedirectResponse
    {
        $this->gate($enseignant);
        abort_if($slot->enseignant_id !== $enseignant->id, 403);

        $slot->update(['valide' => !$slot->valide]);

        $msg = $slot->valide
            ? 'Créneau activé — l\'IA le bloquera lors de la prochaine génération.'
            : 'Créneau désactivé — l\'IA l\'ignorera.';

        return back()->with('success', $msg);
    }

    // ──────────────────────────────────────────────────────────────
    // Suppression d'un créneau individuel
    // ──────────────────────────────────────────────────────────────

    public function destroy(Enseignant $enseignant, EnseignantHoraireExterne $slot): RedirectResponse
    {
        $this->gate($enseignant);
        abort_if($slot->enseignant_id !== $enseignant->id, 403);

        $slot->delete();

        return back()->with('success', 'Créneau supprimé.');
    }

    // ──────────────────────────────────────────────────────────────

    private function gate(Enseignant $enseignant): void
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            return;
        }

        abort_if(
            (int) $user->etablissement_id !== (int) $enseignant->etablissement_id,
            403
        );
    }
}
