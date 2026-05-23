<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Devoir;
use App\Models\Eleve;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\Evaluation;
use App\Models\Note;
use App\Models\MoyenneMatiere;
use App\Models\Trimestre;
use App\Models\TypeEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EnseignantPortalController extends Controller
{
    // ── helpers ────────────────────────────────────────────────────────────

    private function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(!$ens, 403, 'Compte enseignant introuvable pour cette école.');
        return $ens;
    }

    private function etablissementActif(Request $request)
    {
        $id = $request->user()->ecoleActiveId();
        return \App\Models\Etablissement::find($id);
    }

    private function annee(int $etabId): ?AnneeScolaire
    {
        return \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);
    }

    private function trimestre(?AnneeScolaire $annee): ?Trimestre
    {
        if (!$annee) return null;
        return Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
            ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first();
    }

    private function authorizeClasse(Request $request, Classe $classe, Enseignant $ens): void
    {
        $ok = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->exists();
        abort_if(!$ok, 403, 'Vous n\'êtes pas affecté à cette classe.');
    }

    /**
     * Hub central Notes & Devoirs pour l'enseignant — vue d'ensemble de tout
     * ce qu'il gère : évaluations, notes, devoirs publiés, moyennes saisies.
     */
    public function notesHub(Request $request)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        $trimestre = $this->trimestre($annee);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $trimId = (int) $request->input('trimestre_id', $trimestre?->id);

        // Affectations actives
        $affectations = Affectation::where('enseignant_id', $ens->id)
            ->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->with(['classe.niveau', 'matiere'])
            ->get();

        $classes = $affectations->pluck('classe')->unique('id');

        // Évaluations (filtrables par trimestre)
        $evaluations = Evaluation::where('enseignant_id', $ens->id)
            ->when($trimId, fn ($q) => $q->where('trimestre_id', $trimId))
            ->with(['classe:id,nom', 'matiere:id,nom,code', 'typeEvaluation:id,nom,code'])
            ->withCount(['notes as nb_notes' => fn ($q) => $q->whereNotNull('note')])
            ->orderByDesc('date_evaluation')
            ->get();

        // Devoirs récents
        $devoirs = Devoir::where('enseignant_id', $ens->id)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->with(['classe:id,nom', 'matiere:id,nom,code'])
            ->orderByDesc('date_publication')
            ->take(20)->get();

        // Moyennes saisies (count par classe × matière × trimestre)
        $moyennesSaisies = $trimId
            ? \App\Models\MoyenneMatiere::where('enseignant_id', $ens->id)
                ->where('trimestre_id', $trimId)
                ->selectRaw('classe_id, matiere_id, COUNT(*) as nb, AVG(moyenne) as moy_classe')
                ->groupBy('classe_id', 'matiere_id')
                ->with(['classe:id,nom', 'matiere:id,nom,code'])
                ->get()
            : collect();

        // Stats globales
        $stats = [
            'nb_classes'      => $classes->count(),
            'nb_matieres'     => $affectations->pluck('matiere_id')->unique()->count(),
            'nb_evaluations'  => $evaluations->count(),
            'nb_devoirs'      => Devoir::where('enseignant_id', $ens->id)
                                  ->when($annee, fn($q) => $q->where('annee_scolaire_id', $annee->id))
                                  ->count(),
            'evals_a_saisir'  => $evaluations->filter(fn($e) => $e->statut === 'en_saisie' && $e->nb_notes === 0)->count(),
            'evals_publiees'  => $evaluations->where('notes_publiees', true)->count(),
            'devoirs_publies' => $devoirs->where('publie', true)->count(),
            'devoirs_a_venir' => $devoirs->filter(fn($d) => $d->date_limite && $d->date_limite->isFuture())->count(),
        ];

        return view('mon-espace.notes-hub',
            compact('ens','annee','trimestre','trimestres','trimId','classes','affectations',
                    'evaluations','devoirs','moyennesSaisies','stats'));
    }

    // ── dashboard ──────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);

        // Emploi du temps aujourd'hui
        $jourFr  = strtolower(Carbon::now()->locale('fr')->isoFormat('dddd'));
        $jours   = ['lundi','mardi','mercredi','jeudi','vendredi','samedi'];
        $today   = in_array($jourFr, $jours) ? $jourFr : null;

        $seancesAujourdHui = $annee && $today
            ? EmploiDuTemps::where('enseignant_id', $ens->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('jour', $today)
                ->where('actif', true)
                ->with(['classe', 'matiere', 'salle', 'creneau'])
                ->orderBy('creneau_id')
                ->get()
            : collect();

        // Stats rapides
        $nbClasses    = Affectation::where('enseignant_id', $ens->id)->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->distinct('classe_id')->count('classe_id');
        $nbEvals      = Evaluation::where('enseignant_id', $ens->id)
            ->when($annee, fn ($q) => $q->where('etablissement_id', $etab->id))
            ->count();
        $nbDevoirs    = Devoir::where('enseignant_id', $ens->id)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->count();

        return view('mon-espace.dashboard', compact('ens','annee','seancesAujourdHui','nbClasses','nbEvals','nbDevoirs','today'));
    }

    // ── mes classes ────────────────────────────────────────────────────────

    public function classes(Request $request)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);

        $affectations = Affectation::where('enseignant_id', $ens->id)
            ->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->with(['classe.niveau', 'matiere'])
            ->get()
            ->groupBy('classe_id');

        return view('mon-espace.classes', compact('ens','annee','affectations'));
    }

    // ── élèves d'une classe ────────────────────────────────────────────────

    public function eleves(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $annee = $this->annee((int) $request->user()->ecoleActiveId());
        $this->authorizeClasse($request, $classe, $ens);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->with('matiere')
            ->get()
            ->pluck('matiere');

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        return view('mon-espace.classe.eleves', compact('ens','classe','matieres','eleves','annee'));
    }

    // ── évaluations ────────────────────────────────────────────────────────

    public function evaluations(Request $request, Classe $classe)
    {
        $ens       = $this->enseignant($request);
        $etab      = $this->etablissementActif($request);
        $annee     = $this->annee($etab->id);
        $trimestre = $this->trimestre($annee);
        $this->authorizeClasse($request, $classe, $ens);

        $matieres   = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get()->pluck('matiere');

        $trimId    = $request->trimestre ?? $trimestre?->id;
        $trimestres = $annee ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get() : collect();

        $evaluations = Evaluation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->when($trimId, fn ($q) => $q->where('trimestre_id', $trimId))
            ->with(['matiere', 'typeEvaluation'])
            ->orderByDesc('date_evaluation')
            ->get();

        $typesEval = TypeEvaluation::where('etablissement_id', $etab->id)->where('actif', true)->get();

        return view('mon-espace.evaluations.index',
            compact('ens','classe','annee','matieres','trimestres','trimestre','trimId','evaluations','typesEval'));
    }

    public function storeEvaluation(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        $this->authorizeClasse($request, $classe, $ens);

        $data = $request->validate([
            'titre'              => 'required|string|max:200',
            'matiere_id'         => 'required|exists:matieres,id',
            'type_evaluation_id' => 'required|exists:types_evaluation,id',
            'trimestre_id'       => 'required|exists:trimestres,id',
            'date_evaluation'    => 'required|date',
            'note_sur'           => 'required|numeric|min:1|max:100',
            'coefficient'        => 'required|numeric|min:0.5|max:10',
            'description'        => 'nullable|string|max:1000',
            'fichier_sujet'      => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'fichier_corrige'    => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
        ]);

        $sujetPath = null;
        $corrigePath = null;
        if ($request->hasFile('fichier_sujet')) {
            $sujetPath = $request->file('fichier_sujet')->store('sujets-evaluations', 'public');
        }
        if ($request->hasFile('fichier_corrige')) {
            $corrigePath = $request->file('fichier_corrige')->store('corriges-evaluations', 'public');
        }

        Evaluation::create([
            'etablissement_id'    => $etab->id,
            'classe_id'           => $classe->id,
            'enseignant_id'       => $ens->id,
            'matiere_id'          => $data['matiere_id'],
            'type_evaluation_id'  => $data['type_evaluation_id'],
            'trimestre_id'        => $data['trimestre_id'],
            'titre'               => $data['titre'],
            'date_evaluation'     => $data['date_evaluation'],
            'note_sur'            => $data['note_sur'],
            'coefficient'         => $data['coefficient'],
            'description'         => $data['description'] ?? null,
            'fichier_sujet_path'  => $sujetPath,
            'fichier_corrige_path'=> $corrigePath,
            'statut'              => 'en_saisie',
        ]);

        return redirect()->route('mon-espace.evaluations', $classe)->with('success', 'Évaluation créée.');
    }

    // ── SAISIE MOYENNES DIRECTES ──────────────────────────────────────────

    /**
     * Page de saisie directe des moyennes par matière et trimestre.
     * Le prof peut entrer une moyenne /20 par élève sans passer par les notes individuelles.
     */
    public function moyennes(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        $this->authorizeClasse($request, $classe, $ens);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get()->pluck('matiere');

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $matiereId   = $request->input('matiere_id', $matieres->first()?->id);
        $trimestreId = $request->input('trimestre_id', $trimestres->first(fn ($t) => $t->en_cours)?->id ?? $trimestres->first()?->id);

        $matiere = $matieres->firstWhere('id', $matiereId);
        $matiere?->load('sousDisciplines');
        $sousDisciplines = $matiere?->sousDisciplines ?? collect();

        $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')->get();

        if ($sousDisciplines->isNotEmpty()) {
            // Charger les moyennes par sous-discipline → indexed as sd_id → eleve_id → MoyenneMatiere
            $sdMoyRaw = MoyenneMatiere::where('classe_id', $classe->id)
                ->whereIn('matiere_id', $sousDisciplines->pluck('id'))
                ->where('trimestre_id', $trimestreId)
                ->get();
            $moyennesSd = $sdMoyRaw->groupBy('matiere_id')
                ->map(fn ($g) => $g->keyBy('eleve_id'));

            // Charger la moyenne parent (pour l'appréciation)
            $moyennesParent = MoyenneMatiere::where('classe_id', $classe->id)
                ->where('matiere_id', $matiereId)
                ->where('trimestre_id', $trimestreId)
                ->get()->keyBy('eleve_id');

            $moyennes = collect(); // non utilisé en mode SD
        } else {
            $moyennesSd     = collect();
            $moyennesParent = collect();
            $moyennes       = MoyenneMatiere::where('classe_id', $classe->id)
                ->where('matiere_id', $matiereId)
                ->where('trimestre_id', $trimestreId)
                ->get()->keyBy('eleve_id');
        }

        return view('mon-espace.moyennes.index',
            compact('ens', 'classe', 'annee', 'matieres', 'trimestres', 'matiereId', 'trimestreId',
                    'matiere', 'eleves', 'moyennes', 'sousDisciplines', 'moyennesSd', 'moyennesParent'));
    }

    public function storeMoyennes(Request $request, Classe $classe)
    {
        $ens = $this->enseignant($request);
        $this->authorizeClasse($request, $classe, $ens);

        $request->validate([
            'matiere_id'   => 'required|exists:matieres,id',
            'trimestre_id' => 'required|exists:trimestres,id',
        ]);

        $matiereId   = (int) $request->input('matiere_id');
        $trimestreId = (int) $request->input('trimestre_id');

        // Vérifier autorisation (matière ou matière parent)
        $matiere = \App\Models\Matiere::with('sousDisciplines')->findOrFail($matiereId);
        $checkIds = array_filter([$matiereId, $matiere->parent_matiere_id]);
        $autorise = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->whereIn('matiere_id', $checkIds)
            ->where('active', true)->exists();
        abort_if(!$autorise, 403, 'Vous n\'enseignez pas cette matière dans cette classe.');

        $sousDisciplines = $matiere->sousDisciplines;
        $eleves          = Eleve::where('classe_id', $classe->id)->where('actif', true)->pluck('id')->all();
        $count           = 0;

        if ($sousDisciplines->isNotEmpty()) {
            // ── Mode sous-disciplines ──────────────────────────────────────
            $request->validate([
                'sd_moyennes'       => 'nullable|array',
                'sd_moyennes.*'     => 'array',
                'sd_moyennes.*.*'   => 'nullable|numeric|min:0|max:20',
                'appreciations'     => 'nullable|array',
                'appreciations.*'   => 'nullable|string|max:200',
            ]);

            $sdInput      = $request->input('sd_moyennes', []);
            $appreciations = $request->input('appreciations', []);

            DB::transaction(function () use ($sdInput, $appreciations, $eleves, $classe, $ens, $request,
                                             $matiereId, $trimestreId, $matiere, $sousDisciplines, &$count) {
                // 1) Sauvegarder chaque sous-discipline
                foreach ($sousDisciplines as $sd) {
                    $sdMoyennes = $sdInput[$sd->id] ?? [];
                    foreach ($sdMoyennes as $eleveId => $valeur) {
                        if (!in_array((int) $eleveId, $eleves)) continue;
                        if ($valeur === null || $valeur === '') {
                            MoyenneMatiere::where('eleve_id', $eleveId)
                                ->where('matiere_id', $sd->id)
                                ->where('trimestre_id', $trimestreId)
                                ->delete();
                            continue;
                        }
                        MoyenneMatiere::updateOrCreate(
                            ['eleve_id' => (int) $eleveId, 'matiere_id' => $sd->id, 'trimestre_id' => $trimestreId],
                            [
                                'classe_id'        => $classe->id,
                                'enseignant_id'    => $ens->id,
                                'moyenne'          => (float) $valeur,
                                'saisie_directe'   => true,
                                'saisie_par'       => $request->user()->id,
                                'date_saisie'      => now(),
                                'publie'           => true,
                                'date_publication' => now(),
                            ]
                        );
                    }
                }

                // 2) Calculer et sauvegarder la moyenne parent (pondérée par poids_dans_parent)
                foreach ($eleves as $eleveId) {
                    $sumPoids = 0;
                    $sumMoy   = 0;
                    foreach ($sousDisciplines as $sd) {
                        $val = $sdInput[$sd->id][$eleveId] ?? null;
                        if ($val === null || $val === '') continue;
                        $poids    = (float) ($sd->poids_dans_parent ?? 1);
                        $sumMoy  += (float) $val * $poids;
                        $sumPoids += $poids;
                    }

                    if ($sumPoids <= 0) {
                        MoyenneMatiere::where('eleve_id', $eleveId)
                            ->where('matiere_id', $matiereId)
                            ->where('trimestre_id', $trimestreId)
                            ->delete();
                        continue;
                    }

                    $parentMoy    = round($sumMoy / $sumPoids, 2);
                    $appreciation = $appreciations[$eleveId] ?? null;

                    MoyenneMatiere::updateOrCreate(
                        ['eleve_id' => $eleveId, 'matiere_id' => $matiereId, 'trimestre_id' => $trimestreId],
                        [
                            'classe_id'        => $classe->id,
                            'enseignant_id'    => $ens->id,
                            'moyenne'          => $parentMoy,
                            'appreciation'     => $appreciation,
                            'saisie_directe'   => false,
                            'saisie_par'       => $request->user()->id,
                            'date_saisie'      => now(),
                            'publie'           => true,
                            'date_publication' => now(),
                        ]
                    );
                    $count++;
                }
            });
        } else {
            // ── Mode matière simple (comportement d'origine) ───────────────
            $request->validate([
                'moyennes'        => 'required|array',
                'moyennes.*'      => 'nullable|numeric|min:0|max:20',
                'appreciations'   => 'nullable|array',
                'appreciations.*' => 'nullable|string|max:200',
            ]);

            $moyInput      = $request->input('moyennes', []);
            $appreciations = $request->input('appreciations', []);

            DB::transaction(function () use ($moyInput, $appreciations, $eleves, $classe, $ens, $request,
                                             $matiereId, $trimestreId, &$count) {
                foreach ($moyInput as $eleveId => $valeur) {
                    if (!in_array((int) $eleveId, $eleves)) continue;
                    if ($valeur === null || $valeur === '') {
                        MoyenneMatiere::where('eleve_id', $eleveId)
                            ->where('matiere_id', $matiereId)
                            ->where('trimestre_id', $trimestreId)
                            ->delete();
                        continue;
                    }
                    MoyenneMatiere::updateOrCreate(
                        ['eleve_id' => (int) $eleveId, 'matiere_id' => $matiereId, 'trimestre_id' => $trimestreId],
                        [
                            'classe_id'        => $classe->id,
                            'enseignant_id'    => $ens->id,
                            'moyenne'          => (float) $valeur,
                            'appreciation'     => $appreciations[$eleveId] ?? null,
                            'saisie_directe'   => true,
                            'saisie_par'       => $request->user()->id,
                            'date_saisie'      => now(),
                            'publie'           => true,
                            'date_publication' => now(),
                        ]
                    );
                    $count++;
                }
            });
        }

        return redirect()->route('mon-espace.moyennes', [
            'classe'       => $classe,
            'matiere_id'   => $matiereId,
            'trimestre_id' => $trimestreId,
        ])->with('success', "{$count} moyenne(s) enregistrée(s) et publiée(s).");
    }

    // ── saisie des notes ───────────────────────────────────────────────────

    public function notes(Request $request, Evaluation $evaluation)
    {
        $ens  = $this->enseignant($request);
        abort_if($evaluation->enseignant_id !== $ens->id, 403);

        $evaluation->load(['classe', 'matiere', 'typeEvaluation']);
        $eleves = Eleve::where('classe_id', $evaluation->classe_id)
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        $notes = Note::where('evaluation_id', $evaluation->id)
            ->pluck('note', 'eleve_id');
        $absents   = Note::where('evaluation_id', $evaluation->id)->where('absent', true)->pluck('eleve_id')->flip();
        $dispenses = Note::where('evaluation_id', $evaluation->id)->where('dispense', true)->pluck('eleve_id')->flip();

        return view('mon-espace.evaluations.notes',
            compact('ens','evaluation','eleves','notes','absents','dispenses'));
    }

    public function storeNotes(Request $request, Evaluation $evaluation)
    {
        $ens = $this->enseignant($request);
        abort_if($evaluation->enseignant_id !== $ens->id, 403);

        $request->validate([
            'notes'    => 'nullable|array',
            'absents'  => 'nullable|array',
            'dispenses'=> 'nullable|array',
        ]);

        $notesInput    = $request->input('notes', []);
        $absentsInput  = array_flip($request->input('absents', []));
        $dispensesInput= array_flip($request->input('dispenses', []));

        $eleves = Eleve::where('classe_id', $evaluation->classe_id)->where('actif', true)->pluck('id');

        foreach ($eleves as $eleveId) {
            $absent   = isset($absentsInput[$eleveId]);
            $dispense = isset($dispensesInput[$eleveId]);
            $note     = (!$absent && !$dispense && isset($notesInput[$eleveId]) && $notesInput[$eleveId] !== '')
                ? (float) $notesInput[$eleveId]
                : null;

            Note::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'eleve_id' => $eleveId],
                [
                    'note'      => $note,
                    'absent'    => $absent,
                    'dispense'  => $dispense,
                    'saisie_par'=> $request->user()->id,
                    'date_saisie' => now(),
                ]
            );
        }

        $evaluation->update(['statut' => 'cloturee']);

        return redirect()->route('mon-espace.evaluations', $evaluation->classe_id)
            ->with('success', 'Notes enregistrées.');
    }

    // ── devoirs ────────────────────────────────────────────────────────────

    public function devoirs(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        $this->authorizeClasse($request, $classe, $ens);

        $matieres = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)->where('active', true)
            ->with('matiere')->get()->pluck('matiere');

        $devoirs = Devoir::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->with('matiere')
            ->orderByDesc('date_publication')
            ->get();

        return view('mon-espace.devoirs.index', compact('ens','classe','annee','matieres','devoirs'));
    }

    public function storeDevoir(Request $request, Classe $classe)
    {
        $ens   = $this->enseignant($request);
        $etab  = $this->etablissementActif($request);
        $annee = $this->annee($etab->id);
        abort_if(!$annee, 422, 'Aucune année scolaire en cours.');
        $this->authorizeClasse($request, $classe, $ens);

        $data = $request->validate([
            'titre'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'type'             => 'required|in:devoir,exercice,tp,projet,lecture,interrogation',
            'matiere_id'       => 'required|exists:matieres,id',
            'date_publication' => 'required|date',
            'date_limite'      => 'nullable|date|after_or_equal:date_publication',
            'publie'           => 'nullable|boolean',
            'fichier_sujet'    => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'fichier_corrige'  => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
        ]);

        $sujetPath = $request->hasFile('fichier_sujet')
            ? $request->file('fichier_sujet')->store('sujets-devoirs', 'public') : null;
        $corrigePath = $request->hasFile('fichier_corrige')
            ? $request->file('fichier_corrige')->store('corriges-devoirs', 'public') : null;

        Devoir::create([
            'etablissement_id'    => $etab->id,
            'annee_scolaire_id'   => $annee->id,
            'classe_id'           => $classe->id,
            'enseignant_id'       => $ens->id,
            'matiere_id'          => $data['matiere_id'],
            'titre'               => $data['titre'],
            'description'         => $data['description'] ?? null,
            'type'                => $data['type'],
            'date_publication'    => $data['date_publication'],
            'date_limite'         => $data['date_limite'] ?? null,
            'fichier_path'        => $sujetPath,
            'fichier_corrige_path'=> $corrigePath,
            'publie'              => $request->boolean('publie'),
        ]);

        return redirect()->route('mon-espace.devoirs', $classe)->with('success', 'Devoir publié.');
    }

    public function destroyDevoir(Request $request, Devoir $devoir)
    {
        $ens = $this->enseignant($request);
        abort_if($devoir->enseignant_id !== $ens->id, 403);
        $classeId = $devoir->classe_id;
        $devoir->delete();
        return redirect()->route('mon-espace.devoirs', $classeId)->with('success', 'Devoir supprimé.');
    }
}
