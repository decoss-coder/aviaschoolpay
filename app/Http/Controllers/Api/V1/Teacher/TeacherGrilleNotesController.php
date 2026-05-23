<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\MoyenneMatiere;
use App\Models\Note;
use App\Models\Trimestre;
use App\Support\ApiEnvelope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Grille de notes & moyennes matière (mobile).
 *
 * Reproduit la logique de GrilleNotesController côté web mais simplifiée
 * pour usage mobile (matrice élèves × évaluations).
 */
class TeacherGrilleNotesController extends Controller
{
    use ResolvesTeacherContext;

    // ── Grille notes (matrice élèves × évaluations) ──────────────────────────

    public function show(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);

        // Matières que le prof enseigne dans cette classe (parent ou sous-discipline)
        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->with(['matiere' => function ($q) {
                $q->select('id', 'nom', 'code', 'parent_matiere_id');
            }])
            ->get()
            ->pluck('matiere')
            ->filter()
            ->unique('id')
            ->values();

        abort_if($matieres->isEmpty(), 403, 'Aucune matière affectée à cette classe.');

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get(['id', 'libelle', 'numero', 'en_cours'])
            : collect();

        $matiereId   = (int) $request->input('matiere_id', $matieres->first()->id);
        $trimestreId = (int) $request->input('trimestre_id',
            $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);

        // Vérifier que la matière est bien enseignée par ce prof
        abort_unless($matieres->contains('id', $matiereId), 422, 'Matière non autorisée.');

        // ── Sous-disciplines (ex : Français → OG/CF/EO) ────────────────────────
        $matiere = Matiere::with(['sousDisciplines' => fn ($q) => $q->orderBy('ordre')->orderBy('code')])
            ->findOrFail($matiereId);

        $sousDisciplines = $matiere->sousDisciplines ?? collect();
        $hasSous = $sousDisciplines->isNotEmpty();

        // Si la matière a des sous-disciplines, on travaille sur la SD active
        $sousDisciplineId = null;
        $activeMatiereId  = $matiereId;
        if ($hasSous) {
            $sousDisciplineId = (int) $request->input(
                'sous_discipline_id',
                $sousDisciplines->first()->id
            );
            if (! $sousDisciplines->contains('id', $sousDisciplineId)) {
                $sousDisciplineId = $sousDisciplines->first()->id;
            }
            $activeMatiereId = $sousDisciplineId;
        }

        // Élèves de la classe (avec les deux matricules)
        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps']);
        $elevesById = $eleves->keyBy('id');

        // Évaluations (colonnes) pour matière (ou sous-discipline) + trimestre choisis
        $evaluations = Evaluation::where('classe_id', $classe->id)
            ->where('matiere_id', $activeMatiereId)
            ->where('trimestre_id', $trimestreId)
            ->where('enseignant_id', $ens->id)
            ->with(['typeEvaluation:id,nom,code'])
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        // Toutes les notes en une seule requête
        $notes = Note::whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get()
            ->groupBy(fn ($n) => $n->evaluation_id.'_'.$n->eleve_id);

        // Matrice : { eleve_id, eleve, notes: { evaluation_id => { note, absent } } }
        $rows = $eleves->map(function (Eleve $e) use ($evaluations, $notes) {
            $cells = [];
            foreach ($evaluations as $ev) {
                $key = $ev->id.'_'.$e->id;
                $n = $notes->get($key)?->first();
                $cells[$ev->id] = [
                    'note'   => $n?->note !== null ? (float) $n->note : null,
                    'absent' => (bool) ($n?->absent ?? false),
                ];
            }
            return [
                'eleve_id'  => $e->id,
                'eleve'     => [
                    'id'                => $e->id,
                    'nom'               => $e->nom,
                    'prenom'            => $e->prenom,
                    'matricule'         => $e->matricule_interne ?? $e->matricule_desps,
                    'matricule_interne' => $e->matricule_interne,
                    'matricule_desps'   => $e->matricule_desps,
                ],
                'cells'     => $cells,
                'moyenne'   => $this->moyennePonderee($cells, $evaluations),
            ];
        });

        // État publication des sous-disciplines (pour info UI)
        $sdPubliees = $hasSous
            ? MoyenneMatiere::where('trimestre_id', $trimestreId)
                ->whereIn('matiere_id', $sousDisciplines->pluck('id'))
                ->where('publie', true)
                ->pluck('matiere_id')
                ->unique()
                ->values()
                ->all()
            : [];

        return ApiEnvelope::success([
            'classe'             => $classe->only(['id', 'nom']),
            'matieres'           => $matieres,
            'trimestres'         => $trimestres,
            'matiere_id'         => $matiereId,
            'trimestre_id'       => $trimestreId,
            'has_sous_disciplines' => $hasSous,
            'sous_discipline_id' => $sousDisciplineId,
            'sous_disciplines'   => $sousDisciplines->map(fn ($sd) => [
                'id'      => $sd->id,
                'code'    => $sd->code,
                'nom'     => $sd->nom,
                'poids'   => (float) ($sd->poids_dans_parent ?? 1),
                'publie'  => in_array($sd->id, $sdPubliees, true),
            ])->values(),
            'evaluations'        => $evaluations->map(fn ($ev) => [
                'id'          => $ev->id,
                'titre'       => $ev->titre,
                'note_sur'    => (float) $ev->note_sur,
                'coefficient' => (float) $ev->coefficient,
                'date'        => $ev->date_evaluation,
                'type'        => $ev->typeEvaluation?->only(['id', 'nom', 'code']),
                'statut'      => $ev->statut,
            ]),
            'rows'               => $rows,
        ], 'Grille de notes.');
    }

    public function saveNote(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens = $this->enseignant($request);

        $data = $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'eleve_id'      => 'required|exists:eleves,id',
            'note'          => 'nullable|numeric|min:0',
            'absent'        => 'sometimes|boolean',
        ]);

        $evaluation = Evaluation::findOrFail($data['evaluation_id']);
        abort_unless((int) $evaluation->enseignant_id === (int) $ens->id, 403);
        abort_unless((int) $evaluation->classe_id === (int) $classe->id, 422, 'Évaluation hors classe.');
        // Vérif autorisation matière : parent OU sous-discipline
        $matiere = Matiere::find($evaluation->matiere_id);
        $checkIds = array_filter([$evaluation->matiere_id, $matiere?->parent_matiere_id]);
        $authorized = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->whereIn('matiere_id', $checkIds)
            ->where('active', true)
            ->exists();
        abort_unless($authorized, 403, 'Matière non autorisée.');

        if (isset($data['note']) && $data['note'] > $evaluation->note_sur) {
            return ApiEnvelope::fail("La note ne peut pas dépasser {$evaluation->note_sur}.", [], 422);
        }

        // Vérifier élève appartient à la classe
        $ok = Eleve::where('id', $data['eleve_id'])->where('classe_id', $classe->id)->where('actif', true)->exists();
        abort_unless($ok, 422, 'Élève non autorisé.');

        $absent = (bool) ($data['absent'] ?? false);

        $note = Note::updateOrCreate(
            ['evaluation_id' => $evaluation->id, 'eleve_id' => $data['eleve_id']],
            [
                'note'        => $absent ? null : ($data['note'] ?? null),
                'absent'      => $absent,
                'saisie_par'  => $request->user()->id,
                'date_saisie' => now(),
            ]
        );

        return ApiEnvelope::success([
            'note_id' => $note->id,
            'note'    => $note->note !== null ? (float) $note->note : null,
            'absent'  => (bool) $note->absent,
        ], 'Note enregistrée.');
    }

    // ── Moyennes matière (saisie directe) ────────────────────────────────────

    public function moyennes(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $annee  = $this->anneeCourante($etabId);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->with('matiere:id,nom,code')
            ->get()
            ->pluck('matiere')
            ->filter()
            ->unique('id')
            ->values();

        abort_if($matieres->isEmpty(), 403);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get(['id', 'libelle', 'numero', 'en_cours'])
            : collect();

        $matiereId   = (int) $request->input('matiere_id', $matieres->first()->id);
        $trimestreId = (int) $request->input('trimestre_id',
            $trimestres->firstWhere('en_cours', true)?->id ?? $trimestres->first()?->id);

        abort_unless($matieres->contains('id', $matiereId), 422);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps']);

        $moyExistantes = MoyenneMatiere::where('matiere_id', $matiereId)
            ->where('trimestre_id', $trimestreId)
            ->whereIn('eleve_id', $eleves->pluck('id'))
            ->get()
            ->keyBy('eleve_id');

        $rows = $eleves->map(function ($e) use ($moyExistantes) {
            $m = $moyExistantes->get($e->id);
            return [
                'eleve_id'  => $e->id,
                'eleve'     => [
                    'id'                => $e->id,
                    'nom'               => $e->nom,
                    'prenom'            => $e->prenom,
                    'matricule'         => $e->matricule_interne ?? $e->matricule_desps,
                    'matricule_interne' => $e->matricule_interne,
                    'matricule_desps'   => $e->matricule_desps,
                ],
                'moyenne'      => $m?->moyenne !== null ? (float) $m->moyenne : null,
                'rang'         => $m?->rang_classe,
                'appreciation' => $m?->appreciation,
                'publie'       => (bool) ($m?->publie ?? false),
            ];
        });

        return ApiEnvelope::success([
            'classe'       => $classe->only(['id', 'nom']),
            'matieres'     => $matieres,
            'trimestres'   => $trimestres,
            'matiere_id'   => $matiereId,
            'trimestre_id' => $trimestreId,
            'rows'         => $rows,
        ], 'Moyennes par matière.');
    }

    public function saveMoyennes(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);

        $data = $request->validate([
            'matiere_id'         => 'required|exists:matieres,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'moyennes'           => 'required|array|min:1',
            'moyennes.*.eleve_id' => 'required|exists:eleves,id',
            'moyennes.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'moyennes.*.appreciation' => 'nullable|string|max:200',
        ]);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        // Vérifier que tous les élèves appartiennent à la classe
        $eleveIds = collect($data['moyennes'])->pluck('eleve_id');
        $okIds = Eleve::whereIn('id', $eleveIds)
            ->where('classe_id', $classe->id)
            ->where('actif', true)
            ->pluck('id');
        if ($okIds->count() !== $eleveIds->unique()->count()) {
            return ApiEnvelope::fail('Certains élèves ne sont pas dans cette classe.', [], 422);
        }

        $ens = $this->enseignant($request);
        foreach ($data['moyennes'] as $m) {
            MoyenneMatiere::updateOrCreate(
                [
                    'eleve_id'     => $m['eleve_id'],
                    'matiere_id'   => $data['matiere_id'],
                    'trimestre_id' => $data['trimestre_id'],
                ],
                [
                    'classe_id'      => $classe->id,
                    'enseignant_id'  => $ens->id,
                    'moyenne'        => $m['moyenne'] ?? null,
                    'appreciation'   => $m['appreciation'] ?? null,
                    'saisie_par'     => $request->user()->id,
                    'date_saisie'    => now(),
                    'saisie_directe' => true,
                ]
            );
        }

        return ApiEnvelope::success(
            ['count' => count($data['moyennes'])],
            count($data['moyennes']) . ' moyenne(s) enregistrée(s).'
        );
    }

    // ── PDF Grille de notes remplie ──────────────────────────────────────────

    public function gridPdf(Request $request, Classe $classe): HttpResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $etab   = \App\Models\Etablissement::find($etabId);
        $annee  = $this->anneeCourante($etabId);

        $data = $request->validate([
            'matiere_id'   => 'required|exists:matieres,id',
            'trimestre_id' => 'required|exists:trimestres,id',
            'orientation'  => 'nullable|in:portrait,landscape',
        ]);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        $matiere    = Matiere::findOrFail($data['matiere_id']);
        $trimestre  = Trimestre::findOrFail($data['trimestre_id']);
        $orientation = $data['orientation'] ?? 'landscape';

        // Élèves
        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps']);

        // Évaluations
        $evaluations = Evaluation::where('classe_id', $classe->id)
            ->where('matiere_id', $data['matiere_id'])
            ->where('trimestre_id', $data['trimestre_id'])
            ->where('enseignant_id', $ens->id)
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        // Notes
        $notes = Note::whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get()
            ->groupBy(fn ($n) => $n->evaluation_id.'_'.$n->eleve_id);

        $rows = $eleves->map(function (Eleve $e) use ($evaluations, $notes) {
            $cells = [];
            foreach ($evaluations as $ev) {
                $key = $ev->id.'_'.$e->id;
                $n = $notes->get($key)?->first();
                $cells[$ev->id] = [
                    'note'   => $n?->note !== null ? (float) $n->note : null,
                    'absent' => (bool) ($n?->absent ?? false),
                ];
            }
            return [
                'eleve_id'  => $e->id,
                'eleve'     => [
                    'id'              => $e->id,
                    'nom'             => $e->nom,
                    'prenom'          => $e->prenom,
                    'matricule_desps' => $e->matricule_desps,
                ],
                'cells'   => $cells,
                'moyenne' => $this->moyennePonderee($cells, $evaluations),
            ];
        })->all();

        $fname = sprintf('grille-notes_%s_%s.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $matiere->code ?? $matiere->nom)
        );

        return Pdf::loadView('api-pdf.grille-notes', [
            'etab'        => $etab,
            'annee'       => $annee,
            'classe'      => $classe,
            'matiere'     => $matiere,
            'trimestre'   => $trimestre,
            'enseignant'  => $ens,
            'evaluations' => $evaluations,
            'rows'        => $rows,
        ])->setPaper('a4', $orientation)->download($fname);
    }

    // ── PDF Moyennes matière ─────────────────────────────────────────────────

    public function moyennesPdf(Request $request, Classe $classe): HttpResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens    = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        $etab   = \App\Models\Etablissement::find($etabId);
        $annee  = $this->anneeCourante($etabId);

        $data = $request->validate([
            'matiere_id'   => 'required|exists:matieres,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $this->authorizeMatierePourClasse($request, $classe->id, (int) $data['matiere_id']);

        $matiere   = Matiere::findOrFail($data['matiere_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps']);

        $moys = MoyenneMatiere::where('matiere_id', $data['matiere_id'])
            ->where('trimestre_id', $data['trimestre_id'])
            ->whereIn('eleve_id', $eleves->pluck('id'))
            ->get()
            ->keyBy('eleve_id');

        $rows = $eleves->map(function ($e) use ($moys) {
            $m = $moys->get($e->id);
            return [
                'eleve_id' => $e->id,
                'eleve'    => [
                    'id'              => $e->id,
                    'nom'             => $e->nom,
                    'prenom'          => $e->prenom,
                    'matricule_desps' => $e->matricule_desps,
                ],
                'moyenne'      => $m?->moyenne !== null ? (float) $m->moyenne : null,
                'appreciation' => $m?->appreciation,
            ];
        })->all();

        $fname = sprintf('moyennes_%s_%s_T%d.pdf',
            preg_replace('/[^a-zA-Z0-9]/', '-', $classe->nom),
            preg_replace('/[^a-zA-Z0-9]/', '-', $matiere->code ?? $matiere->nom),
            $trimestre->numero
        );

        return Pdf::loadView('api-pdf.moyennes', [
            'etab'       => $etab,
            'annee'      => $annee,
            'classe'     => $classe,
            'matiere'    => $matiere,
            'trimestre'  => $trimestre,
            'enseignant' => $ens,
            'rows'       => $rows,
        ])->setPaper('a4', 'portrait')->download($fname);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function moyennePonderee(array $cells, $evaluations): ?float
    {
        $sumNoteCoef = 0;
        $sumCoef = 0;
        foreach ($evaluations as $ev) {
            $cell = $cells[$ev->id] ?? null;
            if (! $cell || $cell['absent'] || $cell['note'] === null) continue;
            $noteSur20 = ($cell['note'] / max(1, (float) $ev->note_sur)) * 20.0;
            $coef = (float) ($ev->coefficient ?? 1);
            $sumNoteCoef += $noteSur20 * $coef;
            $sumCoef += $coef;
        }
        return $sumCoef > 0 ? round($sumNoteCoef / $sumCoef, 2) : null;
    }
}
