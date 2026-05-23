<?php

namespace App\Http\Controllers;

use App\Models\EleveImportJob;
use App\Services\EleveParserService;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controller dédié à l'import Excel/CSV.
 *
 * Workflow :
 * 1. showForm() → affiche le formulaire d'upload
 * 2. upload() → reçoit le fichier, extrait les données, normalise, crée le job,
 *              redirige vers le preview général (EleveImportController@preview)
 */
class EleveImportExcelController extends Controller
{
    public function __construct(
        private ExcelImportService $excelService,
        private EleveParserService $parser,
    ) {}

    /**
     * Affiche le formulaire d'upload Excel/CSV
     */
    public function showForm(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $classes = $annee
            ? \App\Models\Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau')->orderBy('nom')->get()
            : collect();

        $classePreselect = $request->input('classe_id');

        return view('eleves.import.excel', compact('classes', 'annee', 'classePreselect'));
    }

    /**
     * Reçoit le fichier, extrait, normalise, crée le job
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'fichier' => ['required', 'file', 'max:' . (ExcelImportService::TAILLE_MAX_MO * 1024), 'mimes:xlsx,xls,csv,ods,txt'],
            'classe_cible_id' => ['nullable', 'integer', 'exists:classes,id'],
        ], [
            'fichier.required' => 'Merci de sélectionner un fichier.',
            'fichier.file' => 'Le fichier est invalide.',
            'fichier.max' => 'Le fichier dépasse la taille maximale autorisée (' . ExcelImportService::TAILLE_MAX_MO . ' Mo).',
            'fichier.mimes' => 'Format non supporté. Formats acceptés : Excel (.xlsx, .xls) ou CSV (.csv).',
        ]);

        $fichier = $request->file('fichier');
        $etab = $request->user()->etablissement;

        // Création du job en statut 'parsing'
        $job = EleveImportJob::create([
            'etablissement_id' => $etab->id,
            'user_id' => $request->user()->id,
            'classe_cible_id' => $validated['classe_cible_id'] ?? null,
            'source' => strtolower($fichier->getClientOriginalExtension()) === 'csv' ? 'csv' : 'excel',
            'fichier_original' => $fichier->getClientOriginalName(),
            'fichier_taille' => $fichier->getSize(),
            'statut' => 'parsing',
            'started_at' => now(),
        ]);

        try {
            // Stocker le fichier
            $path = $fichier->storeAs(
                'imports/' . $etab->id,
                'job_' . $job->id . '.' . $fichier->getClientOriginalExtension(),
                'local'
            );
            $job->update(['fichier_path' => $path]);

            // Extraction des données brutes
            $extraction = $this->excelService->extraire($fichier);

            if (empty($extraction['lignes'])) {
                throw new \Exception(
                    "Aucune ligne de données détectée dans le fichier. "
                    . "Vérifiez qu'il contient bien des élèves sous la ligne d'en-tête."
                );
            }

            // Normalisation + validation
            $resultat = $this->parser->normaliserLot($extraction['lignes'], $etab->id);

            // Mise à jour du job avec les données extraites
            $job->update([
                'statut' => 'preview',
                'donnees_brutes' => $extraction['lignes'],
                'donnees_normalisees' => $resultat['valides'],
                'erreurs' => $resultat['erreurs'],
                'metadonnees' => [
                    'headers_detectes' => $extraction['headers_detectes'],
                    'colonnes_non_mappees' => $extraction['colonnes_non_mappees'],
                    'ligne_headers' => $extraction['meta']['ligne_headers'],
                    'format' => $extraction['meta']['format'],
                ],
                'total_lignes' => $resultat['stats']['total'],
                'lignes_valides' => $resultat['stats']['valides'],
                'lignes_erreur' => $resultat['stats']['erreurs'],
            ]);

            // Redirection vers le preview
            return redirect()->route('eleves.import.preview', $job)
                ->with('success',
                    "Fichier analysé : {$resultat['stats']['valides']} ligne(s) prête(s), "
                    . "{$resultat['stats']['erreurs']} ligne(s) en erreur. Vérifiez avant de valider."
                );

        } catch (\Exception $e) {
            $job->update([
                'statut' => 'failed',
                'message_progression' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            // Supprimer le fichier uploadé en cas d'échec
            if ($job->fichier_path && Storage::disk('local')->exists($job->fichier_path)) {
                Storage::disk('local')->delete($job->fichier_path);
            }

            return back()
                ->withInput()
                ->with('error', 'Échec de l\'import : ' . $e->getMessage());
        }
    }
}