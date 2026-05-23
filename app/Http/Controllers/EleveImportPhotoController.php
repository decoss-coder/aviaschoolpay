<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\EleveImportJob;
use App\Services\EleveParserService;
use App\Services\OcrImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EleveImportPhotoController extends Controller
{
    public function __construct(
        private OcrImportService $ocrService,
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
        $ocrActif = !empty(config('services.openai.api_key'));

        return view('eleves.import.photo', compact('classes', 'annee', 'classePreselect', 'ocrActif'));
    }

    public function upload(Request $request)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', 180);
        @ini_set('memory_limit', '256M');

        $validated = $request->validate([
            'fichier' => ['required', 'file', 'max:' . (OcrImportService::TAILLE_MAX_MO * 1024), 'mimes:jpg,jpeg,png,webp,heic,heif'],
            'classe_cible_id' => ['nullable', 'integer', 'exists:classes,id'],
        ]);

        $fichier = $request->file('fichier');
        $etab = $request->user()->etablissement;

        $job = EleveImportJob::create([
            'etablissement_id' => $etab->id,
            'user_id' => $request->user()->id,
            'classe_cible_id' => $validated['classe_cible_id'] ?? null,
            'source' => 'photo_ocr',
            'fichier_original' => $fichier->getClientOriginalName(),
            'fichier_taille' => $fichier->getSize(),
            'statut' => 'parsing',
            'started_at' => now(),
            'message_progression' => 'Analyse en cours...',
        ]);

        try {
            $extension = strtolower($fichier->getClientOriginalExtension());
            $path = $fichier->storeAs(
                'imports/' . $etab->id,
                'job_' . $job->id . '.' . $extension,
                'local'
            );

            $job->update(['fichier_path' => $path]);

            $extraction = $this->ocrService->extraire($fichier);

            if (empty($extraction['lignes'])) {
                throw new \Exception("L'IA n'a détecté aucun élève sur la photo.");
            }

            $resultat = $this->parser->normaliserLot($extraction['lignes'], $etab->id);

            $ocrMap = [];
            foreach ($extraction['lignes'] as $ligneOcr) {
                $key = $this->buildImportFingerprint(
                    $ligneOcr['matricule'] ?? '',
                    $ligneOcr['nom_complet'] ?? '',
                    $ligneOcr['sexe'] ?? ''
                );

                $ocrMap[$key] = [
                    'raw_statut' => $ligneOcr['raw_statut'] ?? '',
                    'statut_eleve' => in_array(($ligneOcr['statut_eleve'] ?? null), ['AFF', 'NAFF'], true)
                        ? $ligneOcr['statut_eleve']
                        : '',
                ];
            }

            foreach ($resultat['valides'] as &$ligneNorm) {
                $nomComplet = trim(($ligneNorm['nom'] ?? '') . ' ' . ($ligneNorm['prenom'] ?? ''));
                $key = $this->buildImportFingerprint(
                    $ligneNorm['matricule_desps'] ?? '',
                    $nomComplet,
                    $ligneNorm['sexe'] ?? ''
                );

                $ligneNorm['raw_statut'] = $ocrMap[$key]['raw_statut'] ?? '';
                $ligneNorm['statut_eleve'] = in_array(($ocrMap[$key]['statut_eleve'] ?? null), ['AFF', 'NAFF'], true)
                    ? $ocrMap[$key]['statut_eleve']
                    : '';
            }
            unset($ligneNorm);

            $job->update([
                'statut' => 'preview',
                'donnees_brutes' => $extraction['lignes'],
                'donnees_normalisees' => $resultat['valides'],
                'erreurs' => $resultat['erreurs'],
                'metadonnees' => array_merge($extraction['meta'], [
                    'avertissements_ocr' => $extraction['avertissements'],
                    'cout_usd' => $extraction['cout_usd'],
                ]),
                'total_lignes' => $resultat['stats']['total'],
                'lignes_valides' => $resultat['stats']['valides'],
                'lignes_erreur' => $resultat['stats']['erreurs'],
            ]);

            $message = "Photo analysée : {$resultat['stats']['valides']} élève(s) détecté(s)";
            if ($resultat['stats']['erreurs'] > 0) {
                $message .= ", {$resultat['stats']['erreurs']} à corriger";
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
            Log::error('[Import Photo] Échec', [
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

            $message = 'Échec de l\'analyse OCR : ' . $e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->withInput()->with('error', $message);
        }
    }

    private function buildImportFingerprint(?string $matricule, ?string $nomComplet, ?string $sexe): string
    {
        $m = mb_strtoupper(trim((string) $matricule));
        $n = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $nomComplet)));
        $s = mb_strtoupper(trim((string) $sexe));

        return md5($m . '|' . $n . '|' . $s);
    }

    public function diagnostic(Request $request)
    {
        $tests = [];
        $apiKey = config('services.openai.api_key');

        $tests[] = [
            'nom' => 'Clé API OpenAI présente',
            'ok' => !empty($apiKey),
            'details' => $apiKey ? 'Clé : sk-...' . substr($apiKey, -8) : 'AUCUNE CLÉ',
        ];

        return view('eleves.import.photo-diagnostic', compact('tests'));
    }
}