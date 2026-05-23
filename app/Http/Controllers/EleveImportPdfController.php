<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\EleveImportJob;
use App\Services\EleveParserService;
use App\Services\PdfImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EleveImportPdfController extends Controller
{
    public function __construct(
        private PdfImportService $pdfService,
        private EleveParserService $parser,
    ) {}

    public function showForm(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau')
                ->orderBy('nom')
                ->get()
            : collect();

        $classePreselect = $request->input('classe_id');
        $pdfActif = $this->pdfService->estDisponible();
        $pdfDiagnostic = $this->pdfService->diagnosticDisponibilite();

        return view('eleves.import.pdf', compact(
            'classes',
            'annee',
            'classePreselect',
            'pdfActif',
            'pdfDiagnostic'
        ));
    }

    public function upload(Request $request)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', 180);
        @ini_set('memory_limit', '256M');

        if (!$this->pdfService->estDisponible()) {
            $message = "L’import PDF n’est pas activé sur ce serveur. Installez smalot/pdfparser ou activez pdftotext.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->withInput()->with('error', $message);
        }

        $validated = $request->validate([
            'fichier' => ['required', 'file', 'max:' . (PdfImportService::TAILLE_MAX_MO * 1024), 'mimes:pdf'],
            'classe_cible_id' => ['nullable', 'integer', 'exists:classes,id'],
        ], [
            'fichier.required' => 'Merci de sélectionner un fichier PDF.',
            'fichier.mimes' => 'Seuls les fichiers PDF sont acceptés.',
            'fichier.max' => 'Le fichier dépasse la taille maximale (' . PdfImportService::TAILLE_MAX_MO . ' Mo).',
        ]);

        $fichier = $request->file('fichier');
        $etab = $request->user()->etablissement;

        $job = EleveImportJob::create([
            'etablissement_id' => $etab->id,
            'user_id' => $request->user()->id,
            'classe_cible_id' => $validated['classe_cible_id'] ?? null,
            'source' => 'pdf',
            'fichier_original' => $fichier->getClientOriginalName(),
            'fichier_taille' => $fichier->getSize(),
            'statut' => 'parsing',
            'started_at' => now(),
            'message_progression' => 'Extraction PDF en cours...',
        ]);

        try {
            $path = $fichier->storeAs(
                'imports/' . $etab->id,
                'job_' . $job->id . '.pdf',
                'local'
            );

            $job->update(['fichier_path' => $path]);

            $extraction = $this->pdfService->extraire($fichier);

            if (empty($extraction['lignes'])) {
                throw new \Exception(
                    "Aucune ligne d'élève détectée dans le PDF. "
                    . "Vérifiez que le document contient un tableau avec les colonnes : "
                    . "Matricule, Nom et Prénoms, Sexe."
                );
            }

            $resultat = $this->parser->normaliserLot($extraction['lignes'], $etab->id);

            $job->update([
                'statut' => 'preview',
                'donnees_brutes' => $extraction['lignes'],
                'donnees_normalisees' => $resultat['valides'],
                'erreurs' => $resultat['erreurs'],
                'metadonnees' => $extraction['meta'],
                'total_lignes' => $resultat['stats']['total'],
                'lignes_valides' => $resultat['stats']['valides'],
                'lignes_erreur' => $resultat['stats']['erreurs'],
                'message_progression' => 'Extraction terminée',
            ]);

            $message = "PDF analysé : {$resultat['stats']['valides']} élève(s) prêt(s)";
            if ($resultat['stats']['erreurs'] > 0) {
                $message .= ", {$resultat['stats']['erreurs']} ligne(s) à corriger";
            }
            if ($extraction['meta']['classe_detectee'] ?? null) {
                $message .= " — Classe détectée : {$extraction['meta']['classe_detectee']}";
            }

            $redirectUrl = route('eleves.import.preview', $job);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => $redirectUrl,
                ]);
            }

            return redirect($redirectUrl)->with('success', $message);

        } catch (\Exception $e) {
            Log::error('[Import PDF] Échec', [
                'job_id' => $job->id,
                'erreur' => $e->getMessage(),
            ]);

            $job->update([
                'statut' => 'failed',
                'message_progression' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            if ($job->fichier_path && Storage::disk('local')->exists($job->fichier_path)) {
                Storage::disk('local')->delete($job->fichier_path);
            }

            $message = 'Échec de l\'import PDF : ' . $e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->withInput()->with('error', $message);
        }
    }
}