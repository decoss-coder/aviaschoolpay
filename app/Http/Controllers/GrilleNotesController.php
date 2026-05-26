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
 * - Support sous-disciplines Français premier cycle : CF / OG / EO.
 */
class GrilleNotesController extends Controller
{
    private function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(!$ens, 403, 'Compte enseignant introuvable.');
        return $ens;
    }

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

    public function index(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = \App\Models\Etablissement::find($request->user()->ecoleActiveId());
        abort_if(!$etab, 403, 'Établissement introuvable.');

        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $classe->loadMissing('niveau');

        $affectations = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->with('matiere')
            ->get();

        abort_if($affectations->isEmpty(), 403, 'Aucune affectation active pour cette classe.');

        $matieres = $affectations->pluck('matiere')->unique('id')->filter()->values();

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $matiereId   = (int) $request->input('matiere_id', $matieres->first()?->id);
        $trimestreId = (int) $request->input('trimestre_id',
            $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);

        $this->authorizeClasseMatiere($ens, $classe, $matiereId);

        $matiere = Matiere::findOrFail($matiereId);

        if ($this->classeUtiliseSousDisciplines($classe) && $this->estFrancaisRacine($matiere)) {
            $this->creerSousDisciplinesFrancaisPremierCycleSiAbsentes($matiere);
        }

        $matiere = Matiere::with(['sousDisciplines' => function ($q) {
            $q->where('active', true)->orderBy('ordre')->orderBy('code');
        }])->findOrFail($matiereId);

        $sousDisciplines = collect();
        if ($this->classeUtiliseSousDisciplines($classe)) {
            $sousDisciplines = $matiere->sousDisciplines;
        }

        $sousDisciplineId = null;
        $activeMatiereId  = $matiereId;

        if ($sousDisciplines->isNotEmpty()) {
            $sousDisciplineId = (int) $request->input('sous_discipline_id', $sousDisciplines->first()->id);
            if (!$sousDisciplines->contains('id', $sousDisciplineId)) {
                $sousDisciplineId = $sousDisciplines->first()->id;
            }
            $activeMatiereId = $sousDisciplineId;
        }

        $trimestre = $trimestres->firstWhere('id', $trimestreId);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $evaluations = Evaluation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $trimestreId)
            ->with('typeEvaluation')
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        $notes = Note::whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get()
            ->groupBy('evaluation_id')
            ->map(fn ($n) => $n->keyBy('eleve_id'));

        $moyennePubliee = MoyenneMatiere::where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $trimestreId)
            ->where('publie', true)
            ->first();

        $sdPubliees = collect();
        if ($sousDisciplines->isNotEmpty()) {
            $sdPubliees = MoyenneMatiere::where('classe_id', $classe->id)
                ->whereIn('matiere_id', $sousDisciplines->pluck('id'))
                ->where('trimestre_id', $trimestreId)
                ->where('publie', true)
                ->pluck('matiere_id')
                ->flip();
        }

        $this->assurerTypesEvaluationParDefaut((int) $etab->id);
        $typesEval = TypeEvaluation::where('etablissement_id', $etab->id)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        return view('mon-espace.grille-notes', compact(
            'ens', 'classe', 'annee', 'matieres', 'matiere', 'matiereId',
            'trimestres', 'trimestre', 'trimestreId', 'eleves', 'evaluations', 'notes',
            'moyennePubliee', 'typesEval',
            'sousDisciplines', 'sousDisciplineId', 'activeMatiereId', 'sdPubliees'
        ));
    }

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
        abort_if(!$etab, 403, 'Établissement introuvable.');

        $typeOk = TypeEvaluation::where('id', $data['type_evaluation_id'])
            ->where('etablissement_id', $etab->id)
            ->where('active', true)
            ->exists();
        abort_if(!$typeOk, 422, 'Type d\'évaluation invalide pour cette école.');

        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

        $eval = Evaluation::create([
            'etablissement_id'   => $etab->id,
            'classe_id'          => $classe->id,
            'matiere_id'         => $data['matiere_id'],
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

    public function publish(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'matiere_id'         => 'required|exists:matieres,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'sous_discipline_id' => 'nullable|exists:matieres,id',
        ]);

        $ens = $this->enseignant($request);
        $this->authorizeClasseMatiere($ens, $classe, $data['matiere_id']);

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

            if (isset($data['sous_discipline_id'])) {
                $this->recalculerMoyenneParent($eleves, $classe, $data['matiere_id'], $data['trimestre_id'], $ens, $request);
            }
        });

        $label = isset($data['sous_discipline_id'])
            ? Matiere::find($activeMatiereId)?->code ?? 'SD'
            : 'matière';

        return back()->with('success', "{$count} moyenne(s) publiée(s) ({$label}). La direction y a maintenant accès.");
    }

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

        if (isset($data['sous_discipline_id'])) {
            MoyenneMatiere::where('classe_id', $classe->id)
                ->where('matiere_id', $data['matiere_id'])
                ->where('trimestre_id', $data['trimestre_id'])
                ->update(['publie' => false]);
        }

        return back()->with('success', 'Moyennes dépubliées. Vous pouvez modifier les notes puis re-publier.');
    }

    private function recalculerMoyenneParent($eleves, Classe $classe, int $parentId, int $trimestreId, Enseignant $ens, Request $request): void
    {
        $parent = Matiere::with(['sousDisciplines' => fn ($q) => $q->where('active', true)->orderBy('ordre')->orderBy('code')])->find($parentId);
        if (!$parent || $parent->sousDisciplines->isEmpty()) return;

        $sousDisciplines = $parent->sousDisciplines;

        $publieeCount = MoyenneMatiere::where('classe_id', $classe->id)
            ->whereIn('matiere_id', $sousDisciplines->pluck('id'))
            ->where('trimestre_id', $trimestreId)
            ->where('publie', true)
            ->distinct('matiere_id')
            ->count('matiere_id');

        if ($publieeCount < $sousDisciplines->count()) return;

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

    private function assurerTypesEvaluationParDefaut(int $etabId): void
    {
        if (TypeEvaluation::where('etablissement_id', $etabId)->where('active', true)->exists()) {
            return;
        }

        $types = [
            ['code' => 'INT', 'nom' => 'Interrogation', 'coef' => 1, 'sur' => 20],
            ['code' => 'DEV', 'nom' => 'Devoir', 'coef' => 1, 'sur' => 20],
            ['code' => 'COMP', 'nom' => 'Composition', 'coef' => 2, 'sur' => 20],
            ['code' => 'ORAL', 'nom' => 'Oral', 'coef' => 1, 'sur' => 20],
        ];

        foreach ($types as $type) {
            TypeEvaluation::firstOrCreate(
                ['etablissement_id' => $etabId, 'code' => $type['code']],
                [
                    'nom' => $type['nom'],
                    'coefficient_defaut' => $type['coef'],
                    'note_sur_defaut' => $type['sur'],
                    'active' => true,
                ]
            );
        }
    }

    private function classeUtiliseSousDisciplines(Classe $classe): bool
    {
        $classe->loadMissing('niveau');
        $niveau = $classe->niveau;
        if (! $niveau) return false;

        $cycle = $this->normaliserTexte((string) ($niveau->cycle ?? ''));
        if (in_array($cycle, ['premier_cycle', 'premier cycle', 'college'], true)) {
            return true;
        }

        $codeOuLibelle = $this->normaliserTexte(trim((string) ($niveau->code ?? '') . ' ' . (string) ($niveau->libelle ?? '')));
        return preg_match('/(^|\s)(6|5|4|3)\s*(e|eme|eme)?(\s|$)/', $codeOuLibelle) === 1;
    }

    private function estFrancaisRacine(Matiere $matiere): bool
    {
        if ($matiere->parent_matiere_id !== null) return false;
        $text = $this->normaliserTexte(trim((string) $matiere->code . ' ' . (string) $matiere->nom));
        return str_contains($text, 'francais') || in_array($text, ['fr', 'fra', 'fran', 'franc'], true);
    }

    private function creerSousDisciplinesFrancaisPremierCycleSiAbsentes(Matiere $fr): void
    {
        $presets = [
            ['code' => 'CF', 'nom' => 'Composition française', 'poids' => 3, 'ordre' => 1],
            ['code' => 'OG', 'nom' => 'Orthographe et grammaire', 'poids' => 1, 'ordre' => 2],
            ['code' => 'EO', 'nom' => 'Expression orale', 'poids' => 1, 'ordre' => 3],
        ];

        foreach ($presets as $preset) {
            $exists = Matiere::where('etablissement_id', $fr->etablissement_id)
                ->where('parent_matiere_id', $fr->id)
                ->where(function ($q) use ($preset) {
                    $q->where('code', $preset['code'])->orWhere('nom', $preset['nom']);
                })
                ->exists();

            if ($exists) continue;

            Matiere::create([
                'etablissement_id' => $fr->etablissement_id,
                'parent_matiere_id' => $fr->id,
                'nom' => $preset['nom'],
                'code' => $this->codeSousDisciplineDisponible($fr, $preset['code']),
                'coefficient_defaut' => $fr->coefficient_defaut ?? 1,
                'poids_dans_parent' => $preset['poids'],
                'ordre' => $preset['ordre'],
                'groupe' => 'francais_premier_cycle',
                'active' => true,
            ]);
        }
    }

    private function codeSousDisciplineDisponible(Matiere $parent, string $base): string
    {
        $candidate = $base;
        $counter = 1;

        while (Matiere::where('etablissement_id', $parent->etablissement_id)
            ->where('code', $candidate)
            ->where('parent_matiere_id', '!=', $parent->id)
            ->exists()) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function normaliserTexte(string $value): string
    {
        $value = strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }
}
