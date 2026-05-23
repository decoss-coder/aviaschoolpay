<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\EleveImportJob;
use App\Services\EleveParserService;
use Illuminate\Http\Request;

/**
 * Controller dédié à la saisie rapide.
 *
 * Workflow :
 * 1. showForm() → affiche le tableau éditable vide avec classe cible
 * 2. submit() → reçoit les lignes saisies, normalise, crée un job,
 *              redirige vers le preview standard
 *
 * Contrairement à l'import Excel où on extrait un fichier, ici les
 * données viennent directement du tableau JSON en frontend.
 */
class EleveImportSaisieController extends Controller
{
    public function __construct(private EleveParserService $parser) {}

    /**
     * Affiche le tableau éditable
     */
    public function showForm(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau')->orderBy('nom')->get()
            : collect();

        $classePreselect = $request->input('classe_id');

        return view('eleves.import.saisie', compact('classes', 'annee', 'classePreselect'));
    }

    /**
     * Reçoit les lignes saisies (POST classique avec tableau lignes[])
     * Crée un job et redirige vers le preview.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'lignes' => ['required', 'array', 'min:1', 'max:500'],
            'lignes.*.matricule' => ['nullable', 'string', 'max:20'],
            'lignes.*.nom_complet' => ['required', 'string', 'max:200'],
            'lignes.*.sexe' => ['required', 'in:M,F'],
            'lignes.*.date_naissance' => ['nullable', 'date'],
            'classe_cible_id' => ['nullable', 'integer', 'exists:classes,id'],
        ], [
            'lignes.required' => 'Vous devez saisir au moins un élève.',
            'lignes.max' => 'Maximum 500 élèves par saisie. Utilisez l\'import Excel pour plus.',
            'lignes.*.nom_complet.required' => 'Une ligne sans nom a été détectée.',
            'lignes.*.sexe.required' => 'Le sexe doit être renseigné pour chaque élève.',
            'lignes.*.sexe.in' => 'Le sexe doit être M ou F.',
        ]);

        $etab = $request->user()->etablissement;

        // Création du job
        $job = EleveImportJob::create([
            'etablissement_id' => $etab->id,
            'user_id' => $request->user()->id,
            'classe_cible_id' => $validated['classe_cible_id'] ?? null,
            'source' => 'saisie_rapide',
            'statut' => 'parsing',
            'started_at' => now(),
        ]);

        try {
            // Normalisation via le parser central
            $resultat = $this->parser->normaliserLot($validated['lignes'], $etab->id);

            $job->update([
                'statut' => 'preview',
                'donnees_brutes' => $validated['lignes'],
                'donnees_normalisees' => $resultat['valides'],
                'erreurs' => $resultat['erreurs'],
                'total_lignes' => $resultat['stats']['total'],
                'lignes_valides' => $resultat['stats']['valides'],
                'lignes_erreur' => $resultat['stats']['erreurs'],
                'metadonnees' => [
                    'nb_lignes_saisies' => count($validated['lignes']),
                ],
            ]);

            return redirect()->route('eleves.import.preview', $job)
                ->with('success',
                    "{$resultat['stats']['valides']} ligne(s) prête(s) à être importée(s)"
                    . ($resultat['stats']['erreurs'] > 0 ? ", {$resultat['stats']['erreurs']} ligne(s) en erreur." : '.')
                );

        } catch (\Exception $e) {
            $job->update([
                'statut' => 'failed',
                'message_progression' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return back()->withInput()
                ->with('error', 'Erreur lors de la saisie : ' . $e->getMessage());
        }
    }
}