<?php
// ══════════════════════════════════════════════════════════════
// app/Http/Controllers/Api/NoteController.php
// MODULE 5 — NOTES, BULLETINS ET ÉVALUATIONS
// ══════════════════════════════════════════════════════════════
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Evaluation, Note, Classe, MoyenneGenerale, MoyenneMatiere, Inscription};
use App\Services\Mobile\ApiSyncDedupService;
use Illuminate\Http\{JsonResponse, Request};

class NoteController extends Controller
{
    public function evaluationsParClasse(Request $request, Classe $classe): JsonResponse
    {
        $trimestre_id = $request->get('trimestre_id');
        $evaluations = Evaluation::where('classe_id', $classe->id)
            ->when($trimestre_id, fn($q) => $q->where('trimestre_id', $trimestre_id))
            ->with(['matiere:id,nom,code', 'enseignant:id,nom,prenom', 'notes'])
            ->latest('date_evaluation')->get();

        return response()->json($evaluations);
    }

    public function creerEvaluation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'trimestre_id' => 'required|exists:trimestres,id',
            'type_evaluation_id' => 'required|exists:types_evaluation,id',
            'titre' => 'required|string|max:200',
            'date_evaluation' => 'required|date',
            'note_sur' => 'required|numeric|min:1|max:100',
            'coefficient' => 'required|numeric|min:0.5|max:10',
        ]);

        $enseignant = $request->user()->enseignantActif();
        if (!$enseignant) {
            return response()->json(['error' => 'Profil enseignant non disponible pour cet établissement.'], 403);
        }

        $evaluation = Evaluation::create([
            ...$validated,
            'etablissement_id' => $request->user()->ecoleActiveId(),
            'enseignant_id' => $enseignant->id,
            'statut' => 'en_saisie',
        ]);

        return response()->json($evaluation, 201);
    }

    public function saisirNotes(Request $request, Evaluation $evaluation): JsonResponse
    {
        $request->validate([
            'notes' => 'required|array',
            'notes.*.eleve_id' => 'required|exists:eleves,id',
            'notes.*.note' => 'nullable|numeric|min:0|max:' . $evaluation->note_sur,
            'notes.*.absent' => 'nullable|boolean',
            'client_mutation_id' => 'nullable|string|max:64',
        ]);

        if ($replay = ApiSyncDedupService::replayIfExists($request, 'notes_saisie')) {
            return response()->json($replay);
        }

        $enseignant = $request->user()->enseignantActif();
        if (!$enseignant || (int) $evaluation->enseignant_id !== (int) $enseignant->id) {
            return response()->json(['error' => 'Non autorisé pour cette évaluation.'], 403);
        }

        foreach ($request->notes as $noteData) {
            Note::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'eleve_id' => $noteData['eleve_id']],
                [
                    'note' => $noteData['absent'] ?? false ? null : $noteData['note'],
                    'absent' => $noteData['absent'] ?? false,
                    'saisie_par' => $request->user()->id,
                    'date_saisie' => now(),
                ]
            );
        }

        $payload = [
            'message' => count($request->notes) . ' notes enregistrées.',
            'moyenne_classe' => $evaluation->fresh()->moyenneClasse(),
        ];

        ApiSyncDedupService::store($request, 'notes_saisie', $evaluation->id, $payload);

        return response()->json($payload);
    }

    public function calculerMoyennes(Request $request, Classe $classe): JsonResponse
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);
        $trimestreId = $request->trimestre_id;

        $inscriptions = Inscription::where('classe_id', $classe->id)->where('statut', 'validee')->with('eleve')->get();
        $resultats = [];

        foreach ($inscriptions as $inscription) {
            $eleve = $inscription->eleve;

            // Récupérer toutes les notes de l'élève pour ce trimestre
            $notes = Note::whereHas('evaluation', fn($q) => $q->where('classe_id', $classe->id)->where('trimestre_id', $trimestreId))
                ->where('eleve_id', $eleve->id)
                ->whereNotNull('note')
                ->with('evaluation.matiere')
                ->get();

            // Calculer les moyennes par matière
            $totalPoints = 0;
            $totalCoeff = 0;

            $notesByMatiere = $notes->groupBy('evaluation.matiere_id');
            foreach ($notesByMatiere as $matiereId => $notesMatiere) {
                $moyenneMatiere = $notesMatiere->avg(fn($n) => $n->noteSur20());
                $coeff = $notesMatiere->first()->evaluation->matiere->coefficient_defaut;
                $totalPoints += $moyenneMatiere * $coeff;
                $totalCoeff += $coeff;
            }

            $moyenneGenerale = $totalCoeff > 0 ? round($totalPoints / $totalCoeff, 2) : null;

            MoyenneGenerale::updateOrCreate(
                ['eleve_id' => $eleve->id, 'trimestre_id' => $trimestreId],
                [
                    'classe_id' => $classe->id,
                    'annee_scolaire_id' => $inscription->annee_scolaire_id,
                    'moyenne_generale' => $moyenneGenerale,
                    'total_points' => round($totalPoints, 2),
                    'total_coefficients' => $totalCoeff,
                    'effectif_classe' => $inscriptions->count(),
                ]
            );

            $resultats[] = ['eleve' => $eleve->nom_complet, 'moyenne' => $moyenneGenerale];
        }

        // Calculer les rangs
        $moyennes = MoyenneGenerale::where('classe_id', $classe->id)->where('trimestre_id', $trimestreId)
            ->orderByDesc('moyenne_generale')->get();
        $rang = 1;
        foreach ($moyennes as $moy) {
            $moy->update([
                'rang' => $rang++,
                'moyenne_premier' => $moyennes->first()->moyenne_generale,
                'moyenne_dernier' => $moyennes->last()->moyenne_generale,
                'moyenne_classe' => round($moyennes->avg('moyenne_generale'), 2),
            ]);
        }

        // Mentions automatiques
        foreach ($moyennes as $moy) {
            $mention = 'aucune';
            if ($moy->moyenne_generale >= 16) $mention = 'felicitations';
            elseif ($moy->moyenne_generale >= 14) $mention = 'tableau_honneur';
            elseif ($moy->moyenne_generale >= 12) $mention = 'encouragements';
            elseif ($moy->moyenne_generale < 8) $mention = 'avertissement';
            $moy->update(['mention' => $mention]);
        }

        return response()->json([
            'message' => "Moyennes calculées pour {$classe->nom}.",
            'effectif' => $inscriptions->count(),
            'moyenne_classe' => round($moyennes->avg('moyenne_generale'), 2),
            'resultats' => collect($resultats)->sortByDesc('moyenne')->values(),
        ]);
    }

    public function bulletinEleve(Request $request, $eleveId): JsonResponse
    {
        $request->validate(['trimestre_id' => 'required|exists:trimestres,id']);

        $moyenneGenerale = MoyenneGenerale::where('eleve_id', $eleveId)
            ->where('trimestre_id', $request->trimestre_id)
            ->first();

        $moyennesMatieres = MoyenneMatiere::where('eleve_id', $eleveId)
            ->where('trimestre_id', $request->trimestre_id)
            ->with('matiere:id,nom,code,coefficient_defaut')
            ->get();

        return response()->json([
            'eleve' => \App\Models\Eleve::select('id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps', 'date_naissance', 'sexe')->find($eleveId),
            'moyenne_generale' => $moyenneGenerale,
            'matieres' => $moyennesMatieres,
        ]);
    }
}
