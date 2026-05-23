<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{RemonteeSigfne, RemonteeEleve, Eleve, MoyenneGenerale, Trimestre, DecisionFinAnnee, Inscription};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class SigfneController extends Controller
{
    /**
     * PRÉPARER LA REMONTÉE DES MOYENNES TRIMESTRIELLES
     */
    public function preparerRemontee(Request $request): JsonResponse
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);

        $etab = $request->user()->etablissement;
        $trimestre = Trimestre::findOrFail($request->trimestre_id);
        $anneeId = $trimestre->annee_scolaire_id;

        // Récupérer tous les élèves inscrits avec leurs moyennes
        $inscriptions = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with(['eleve:id,nom,prenom,matricule_desps,matricule_interne'])
            ->get();

        $totalEleves = $inscriptions->count();
        $sansMatricule = 0;
        $sansMoyenne = 0;
        $prets = 0;
        $details = [];

        foreach ($inscriptions as $insc) {
            $eleve = $insc->eleve;
            $moyenne = MoyenneGenerale::where('eleve_id', $eleve->id)->where('trimestre_id', $trimestre->id)->first();

            $erreurs = [];
            if (!$eleve->matricule_desps) { $sansMatricule++; $erreurs[] = 'Matricule DESPS manquant'; }
            if (!$moyenne || $moyenne->moyenne_generale === null) { $sansMoyenne++; $erreurs[] = 'Moyenne non calculée'; }
            if (empty($erreurs)) $prets++;

            $details[] = [
                'eleve_id' => $eleve->id,
                'nom_complet' => $eleve->nom_complet,
                'matricule_interne' => $eleve->matricule_interne,
                'matricule_desps' => $eleve->matricule_desps,
                'moyenne' => $moyenne?->moyenne_generale,
                'rang' => $moyenne?->rang,
                'pret' => empty($erreurs),
                'erreurs' => $erreurs,
            ];
        }

        return response()->json([
            'etablissement' => $etab->nom,
            'code_desps' => $etab->code_desps,
            'trimestre' => $trimestre->libelle,
            'date_limite_remontee' => $trimestre->date_remontee_desps?->format('d/m/Y'),
            'resume' => [
                'total_eleves' => $totalEleves,
                'prets_pour_remontee' => $prets,
                'sans_matricule_desps' => $sansMatricule,
                'sans_moyenne' => $sansMoyenne,
                'taux_preparation' => $totalEleves > 0 ? round(($prets / $totalEleves) * 100, 1) : 0,
            ],
            'eleves' => $details,
            'peut_envoyer' => $sansMatricule === 0 && $sansMoyenne === 0,
        ]);
    }

    /**
     * EXÉCUTER LA REMONTÉE VERS LE SIGFNE
     */
    public function executerRemontee(Request $request): JsonResponse
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);

        $etab = $request->user()->etablissement;
        $trimestre = Trimestre::findOrFail($request->trimestre_id);
        $anneeId = $trimestre->annee_scolaire_id;
        $plateforme = in_array($etab->type, ['primaire', 'prescolaire']) ? 'agcp' : 'agfne';

        // Créer l'enregistrement de remontée
        $remontee = RemonteeSigfne::create([
            'etablissement_id' => $etab->id,
            'trimestre_id' => $trimestre->id,
            'annee_scolaire_id' => $anneeId,
            'plateforme' => $plateforme,
            'type' => 'moyennes_trimestrielles',
            'statut' => 'en_cours',
            'envoye_par' => $request->user()->id,
            'date_envoi' => now(),
        ]);

        $inscriptions = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with('eleve')->get();

        $remontes = 0;
        $erreurs = 0;
        $sansMatricule = 0;
        $erreursDetail = [];

        foreach ($inscriptions as $insc) {
            $eleve = $insc->eleve;
            $moyenne = MoyenneGenerale::where('eleve_id', $eleve->id)
                ->where('trimestre_id', $trimestre->id)->first();

            $statut = 'ok';
            $messageErreur = null;

            if (!$eleve->matricule_desps) {
                $statut = 'erreur_matricule';
                $messageErreur = 'Matricule DESPS absent';
                $sansMatricule++;
                $erreurs++;
            } elseif (!$moyenne || $moyenne->moyenne_generale === null) {
                $statut = 'erreur_moyenne';
                $messageErreur = 'Moyenne non calculée';
                $erreurs++;
            } else {
                $remontes++;
            }

            RemonteeEleve::create([
                'remontee_sigfne_id' => $remontee->id,
                'eleve_id' => $eleve->id,
                'matricule_desps' => $eleve->matricule_desps,
                'moyenne_remontee' => $moyenne?->moyenne_generale,
                'statut' => $statut,
                'message_erreur' => $messageErreur,
            ]);

            if ($messageErreur) {
                $erreursDetail[] = "{$eleve->nom_complet}: $messageErreur";
            }
        }

        $remontee->update([
            'total_eleves' => $inscriptions->count(),
            'eleves_remontes' => $remontes,
            'eleves_en_erreur' => $erreurs,
            'eleves_sans_matricule' => $sansMatricule,
            'statut' => $erreurs === 0 ? 'termine' : 'erreur',
            'erreurs_detail' => $erreursDetail,
        ]);

        // Mettre à jour le trimestre
        if ($erreurs === 0) {
            $trimestre->update(['moyennes_remontees' => true]);
        }

        return response()->json([
            'success' => $erreurs === 0,
            'remontee_id' => $remontee->id,
            'plateforme' => strtoupper($plateforme),
            'total_eleves' => $inscriptions->count(),
            'remontes' => $remontes,
            'erreurs' => $erreurs,
            'message' => $erreurs === 0
                ? "Remontée terminée avec succès. {$remontes} moyennes envoyées vers {$plateforme}."
                : "Remontée partielle. {$remontes} réussies, {$erreurs} erreurs.",
            'erreurs_detail' => $erreursDetail,
        ]);
    }

    /**
     * EXPORTER LE FICHIER CSV FORMAT AGFNE (mode secours)
     */
    public function exporterCSV(Request $request): JsonResponse
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);

        $etab = $request->user()->etablissement;
        $trimestre = Trimestre::findOrFail($request->trimestre_id);

        $lignes = DB::table('inscriptions as i')
            ->join('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->join('classes as c', 'c.id', '=', 'i.classe_id')
            ->leftJoin('moyennes_generales as mg', function ($join) use ($trimestre) {
                $join->on('mg.eleve_id', '=', 'e.id')->where('mg.trimestre_id', $trimestre->id);
            })
            ->where('i.etablissement_id', $etab->id)
            ->where('i.annee_scolaire_id', $trimestre->annee_scolaire_id)
            ->where('i.statut', 'validee')
            ->select('e.matricule_desps', 'e.nom', 'e.prenom', 'e.sexe', 'e.date_naissance', 'c.nom as classe', 'mg.moyenne_generale', 'mg.rang')
            ->orderBy('c.nom')->orderByDesc('mg.moyenne_generale')
            ->get();

        return response()->json([
            'etablissement' => $etab->nom,
            'code_desps' => $etab->code_desps,
            'trimestre' => $trimestre->libelle,
            'total_lignes' => $lignes->count(),
            'format' => 'CSV compatible AGFNE',
            'colonnes' => ['matricule_desps', 'nom', 'prenom', 'sexe', 'date_naissance', 'classe', 'moyenne_generale', 'rang'],
            'donnees' => $lignes,
        ]);
    }

    /**
     * HISTORIQUE DES REMONTÉES
     */
    public function historiqueRemontees(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $remontees = RemonteeSigfne::where('etablissement_id', $etab)
            ->with(['trimestre:id,libelle,numero', 'anneeScolaire:id,libelle', 'envoyePar:id,nom,prenom'])
            ->latest()
            ->paginate(20);

        return response()->json($remontees);
    }

    /**
     * DÉCISIONS DE FIN D'ANNÉE (DFA)
     */
    public function genererDFA(Request $request): JsonResponse
    {
        $request->validate(['annee_scolaire_id' => 'required|exists:annees_scolaires,id']);

        $etab = $request->user()->etablissement;
        $anneeId = $request->annee_scolaire_id;

        $inscriptions = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $anneeId)
            ->where('statut', 'validee')
            ->with(['eleve', 'classe.niveau'])
            ->get();

        $decisions = [];
        foreach ($inscriptions as $insc) {
            $moyAnnuelle = \App\Models\MoyenneAnnuelle::where('eleve_id', $insc->eleve_id)
                ->where('annee_scolaire_id', $anneeId)->first();

            $moy = $moyAnnuelle?->moyenne_annuelle ?? 0;

            // Logique de décision automatique
            $decision = 'passage';
            if ($moy < 8) $decision = 'exclusion';
            elseif ($moy < 10) $decision = 'redoublement';

            // Suggestion IA pour l'orientation (en fin de 3ème)
            $suggestionIa = null;
            if ($insc->classe->niveau->code === '3eme' && $decision === 'passage') {
                $suggestionIa = $moy >= 14 ? 'Série C ou D recommandée' : ($moy >= 12 ? 'Série D recommandée' : 'Série A recommandée');
            }

            $dfa = DecisionFinAnnee::updateOrCreate(
                ['eleve_id' => $insc->eleve_id, 'annee_scolaire_id' => $anneeId],
                [
                    'etablissement_id' => $etab->id,
                    'classe_id' => $insc->classe_id,
                    'moyenne_annuelle' => $moy,
                    'decision' => $decision,
                    'suggestion_ia' => $suggestionIa,
                    'statut_validation' => 'proposition',
                ]
            );

            $decisions[] = [
                'eleve' => $insc->eleve->nom_complet,
                'classe' => $insc->classe->nom,
                'moyenne' => $moy,
                'decision' => $decision,
                'suggestion_ia' => $suggestionIa,
            ];
        }

        return response()->json([
            'message' => count($decisions) . ' DFA générées.',
            'passages' => collect($decisions)->where('decision', 'passage')->count(),
            'redoublements' => collect($decisions)->where('decision', 'redoublement')->count(),
            'exclusions' => collect($decisions)->where('decision', 'exclusion')->count(),
            'decisions' => $decisions,
        ]);
    }
}
