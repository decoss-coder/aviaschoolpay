<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\Trimestre;
use App\Models\TypeEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Grille de notes type spreadsheet — enseignant.
 *
 * - Chaque évaluation = une colonne configurable (titre, type, barème, coef).
 * - Cellules de notes éditables, auto-save AJAX.
 * - Bouton "Publier les moyennes" : calcule la moyenne pondérée par élève
 *   et la publie dans moyennes_matieres (visible par la direction).
 * - Support sous-disciplines : si la matière affectée au prof a des sous-disciplines
 *   (ex. Français → CF, OG, EO), des onglets s'affichent pour naviguer entre elles.
 *   La publication d'une sous-discipline recalcule automatiquement la moyenne parent
 *   dès que toutes les sous-disciplines sont publiées.
 */
class GrilleNotesController extends Controller
{
    // ── helpers ────────────────────────────────────────────────────────────

    private function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(!$ens, 403, 'Compte enseignant introuvable.');
        return $ens;
    }

    /**
     * Autorise si le prof enseigne la matière (ou le parent de la matière) dans la classe.
     */
    private function authorizeClasseMatiere(Enseignant $ens, Classe $classe, int $matiereId): void
    {
        $matiere  = Matiere::find($matiereId);
        $checkIds = array_filter([$matiereId, $matiere?->parent_matiere_id]);

        $ok = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->whereIn('matiere_id', $checkIds)
            ->where('active', true)
            ->exists();
        abort_if(!$ok, 403, 'Vous n\'enseignez pas cette matière dans cette classe.');
    }

    // ── Grille principale ──────────────────────────────────────────────────

    public function index(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = \App\Models\Etablissement::find($request->user()->ecoleActiveId());
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        // Matières affectées au prof pour cette classe (top-level uniquement dans le sélecteur)
        $affectations = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get();
        abort_if($affectations->isEmpty(), 403);

        $matieres = $affectations->pluck('matiere')->unique('id')->filter()->values();

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $matiereId   = (int) $request->input('matiere_id', $matieres->first()?->id);
        $trimestreId = (int) $request->input('trimestre_id',
            $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);

        $this->authorizeClasseMatiere($ens, $classe, $matiereId);

        $matiere         = Matiere::with('sousDisciplines')->findOrFail($matiereId);
        $sousDisciplines = $matiere->sousDisciplines; // collection ordonnée par ordre/code

        // Si la matière a des sous-disciplines, on travaille sur la SD sélectionnée
        $sousDisciplineId = null;
        $activeMatiereId  = $matiereId;

        if ($sousDisciplines->isNotEmpty()) {
            $sousDisciplineId = (int) $request->input('sous_discipline_id', $sousDisciplines->first()->id);
            // Vérifier que l'ID appartient bien à cette matière
            if (!$sousDisciplines->contains('id', $sousDisciplineId)) {
                $sousDisciplineId = $sousDisciplines->first()->id;
            }
            $activeMatiereId = $sousDisciplineId;
        }

        $trimestre = $trimestres->firstWhere('id', $trimestreId);

        // Élèves
        $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')->get();

        // Évaluations pour la matière active (SD ou parent)
        $evaluations = Evaluation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $trimestreId)
            ->with('typeEvaluation')
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        // Notes existantes indexées par (eval_id → eleve_id)
        $notes = Note::whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get()
            ->groupBy('evaluation_id')
            ->map(fn ($n) => $n->keyBy('eleve_id'));

        // Moyenne publiée actuelle (SD active ou parent si pas de SD)
        $moyennePubliee = MoyenneMatiere::where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $trimestreId)
            ->where('publie', true)
            ->first();

        // État de publication de chaque sous-discipline (pour indiquer lesquelles sont faites)
        $sdPubliees = collect();
        if ($sousDisciplines->isNotEmpty()) {
            $sdPubliees = MoyenneMatiere::where('classe_id', $classe->id)
                ->whereIn('matiere_id', $sousDisciplines->pluck('id'))
                ->where('trimestre_id', $trimestreId)
                ->where('publie', true)
                ->pluck('matiere_id')
                ->flip(); // set pour lookup O(1)
        }

        // Types d'évaluation pour le sélecteur "Ajouter colonne"
        $typesEval = TypeEvaluation::where('etablissement_id', $etab->id)
            ->where('actif', true)->get();

        return view('mon-espace.grille-notes', compact(
            'ens', 'classe', 'annee', 'matieres', 'matiere', 'matiereId',
            'trimestres', 'trimestre', 'trimestreId', 'eleves', 'evaluations', 'notes',
            'moyennePubliee', 'typesEval',
            'sousDisciplines', 'sousDisciplineId', 'activeMatiereId', 'sdPubliees'
        ));
    }

    // ── Ajouter une colonne (nouvelle évaluation) ──────────────────────────

    public function addColumn(Request $request, Classe $classe): JsonResponse
    {
        $data = $request->validate([
            'matiere_id'         => 'required|exists:matieres,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'titre'              => 'required|string|max:200',
            'type_evaluation_id' => 'required|exists:types_evaluation,id',
            'date_evaluation'    => 'required|date',
            'note_sur'           => 'required|numeric|min:1|max:100',
            'coefficient'        => 'required|numeric|min:0.5|max:10',
        ]);

        $ens  = $this->enseignant($request);
        $etab = \App\Models\Etablissement::find($request->user()->ecoleActiveId());
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $eval = Evaluation::create([
            'etablissement_id'   => $etab->id,
            'classe_id'          => $classe->id,
            'matiere_id'         => $data['matiere_id'], // ID de la SD active
            'enseignant_id'      => $ens->id,
            'trimestre_id'       => $data['trimestre_id'],
            'type_evaluation_id' => $data['type_evaluation_id'],
            'titre'              => $data['titre'],
            'date_evaluation'    => $data['date_evaluation'],
            'note_sur'           => $data['note_sur'],
            'coefficient'        => $data['coefficient'],
            'statut'             => 'en_saisie',
        ]);

        return response()->json([
            'success'    => true,
            'evaluation' => $eval->load('typeEvaluation'),
        ]);
    }

    // ── Modifier une colonne ───────────────────────────────────────────────

    public function updateColumn(Request $request, Evaluation $evaluation): JsonResponse
    {
        $ens = $this->enseignant($request);
        abort_if($evaluation->enseignant_id !== $ens->id, 403);

        $data = $request->validate([
            'titre'              => 'required|string|max:200',
            'type_evaluation_id' => 'required|exists:types_evaluation,id',
            'date_evaluation'    => 'required|date',
            'note_sur'           => 'required|numeric|min:1|max:100',
            'coefficient'        => 'required|numeric|min:0.5|max:10',
        ]);

        $evaluation->update($data);

        return response()->json(['success' => true]);
    }

    // ── Supprimer une colonne ──────────────────────────────────────────────

    public function deleteColumn(Request $request, Evaluation $evaluation): JsonResponse
    {
        $ens = $this->enseignant($request);
        abort_if($evaluation->enseignant_id !== $ens->id, 403);

        $publiee = MoyenneMatiere::where('classe_id', $evaluation->classe_id)
            ->where('matiere_id', $evaluation->matiere_id)
            ->where('trimestre_id', $evaluation->trimestre_id)
            ->where('publie', true)->exists();
        if ($publiee) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible : les moyennes de cette matière sont déjà publiées. Dépubliez d\'abord.',
            ], 422);
        }

        $evaluation->notes()->delete();
        $evaluation->delete();

        return response()->json(['success' => true]);
    }

    // ── Sauvegarder une note (AJAX) ────────────────────────────────────────

    public function saveNote(Request $request, Evaluation $evaluation): JsonResponse
    {
        $data = $request->validate([
            'eleve_id' => 'required|integer|exists:eleves,id',
            'note'     => 'nullable|string|max:10',
        ]);

        $ens = $this->enseignant($request);
        abort_if($evaluation->enseignant_id !== $ens->id, 403);

        $eleveOk = Eleve::where('id', $data['eleve_id'])
            ->where('classe_id', $evaluation->classe_id)->where('actif', true)->exists();
        abort_if(!$eleveOk, 422);

        $raw   = trim((string) ($data['note'] ?? ''));
        $upper = strtoupper($raw);

        $absent   = in_array($upper, ['ABS', 'A', 'ABSENT']);
        $dispense = in_array($upper, ['DISP', 'D', 'DISPENSE', 'DISPENSÉ']);
        $note     = (!$absent && !$dispense && $raw !== '' && is_numeric(str_replace(',', '.', $raw)))
            ? (float) str_replace(',', '.', $raw)
            : null;

        if ($raw === '' && !$absent && !$dispense) {
            Note::where('evaluation_id', $evaluation->id)
                ->where('eleve_id', $data['eleve_id'])->delete();
            return response()->json(['success' => true, 'cleared' => true]);
        }

        if ($note !== null) {
            $bareme = (float) $evaluation->note_sur;
            if ($note > $bareme || $note < 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Note invalide : doit être comprise entre 0 et {$bareme}.",
                ], 422);
            }
        }

        Note::updateOrCreate(
            ['evaluation_id' => $evaluation->id, 'eleve_id' => $data['eleve_id']],
            [
                'note'        => $note,
                'absent'      => $absent,
                'dispense'    => $dispense,
                'saisie_par'  => $request->user()->id,
                'date_saisie' => now(),
            ]
        );

        return response()->json([
            'success'  => true,
            'note'     => $note,
            'absent'   => $absent,
            'dispense' => $dispense,
        ]);
    }

    // ── Publier les moyennes ───────────────────────────────────────────────

    public function publish(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id'         => 'required|exists:matieres,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'sous_discipline_id' => 'nullable|exists:matieres,id',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        // Matière active : la sous-discipline si précisée, sinon la matière parent
        $activeMatiereId = $data['sous_discipline_id'] ?? $data['matiere_id'];

        $evaluations = Evaluation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $data['trimestre_id'])
            ->get();

        if ($evaluations->isEmpty()) {
            return back()->withErrors(['publish' => 'Aucune évaluation à publier pour cette sous-discipline.']);
        }

        $eleves   = Eleve::where('classe_id', $classe->id)->where('actif', true)->get();
        $count    = 0;
        $moyennes = [];

        DB::transaction(function () use ($evaluations, $eleves, $classe, $data, $ens, $request, $activeMatiereId, &$count, &$moyennes) {
            Evaluation::whereIn('id', $evaluations->pluck('id'))
                ->update(['notes_publiees' => true, 'statut' => 'cloturee']);

            foreach ($eleves as $eleve) {
                $sommeP = 0;
                $sommeC = 0;
                foreach ($evaluations as $eval) {
                    $n = Note::where('evaluation_id', $eval->id)
                        ->where('eleve_id', $eleve->id)
                        ->whereNotNull('note')->where('absent', false)->where('dispense', false)
                        ->first();
                    if (!$n) continue;
                    $bareme  = (float) ($eval->note_sur ?: 20);
                    $coef    = (float) ($eval->coefficient ?: 1);
                    $n20     = $bareme > 0 ? ((float) $n->note / $bareme) * 20 : (float) $n->note;
                    $sommeP += $n20 * $coef;
                    $sommeC += $coef;
                }

                $moyenne = $sommeC > 0 ? round($sommeP / $sommeC, 2) : null;
                if ($moyenne === null) continue;

                $moyennes[] = $moyenne;

                MoyenneMatiere::updateOrCreate(
                    [
                        'eleve_id'     => $eleve->id,
                        'matiere_id'   => $activeMatiereId,
                        'trimestre_id' => $data['trimestre_id'],
                    ],
                    [
                        'classe_id'        => $classe->id,
                        'enseignant_id'    => $ens->id,
                        'moyenne'          => $moyenne,
                        'moyenne_ponderee' => $moyenne,
                        'saisie_directe'   => false,
                        'saisie_par'       => $request->user()->id,
                        'date_saisie'      => now(),
                        'publie'           => true,
                        'date_publication' => now(),
                    ]
                );
                $count++;
            }

            if (!empty($moyennes)) {
                MoyenneMatiere::where('classe_id', $classe->id)
                    ->where('matiere_id', $activeMatiereId)
                    ->where('trimestre_id', $data['trimestre_id'])
                    ->update([
                        'note_min_classe' => round(min($moyennes), 2),
                        'note_max_classe' => round(max($moyennes), 2),
                        'moyenne_classe'  => round(array_sum($moyennes) / count($moyennes), 2),
                    ]);
            }

            // Si on vient de publier une sous-discipline, recalculer la moyenne parent
            if (isset($data['sous_discipline_id'])) {
                $this->recalculerMoyenneParent(
                    $eleves, $classe, $data['matiere_id'], $data['trimestre_id'], $ens, $request
                );
            }
        });

        $label = isset($data['sous_discipline_id'])
            ? Matiere::find($activeMatiereId)?->code ?? 'SD'
            : 'matière';

        return back()->with('success', "{$count} moyenne(s) publiée(s) ({$label}). La direction y a maintenant accès.");
    }

    // ── Dépublier ──────────────────────────────────────────────────────────

    public function unpublish(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id'         => 'required|exists:matieres,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'sous_discipline_id' => 'nullable|exists:matieres,id',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $activeMatiereId = $data['sous_discipline_id'] ?? $data['matiere_id'];

        MoyenneMatiere::where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $data['trimestre_id'])
            ->where('enseignant_id', $ens->id)
            ->update(['publie' => false]);

        // Si c'est une SD, dépublier aussi la moyenne parent (car elle dépend de cette SD)
        if (isset($data['sous_discipline_id'])) {
            MoyenneMatiere::where('classe_id', $classe->id)
                ->where('matiere_id', $data['matiere_id'])
                ->where('trimestre_id', $data['trimestre_id'])
                ->update(['publie' => false]);
        }

        return back()->with('success', 'Moyennes dépubliées. Vous pouvez modifier les notes puis re-publier.');
    }

    // ── Recalcul moyenne parent après publication d'une SD ─────────────────

    private function recalculerMoyenneParent(
        $eleves, Classe $classe, int $parentId, int $trimestreId, Enseignant $ens, Request $request
    ): void {
        $parent = Matiere::with('sousDisciplines')->find($parentId);
        if (!$parent || $parent->sousDisciplines->isEmpty()) return;

        $sousDisciplines = $parent->sousDisciplines;

        // Vérifier que TOUTES les SDs ont des moyennes publiées
        $publieeCount = MoyenneMatiere::where('classe_id', $classe->id)
            ->whereIn('matiere_id', $sousDisciplines->pluck('id'))
            ->where('trimestre_id', $trimestreId)
            ->where('publie', true)
            ->distinct('matiere_id')
            ->count('matiere_id');

        if ($publieeCount < $sousDisciplines->count()) return;

        // Calculer et sauvegarder la moyenne parent pondérée par poids_dans_parent
        $moyParent = [];

        foreach ($eleves as $eleve) {
            $sumPoids = 0;
            $sumMoy   = 0;

            foreach ($sousDisciplines as $sd) {
                $moy = MoyenneMatiere::where('eleve_id', $eleve->id)
                    ->where('matiere_id', $sd->id)
                    ->where('trimestre_id', $trimestreId)
                    ->where('publie', true)
                    ->value('moyenne');

                if ($moy === null) continue;

                $poids    = (float) ($sd->poids_dans_parent ?? 1);
                $sumMoy  += (float) $moy * $poids;
                $sumPoids += $poids;
            }

            if ($sumPoids <= 0) continue;

            $moy         = round($sumMoy / $sumPoids, 2);
            $moyParent[] = $moy;

            MoyenneMatiere::updateOrCreate(
                ['eleve_id' => $eleve->id, 'matiere_id' => $parentId, 'trimestre_id' => $trimestreId],
                [
                    'classe_id'        => $classe->id,
                    'enseignant_id'    => $ens->id,
                    'moyenne'          => $moy,
                    'moyenne_ponderee' => $moy,
                    'saisie_directe'   => false,
                    'saisie_par'       => $request->user()->id,
                    'date_saisie'      => now(),
                    'publie'           => true,
                    'date_publication' => now(),
                ]
            );
        }

        if (!empty($moyParent)) {
            MoyenneMatiere::where('classe_id', $classe->id)
                ->where('matiere_id', $parentId)
                ->where('trimestre_id', $trimestreId)
                ->update([
                    'note_min_classe' => round(min($moyParent), 2),
                    'note_max_classe' => round(max($moyParent), 2),
                    'moyenne_classe'  => round(array_sum($moyParent) / count($moyParent), 2),
                ]);
        }
    }
}
