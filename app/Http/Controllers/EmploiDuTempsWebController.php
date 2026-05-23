<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\EmploiDuTempsIAGenerateRequest;
use App\Http\Requests\Admin\EmploiDuTempsRequest;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EdtVacataireImport;
use App\Models\EmploiDuTemps;
use App\Models\EmploiDuTempsAdjustment;
use App\Models\Enseignant;
use App\Models\EnseignantHoraireExterne;
use App\Models\Matiere;
use App\Models\Salle;
use App\Services\EmploiDuTempsIAService;
use App\Services\EmploiDuTempsLearningService;
use App\Services\Scolarite\AnneeScolaireContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmploiDuTempsWebController extends Controller
{
    public function __construct(
        protected EmploiDuTempsIAService $iaService,
        protected EmploiDuTempsLearningService $learningService
    ) {
    }

    public function index(Request $request)
    {
        $etabId = $request->user()->etablissement_id;

        $annees = $this->annees($etabId);
        $anneeIdDefaut = $this->resolveAnneeScolaireId($request, $etabId, $annees);

        $query = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->with(['anneeScolaire', 'classe.niveau', 'matiere', 'enseignant', 'salle', 'creneau']);

        $anneeFiltre = $request->filled('annee_scolaire_id')
            ? $request->integer('annee_scolaire_id')
            : $anneeIdDefaut;

        if ($anneeFiltre) {
            $query->where('annee_scolaire_id', $anneeFiltre);
        }

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->integer('classe_id'));
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->integer('enseignant_id'));
        }

        if ($request->filled('salle_id')) {
            $query->where('salle_id', $request->integer('salle_id'));
        }

        if ($request->filled('jour')) {
            $query->where('jour', $request->input('jour'));
        }

        if ($request->filled('active')) {
            $query->where('actif', $request->boolean('active'));
        }

        $emplois = $query
            ->orderBy('jour')
            ->orderBy('creneau_id')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $edtCountsParAnnee = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->selectRaw('annee_scolaire_id, COUNT(*) as total')
            ->groupBy('annee_scolaire_id')
            ->pluck('total', 'annee_scolaire_id');

        $statsBase = EmploiDuTemps::query()->where('etablissement_id', $etabId);
        if ($anneeFiltre) {
            $statsBase->where('annee_scolaire_id', $anneeFiltre);
        }

        $anneePourRecup = null;
        $edtDansArchiveCourante = 0;
        if ($anneeFiltre && ($edtCountsParAnnee[$anneeFiltre] ?? 0) === 0) {
            // 1) Priorité : l'année actuellement sélectionnée (si archivée avec clé en coffre)
            $anneeCourante = $annees->firstWhere('id', $anneeFiltre);
            if ($anneeCourante && $anneeCourante->archive_path && $anneeCourante->restoration_key_vault) {
                $cnt = \App\Services\Scolarite\AnneeScolaireArchiveService::compterEmploiDansArchive($anneeCourante);
                if ($cnt > 0) {
                    $anneePourRecup = $anneeCourante;
                    $edtDansArchiveCourante = $cnt;
                }
            }
            // 2) Fallback : autres années archivées (utile en cas de migration ou première installation)
            if (! $anneePourRecup) {
                foreach ($annees as $a) {
                    if ($a->id === $anneeFiltre) continue;
                    if (! $a->archive_path || ! $a->restoration_key_vault) continue;
                    $cnt = \App\Services\Scolarite\AnneeScolaireArchiveService::compterEmploiDansArchive($a);
                    if ($cnt > 0) {
                        $anneePourRecup = $a;
                        $edtDansArchiveCourante = $cnt;
                        break;
                    }
                }
            }
        }

        return view('emploi-du-temps.index', [
            'emplois' => $emplois,
            'annees' => $annees,
            'anneeIdDefaut' => $anneeIdDefaut,
            'anneeFiltre' => $anneeFiltre,
            'edtCountsParAnnee' => $edtCountsParAnnee,
            'anneePourRecup' => ($edtCountsParAnnee[$anneeFiltre ?? 0] ?? 0) === 0 ? ($anneePourRecup ?? $annees->firstWhere('id', $anneeFiltre)) : null,
            'edtDansArchiveCourante' => $edtDansArchiveCourante,
            'classes' => $this->classes($etabId, $anneeFiltre),
            'enseignants' => $this->enseignants($etabId),
            'salles' => $this->salles($etabId),
            'jours' => EmploiDuTemps::jours(),
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'actifs' => (clone $statsBase)->where('actif', true)->count(),
                'inactifs' => (clone $statsBase)->where('actif', false)->count(),
            ],
        ]);
    }

    public function professeur(Request $request, Enseignant $enseignant)
    {
        $etabId = $request->user()->etablissement_id;
        abort_unless($enseignant->etablissement_id === $etabId, 404);

        $annees = $this->annees($etabId);
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $annees);
        $anneeActive = $annees->firstWhere('id', $anneeId);

        $emplois = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->where('enseignant_id', $enseignant->id)
            ->where('actif', true)
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->with(['classe.niveau', 'matiere', 'salle', 'creneau'])
            ->orderBy('jour')
            ->orderBy('creneau_id')
            ->get();

        $jours = EmploiDuTemps::jours();
        $creneaux = $this->creneaux($etabId);

        $grid = [];
        foreach ($jours as $jour) {
            foreach ($creneaux as $creneau) {
                $grid[$jour][$creneau->id] = null;
            }
        }

        foreach ($emplois as $seance) {
            $grid[$seance->jour][$seance->creneau_id] = $seance;
        }

        return view('emploi-du-temps.professeur', [
            'enseignant' => $enseignant,
            'anneeActive' => $anneeActive,
            'annees' => $annees,
            'creneaux' => $creneaux,
            'jours' => $jours,
            'emplois' => $emplois,
            'grid' => $grid,
            'etablissement' => $request->user()->etablissement,
        ]);
    }

    public function grille(Request $request)
    {
        $etabId = $request->user()->etablissement_id;

        $annees = $this->annees($etabId);
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $annees);
        $anneeActive = $annees->firstWhere('id', $anneeId);

        $vue = $request->input('vue', 'classe');
        $classeId = $request->integer('classe_id') ?: null;
        $enseignantId = $request->integer('enseignant_id') ?: null;

        $jours = EmploiDuTemps::jours();
        $creneaux = $this->creneaux($etabId);

        $query = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->where('actif', true)
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
            ->when($enseignantId, fn ($q) => $q->where('enseignant_id', $enseignantId))
            ->with(['classe', 'matiere', 'enseignant', 'salle', 'creneau']);

        $emplois = $query->get();

        $grid = [];
        foreach ($jours as $jour) {
            foreach ($creneaux as $creneau) {
                $grid[$jour][$creneau->id] = collect();
            }
        }

        foreach ($emplois as $seance) {
            $grid[$seance->jour][$seance->creneau_id][] = $seance;
            $grid[$seance->jour][$seance->creneau_id] = collect($grid[$seance->jour][$seance->creneau_id]);
        }

        $conflits = $this->iaService->detecterConflits($etabId, $anneeId);
        $conflitKeys = collect($conflits)
            ->flatMap(fn ($c) => collect($c['items'])->map(fn ($i) => $i->jour . '|' . $i->creneau_id))
            ->unique()
            ->values()
            ->all();

        $lastAppliedGenerationUuid = $this->resolveLastAppliedGenerationUuid(
            etablissementId: $etabId,
            anneeScolaireId: $anneeId,
            classeId: $classeId,
            enseignantId: $enseignantId
        );

        $lastIaRun = $this->resolveLastIaRun($etabId, $anneeId);
        $parametres = $this->resolveEdtParametres($etabId, $anneeId);

        $adjustmentsLearningEnabled = (bool) ($parametres['activer_apprentissage_ajustements'] ?? true);

        $adjustmentsCount = $this->resolveAdjustmentsCount(
            etablissementId: $etabId,
            anneeScolaireId: $anneeId,
            classeId: $classeId,
            enseignantId: $enseignantId
        );

        return view('emploi-du-temps.grille', [
            'annees' => $annees,
            'anneeActive' => $anneeActive,
            'classes' => $this->classes($etabId, $anneeId),
            'matieres' => $this->matieres($etabId),
            'enseignants' => $this->enseignants($etabId),
            'salles' => $this->salles($etabId),
            'creneaux' => $creneaux,
            'jours' => $jours,
            'grid' => $grid,
            'conflits' => $conflits,
            'conflitKeys' => $conflitKeys,
            'vue' => $vue,
            'lastAppliedGenerationUuid' => $lastAppliedGenerationUuid,
            'lastIaRun' => $lastIaRun,
            'adjustmentsLearningEnabled' => $adjustmentsLearningEnabled,
            'adjustmentsCount' => $adjustmentsCount,
            'parametresRoute' => route('emploi-du-temps.parametres.edit', array_filter([
                'annee_scolaire_id' => $anneeId,
            ])),
            'iaCreateRoute' => route('emploi-du-temps.create', array_filter([
                'annee_scolaire_id' => $anneeId,
            ])),
        ]);
    }

    public function create(Request $request)
    {
        $etabId = $request->user()->etablissement_id;

        $annees = $this->annees($etabId);
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $annees);
        $anneeActive = $annees->firstWhere('id', old('annee_scolaire_id', $anneeId));

        $classes = $this->classes($etabId, $anneeActive?->id);
        $salles = $this->salles($etabId);
        $creneaux = $this->creneauxCours($etabId);

        $parametres = $this->resolveEdtParametres($etabId, $anneeActive?->id);
        $lastIaRun = $this->resolveLastIaRun($etabId, $anneeActive?->id);

        $joursAutorises = $parametres['jours_autorises_json']
            ?? ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];

        $creneauxAutorises = collect($parametres['creneaux_autorises_json'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $sallesAutorisees = collect($parametres['salles_autorisees_json'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $vacataires = $this->vacataires($etabId);
        $vacatairesStats = $this->resolveVacatairesStats($etabId, $anneeActive?->id);
        $vacataireImports = $this->resolveRecentVacataireImports($etabId, $anneeActive?->id);

        // Vacataires couverts par un EDT externe validé (compte comme "horaire fourni")
        $externalCoveredIds = Schema::hasTable('edt_enseignant_horaires_externes')
            ? EnseignantHoraireExterne::query()
                ->whereIn('enseignant_id', $vacataires->pluck('id'))
                ->where('valide', true)
                ->when($anneeActive?->id, fn ($q) =>
                    $q->where(fn ($q2) =>
                        $q2->where('annee_scolaire_id', $anneeActive->id)->orWhereNull('annee_scolaire_id')
                    )
                )
                ->distinct()->pluck('enseignant_id')
                ->map(fn ($v) => (int) $v)
                ->values()
            : collect();

        return view('emploi-du-temps.create', [
            'annees' => $annees,
            'anneeActive' => $anneeActive,
            'classes' => $classes,
            'salles' => $salles,
            'creneaux' => $creneaux,
            'jours' => EmploiDuTemps::jours(),
            'iaResult' => session('ia_result'),

            // bloc grille
            'grilleEntry' => [
                'route' => route('emploi-du-temps.grille', array_filter([
                    'annee_scolaire_id' => $anneeActive?->id,
                ])),
            ],

            // bloc IA unique
            'iaConfig' => [
                'enabled' => true,
                'mode_generation_defaut' => $parametres['mode_generation_defaut'] ?? 'prive_equilibre',
                'policy_id' => $parametres['policy_id'] ?? null,
                'jours_autorises' => $joursAutorises,
                'creneaux_autorises' => $creneauxAutorises,
                'salles_autorisees' => $sallesAutorisees,
                'attendre_horaires_vacataires' => (bool) ($parametres['attendre_horaires_vacataires'] ?? true),
                'bloquer_si_vacataire_sans_horaire' => (bool) ($parametres['bloquer_si_vacataire_sans_horaire'] ?? true),
                'respecter_imports_vacataires' => (bool) ($parametres['respecter_imports_vacataires'] ?? true),
                'prioriser_classes_examen' => (bool) ($parametres['prioriser_classes_examen'] ?? false),
                'prioriser_permanents' => (bool) ($parametres['prioriser_permanents'] ?? true),
                'autoriser_reduction_heures' => (bool) ($parametres['autoriser_reduction_heures'] ?? false),
                'max_reduction_minutes_par_classe' => (int) ($parametres['max_reduction_minutes_par_classe'] ?? 0),
                'max_reduction_minutes_par_matiere' => (int) ($parametres['max_reduction_minutes_par_matiere'] ?? 0),
                'respecter_tp_consecutifs' => (bool) ($parametres['respecter_tp_consecutifs'] ?? true),
                'eviter_eps_heures_chaudes' => (bool) ($parametres['eviter_eps_heures_chaudes'] ?? true),
                'limiter_niveaux_prof' => (bool) ($parametres['limiter_niveaux_prof'] ?? true),
                'max_niveaux_par_prof' => (int) ($parametres['max_niveaux_par_prof'] ?? 3),
                'limiter_heures_creuses' => (bool) ($parametres['limiter_heures_creuses'] ?? true),
                'max_heures_creuses_prof' => (int) ($parametres['max_heures_creuses_prof'] ?? 2),
                'autoriser_trous' => (bool) ($parametres['autoriser_trous'] ?? false),
                'tolerer_surcharge_legere' => (bool) ($parametres['tolerer_surcharge_legere'] ?? false),
                'notes_generation' => $parametres['notes_generation'] ?? null,
            ],

            'lastIaRun' => $lastIaRun,
            'vacataires' => $vacataires,
            'vacataireImports' => $vacataireImports,
            'vacatairesStats' => $vacatairesStats,
            'externalCoveredIds' => $externalCoveredIds,

            'statsIa' => [
                'classes' => $classes->count(),
                'salles' => $salles->count(),
                'creneaux' => $creneaux->count(),
                'vacataires' => $vacatairesStats['vacataires_total'],
                'vacataires_valides' => $vacatairesStats['imports_valides'],
            ],
        ]);
    }

    /**
     * Legacy IA - conserve tes routes existantes tant que le flux unifié n'est pas totalement supprimé.
     */
    public function iaSuggestions(EmploiDuTempsIAGenerateRequest $request)
    {
        $etabId = $request->user()->etablissement_id;
        $data = $request->validated();

        abort_if(empty($data['classe_id']), 422, 'Veuillez sélectionner une classe.');

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($data['annee_scolaire_id']);

        $classe = Classe::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($data['classe_id']);

        $result = $this->iaService->suggererPourClasse($classe, $annee, $data);

        if (!($result['success'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['ia' => $result['message'] ?? 'Impossible d’analyser cet emploi du temps.']);
        }

        return redirect()
            ->route('emploi-du-temps.create', array_filter([
                'annee_scolaire_id' => $data['annee_scolaire_id'] ?? null,
            ]))
            ->withInput()
            ->with('ia_result', $result);
    }

    public function genererClasse(EmploiDuTempsIAGenerateRequest $request)
    {
        $etabId = $request->user()->etablissement_id;
        $data = $request->validated();

        abort_if(empty($data['classe_id']), 422, 'Veuillez sélectionner une classe.');

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($data['annee_scolaire_id']);

        $classe = Classe::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($data['classe_id']);

        $result = $this->iaService->genererPourClasse($classe, $annee, $data);

        if (!($result['success'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['ia' => $result['message'] ?? 'La génération IA a échoué.']);
        }

        return redirect()
            ->route('emploi-du-temps.index')
            ->with('success', "Génération terminée : {$result['created']} créés, {$result['ignored']} ignorés, score {$result['score']}%.");
    }

    public function genererGlobal(EmploiDuTempsIAGenerateRequest $request)
    {
        $etabId = $request->user()->etablissement_id;
        $data = $request->validated();

        $result = $this->iaService->genererGlobal($etabId, $data['annee_scolaire_id'], $data);

        if (!($result['success'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['ia' => $result['message'] ?? 'La génération globale IA a échoué.']);
        }

        return redirect()
            ->route('emploi-du-temps.index')
            ->with('success', "Génération globale terminée : {$result['classes_traitees']} classes, {$result['created']} créés, {$result['ignored']} ignorés.");
    }

    public function store(EmploiDuTempsRequest $request)
    {
        $etabId = $request->user()->etablissement_id;
        $data = $request->validated();
        $this->assertScopedData($data, $etabId);

        $conflits = $this->detectConflitsManuels($data);

        if (!empty($conflits)) {
            if ($request->input('_redirect')) {
                session([
                    'modal_mode' => 'create',
                    'modal_form' => array_merge($data, ['id' => null]),
                ]);

                return redirect($request->input('_redirect'))
                    ->withInput()
                    ->withErrors(['jour' => implode(' ', $conflits)]);
            }

            return back()
                ->withInput()
                ->withErrors(['jour' => implode(' ', $conflits)]);
        }

        $emploi = EmploiDuTemps::create(array_merge($data, [
            'etablissement_id' => $etabId,
            'source' => 'manuel',
            'locked_by_user' => $request->boolean('lock_for_future', true),
            'last_adjusted_by' => $request->user()->id,
            'last_adjusted_at' => now(),
        ]));

        EmploiDuTempsAdjustment::create([
            'emploi_du_temps_id' => $emploi->id,
            'etablissement_id' => $emploi->etablissement_id,
            'annee_scolaire_id' => $emploi->annee_scolaire_id,
            'user_id' => $request->user()->id,
            'action' => 'create',
            'generation_uuid' => $emploi->generation_uuid,
            'old_payload' => null,
            'new_payload' => $emploi->only([
                'annee_scolaire_id',
                'jour',
                'creneau_id',
                'classe_id',
                'matiere_id',
                'enseignant_id',
                'salle_id',
                'valide_du',
                'valide_au',
                'actif',
            ]),
            'reason' => $request->input('adjustment_reason'),
            'used_for_learning' => $request->boolean('lock_for_future', true),
        ]);

        $this->learningService->rebuildRulesForEtablissement(
            $emploi->etablissement_id,
            $emploi->annee_scolaire_id
        );

        $redirect = $request->input('_redirect')
            ? redirect($request->input('_redirect'))
            : redirect()->route('emploi-du-temps.index');

        return $redirect->with('success', 'Créneau créé et mémorisé pour l’IA.');
    }

    public function update(EmploiDuTempsRequest $request, EmploiDuTemps $emploi)
    {
        abort_unless($emploi->etablissement_id === $request->user()->etablissement_id, 404);

        $etabId = $request->user()->etablissement_id;
        $data = $request->validated();
        $this->assertScopedData($data, $etabId);

        $before = $emploi->only([
            'annee_scolaire_id',
            'jour',
            'creneau_id',
            'classe_id',
            'matiere_id',
            'enseignant_id',
            'salle_id',
            'valide_du',
            'valide_au',
            'actif',
        ]);

        $conflits = $this->detectConflitsManuels($data, $emploi->id);

        if (!empty($conflits)) {
            if ($request->input('_redirect')) {
                session([
                    'modal_mode' => 'edit',
                    'modal_form' => array_merge($data, ['id' => $emploi->id]),
                ]);

                return redirect($request->input('_redirect'))
                    ->withInput()
                    ->withErrors(['jour' => implode(' ', $conflits)]);
            }

            return back()
                ->withInput()
                ->withErrors(['jour' => implode(' ', $conflits)]);
        }

        $emploi->update(array_merge($data, [
            'source' => 'ajustement',
            'locked_by_user' => $request->boolean('lock_for_future', true),
            'last_adjusted_by' => $request->user()->id,
            'last_adjusted_at' => now(),
        ]));

        EmploiDuTempsAdjustment::create([
            'emploi_du_temps_id' => $emploi->id,
            'etablissement_id' => $emploi->etablissement_id,
            'annee_scolaire_id' => $emploi->annee_scolaire_id,
            'user_id' => $request->user()->id,
            'action' => $this->resolveAdjustmentAction($before, $emploi->fresh()),
            'generation_uuid' => $emploi->generation_uuid,
            'old_payload' => $before,
            'new_payload' => $emploi->fresh()->only([
                'annee_scolaire_id',
                'jour',
                'creneau_id',
                'classe_id',
                'matiere_id',
                'enseignant_id',
                'salle_id',
                'valide_du',
                'valide_au',
                'actif',
            ]),
            'reason' => $request->input('adjustment_reason'),
            'used_for_learning' => $request->boolean('lock_for_future', true),
        ]);

        $this->learningService->rebuildRulesForEtablissement(
            $emploi->etablissement_id,
            $emploi->annee_scolaire_id
        );

        $redirect = $request->input('_redirect')
            ? redirect($request->input('_redirect'))
            : redirect()->route('emploi-du-temps.index');

        return $redirect->with('success', 'Ajustement enregistré et mémorisé pour l’IA.');
    }

    public function destroy(Request $request, EmploiDuTemps $emploi)
    {
        abort_unless($emploi->etablissement_id === $request->user()->etablissement_id, 404);

        EmploiDuTempsAdjustment::create([
            'emploi_du_temps_id' => $emploi->id,
            'etablissement_id' => $emploi->etablissement_id,
            'annee_scolaire_id' => $emploi->annee_scolaire_id,
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'generation_uuid' => $emploi->generation_uuid,
            'old_payload' => $emploi->only([
                'annee_scolaire_id',
                'jour',
                'creneau_id',
                'classe_id',
                'matiere_id',
                'enseignant_id',
                'salle_id',
                'valide_du',
                'valide_au',
                'actif',
            ]),
            'new_payload' => null,
            'reason' => $request->input('adjustment_reason'),
            'used_for_learning' => true,
        ]);

        $etabId = $emploi->etablissement_id;
        $anneeId = $emploi->annee_scolaire_id;

        $emploi->delete();

        $this->learningService->rebuildRulesForEtablissement($etabId, $anneeId);

        $redirect = $request->input('_redirect')
            ? redirect($request->input('_redirect'))
            : redirect()->route('emploi-du-temps.index');

        return $redirect->with('success', 'Créneau supprimé et pris en compte pour l’IA.');
    }

    public function toggle(Request $request, EmploiDuTemps $emploi)
    {
        abort_unless($emploi->etablissement_id === $request->user()->etablissement_id, 404);

        $emploi->update(['actif' => !$emploi->actif]);

        return back()->with('success', 'État mis à jour.');
    }

    public function conflits(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = $request->input('annee_scolaire_id')
            ? (int) $request->input('annee_scolaire_id')
            : null;

        return view('emploi-du-temps.conflits', [
            'conflits' => $this->iaService->detecterConflits($etabId, $anneeId),
            'annees' => $this->annees($etabId),
        ]);
    }

    private function resolveAdjustmentAction(array $before, EmploiDuTemps $after): string
    {
        if (($before['jour'] ?? null) !== $after->jour || ($before['creneau_id'] ?? null) != $after->creneau_id) {
            return 'move';
        }

        if (($before['enseignant_id'] ?? null) != $after->enseignant_id) {
            return 'assign_teacher';
        }

        if (($before['salle_id'] ?? null) != $after->salle_id) {
            return 'change_room';
        }

        if ((bool) ($after->locked_by_user ?? false)) {
            return 'lock';
        }

        return 'update';
    }

    private function detectConflitsManuels(array $data, ?int $ignoreId = null): array
    {
        $errors = [];

        $base = EmploiDuTemps::query()
            ->where('annee_scolaire_id', $data['annee_scolaire_id'])
            ->where('jour', $data['jour'])
            ->where('creneau_id', $data['creneau_id'])
            ->where('actif', true);

        if ($ignoreId) {
            $base->whereKeyNot($ignoreId);
        }

        $this->applyDateOverlap($base, $data);

        if ((clone $base)->where('classe_id', $data['classe_id'])->exists()) {
            $errors[] = 'Conflit : la classe a déjà un cours sur ce créneau.';
        }

        if (!empty($data['enseignant_id']) && (clone $base)->where('enseignant_id', $data['enseignant_id'])->exists()) {
            $errors[] = 'Conflit : l’enseignant est déjà occupé sur ce créneau.';
        }

        if ((clone $base)->where('salle_id', $data['salle_id'])->exists()) {
            $errors[] = 'Conflit : la salle est déjà occupée sur ce créneau.';
        }

        return $errors;
    }

    private function assertScopedData(array $data, int $etabId): void
    {
        $anneeOk = AnneeScolaire::where('id', $data['annee_scolaire_id'])
            ->where('etablissement_id', $etabId)
            ->exists();

        $classeOk = Classe::where('id', $data['classe_id'])
            ->where('etablissement_id', $etabId)
            ->exists();

        $matiereOk = Matiere::where('id', $data['matiere_id'])
            ->where('etablissement_id', $etabId)
            ->exists();

        $salleOk = Salle::where('id', $data['salle_id'])
            ->where('etablissement_id', $etabId)
            ->exists();

        $enseignantOk = true;
        if (!empty($data['enseignant_id'])) {
            $enseignantOk = Enseignant::where('id', $data['enseignant_id'])
                ->where('etablissement_id', $etabId)
                ->exists();
        }

        abort_unless($anneeOk && $classeOk && $matiereOk && $enseignantOk && $salleOk, 403);

        if (Schema::hasColumn('creneaux', 'etablissement_id')) {
            $creneauOk = Creneau::where('id', $data['creneau_id'])
                ->where('etablissement_id', $etabId)
                ->exists();

            abort_unless($creneauOk, 403);
        }
    }

    private function applyDateOverlap($query, array $data): void
    {
        $from = $data['valide_du'] ?? null;
        $to = $data['valide_au'] ?? null;

        if ($from && $to) {
            $query->where(function ($q) use ($to) {
                $q->whereNull('valide_du')->orWhere('valide_du', '<=', $to);
            })->where(function ($q) use ($from) {
                $q->whereNull('valide_au')->orWhere('valide_au', '>=', $from);
            });
        }
    }

    private function annees(int $etabId)
    {
        return AnneeScolaire::where('etablissement_id', $etabId)
            ->orderByDesc('date_debut')
            ->get();
    }

    /**
     * Année affichée : paramètre URL, sinon année scolaire en cours (contexte), sinon la plus récente.
     */
    private function resolveAnneeScolaireId(Request $request, int $etabId, Collection $annees): ?int
    {
        if ($request->filled('annee_scolaire_id')) {
            return $request->integer('annee_scolaire_id');
        }

        $ctx = AnneeScolaireContext::courantePourEtablissement($etabId);
        if ($ctx && EmploiDuTemps::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $ctx->id)
            ->exists()) {
            return $ctx->id;
        }

        $meilleure = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->selectRaw('annee_scolaire_id, COUNT(*) as c')
            ->groupBy('annee_scolaire_id')
            ->orderByDesc('c')
            ->first();

        if ($meilleure?->annee_scolaire_id) {
            return (int) $meilleure->annee_scolaire_id;
        }

        return $ctx?->id ?? $annees->first()?->id;
    }

    private function classes(int $etabId, ?int $anneeScolaireId = null)
    {
        return Classe::where('etablissement_id', $etabId)
            ->where('active', true)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->orderBy('nom')
            ->get();
    }

    private function matieres(int $etabId)
    {
        return Matiere::where('etablissement_id', $etabId)
            ->where('active', true)
            ->orderBy('nom')
            ->get();
    }

    private function enseignants(int $etabId)
    {
        return Enseignant::where('etablissement_id', $etabId)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    private function vacataires(int $etabId)
    {
        return Enseignant::where('etablissement_id', $etabId)
            ->where('actif', true)
            ->where('statut', Enseignant::STATUT_VACATAIRE)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    private function salles(int $etabId)
    {
        return Salle::where('etablissement_id', $etabId)
            ->where('active', true)
            ->orderBy('nom')
            ->get();
    }

    private function creneaux(int $etabId)
    {
        $query = Creneau::query();

        if (Schema::hasColumn('creneaux', 'etablissement_id')) {
            $query->where('etablissement_id', $etabId);
        }

        if (Schema::hasColumn('creneaux', 'actif')) {
            $query->where('actif', true);
        }

        if (Schema::hasColumn('creneaux', 'ordre')) {
            $query->orderBy('ordre');
        } elseif (Schema::hasColumn('creneaux', 'heure_debut')) {
            $query->orderBy('heure_debut');
        } else {
            $query->orderBy('id');
        }

        return $query->get();
    }

    private function creneauxCours(int $etabId)
    {
        $query = Creneau::query();

        if (Schema::hasColumn('creneaux', 'etablissement_id')) {
            $query->where('etablissement_id', $etabId);
        }

        if (Schema::hasColumn('creneaux', 'actif')) {
            $query->where('actif', true);
        }

        if (Schema::hasColumn('creneaux', 'type')) {
            $query->where('type', Creneau::TYPE_COURS);
        }

        if (Schema::hasColumn('creneaux', 'ordre')) {
            $query->orderBy('ordre');
        } elseif (Schema::hasColumn('creneaux', 'heure_debut')) {
            $query->orderBy('heure_debut');
        } else {
            $query->orderBy('id');
        }

        return $query->get();
    }

    public function pdfClasse(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $classeId = $request->integer('classe_id');
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $this->annees($etabId));

        abort_if(!$classeId, 422, 'Veuillez sélectionner une classe.');

        $classe = Classe::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($classeId);

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($anneeId);

        $documents = [$this->buildClassePdfDocument($etabId, $annee, $classe)];

        return $this->downloadPdf(
            view: 'emploi-du-temps.pdf.classes',
            data: [
                'documents' => $documents,
                'annee' => $annee,
                'etablissement' => $request->user()->etablissement,
            ],
            filename: 'emploi-du-temps-classe-' . $this->safeFilename($classe->nom) . '.pdf',
            orientation: 'portrait'
        );
    }

    public function pdfClasses(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $this->annees($etabId));

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($anneeId);

        $classes = Classe::query()
            ->where('etablissement_id', $etabId)
            ->where('active', true)
            ->when($request->filled('classe_id'), fn ($q) => $q->where('id', $request->integer('classe_id')))
            ->orderBy('nom')
            ->get();

        abort_if($classes->isEmpty(), 422, 'Aucune classe à imprimer.');

        $documents = $classes
            ->map(fn ($classe) => $this->buildClassePdfDocument($etabId, $annee, $classe))
            ->all();

        return $this->downloadPdf(
            view: 'emploi-du-temps.pdf.classes',
            data: [
                'documents' => $documents,
                'annee' => $annee,
                'etablissement' => $request->user()->etablissement,
            ],
            filename: 'emploi-du-temps-classes-' . $annee->libelle . '.pdf'
        );
    }

    public function pdfClassesCustom(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = (int) $request->input('annee_scolaire_id');
        $classIds = collect($request->input('class_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($classIds->isEmpty(), 422, 'Veuillez sélectionner au moins une classe.');

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($anneeId);

        $classes = Classe::query()
            ->where('etablissement_id', $etabId)
            ->whereIn('id', $classIds)
            ->orderBy('nom')
            ->get();

        abort_if($classes->isEmpty(), 422, 'Aucune classe valide à imprimer.');

        $documents = $classes
            ->map(fn ($classe) => $this->buildClassePdfDocument($etabId, $annee, $classe))
            ->all();

        return $this->downloadPdf(
            view: 'emploi-du-temps.pdf.classes',
            data: [
                'documents' => $documents,
                'annee' => $annee,
                'etablissement' => $request->user()->etablissement,
            ],
            filename: 'emploi-du-temps-classes-personnalise-' . $annee->libelle . '.pdf'
        );
    }

    public function pdfProfesseur(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $enseignantId = $request->integer('enseignant_id');
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $this->annees($etabId));

        abort_if(!$enseignantId, 422, 'Veuillez sélectionner un professeur.');

        $enseignant = Enseignant::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($enseignantId);

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($anneeId);

        $documents = [$this->buildProfesseurPdfDocument($etabId, $annee, $enseignant)];

        return $this->downloadPdf(
            view: 'emploi-du-temps.pdf.professeurs',
            data: [
                'documents' => $documents,
                'annee' => $annee,
                'etablissement' => $request->user()->etablissement,
            ],
            filename: 'emploi-du-temps-professeur-' . $this->safeFilename($enseignant->nom . '-' . $enseignant->prenom) . '.pdf',
            orientation: 'portrait'
        );
    }

    public function pdfProfesseurs(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = $this->resolveAnneeScolaireId($request, $etabId, $this->annees($etabId));

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($anneeId);

        $enseignants = Enseignant::query()
            ->where('etablissement_id', $etabId)
            ->where('actif', true)
            ->when($request->filled('enseignant_id'), fn ($q) => $q->where('id', $request->integer('enseignant_id')))
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        abort_if($enseignants->isEmpty(), 422, 'Aucun professeur à imprimer.');

        $documents = $enseignants
            ->map(fn ($enseignant) => $this->buildProfesseurPdfDocument($etabId, $annee, $enseignant))
            ->all();

        return $this->downloadPdf(
            view: 'emploi-du-temps.pdf.professeurs',
            data: [
                'documents' => $documents,
                'annee' => $annee,
                'etablissement' => $request->user()->etablissement,
            ],
            filename: 'emploi-du-temps-professeurs-' . $annee->libelle . '.pdf'
        );
    }

    public function pdfProfesseursCustom(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = (int) $request->input('annee_scolaire_id');
        $enseignantIds = collect($request->input('enseignant_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($enseignantIds->isEmpty(), 422, 'Veuillez sélectionner au moins un professeur.');

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail($anneeId);

        $enseignants = Enseignant::query()
            ->where('etablissement_id', $etabId)
            ->whereIn('id', $enseignantIds)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        abort_if($enseignants->isEmpty(), 422, 'Aucun professeur valide à imprimer.');

        $documents = $enseignants
            ->map(fn ($enseignant) => $this->buildProfesseurPdfDocument($etabId, $annee, $enseignant))
            ->all();

        return $this->downloadPdf(
            view: 'emploi-du-temps.pdf.professeurs',
            data: [
                'documents' => $documents,
                'annee' => $annee,
                'etablissement' => $request->user()->etablissement,
            ],
            filename: 'emploi-du-temps-professeurs-personnalise-' . $annee->libelle . '.pdf'
        );
    }

    private function downloadPdf(string $view, array $data, string $filename, string $orientation = 'landscape')
    {
        return Pdf::setOption([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'dpi' => 120,
        ])
            ->loadView($view, $data)
            ->setPaper('a4', $orientation)
            ->download($filename);
    }

    private function buildClassePdfDocument(int $etabId, AnneeScolaire $annee, Classe $classe): array
    {
        $jours = EmploiDuTemps::jours();
        $creneaux = $this->creneaux($etabId);

        $emplois = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('classe_id', $classe->id)
            ->where('actif', true)
            ->with(['matiere', 'enseignant', 'salle', 'creneau'])
            ->orderBy('jour')
            ->orderBy('creneau_id')
            ->get();

        $grid = [];
        foreach ($jours as $jour) {
            foreach ($creneaux as $creneau) {
                $grid[$jour][$creneau->id] = collect();
            }
        }

        foreach ($emplois as $seance) {
            $grid[$seance->jour][$seance->creneau_id]->push($seance);
        }

        $matiereProfesseurs = $emplois
            ->groupBy('matiere_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'matiere' => $first?->matiere?->nom ?? '—',
                    'enseignant' => $items
                        ->map(fn ($i) => $i->enseignant?->nom_complet ?? $i->enseignant?->nom)
                        ->filter()
                        ->unique()
                        ->implode(', '),
                ];
            })
            ->values();

        return [
            'type' => 'classe',
            'classe' => $classe,
            'jours' => $jours,
            'creneaux' => $creneaux,
            'grid' => $grid,
            'emplois' => $emplois,
            'matiere_professeurs' => $matiereProfesseurs,
            'professeur_principal' => $classe->professeur_principal_nom ?? $classe->professeurPrincipal?->nom_complet ?? '—',
            'educateur' => $classe->educateur_nom ?? $classe->educateur?->nom_complet ?? '—',
        ];
    }

    private function buildProfesseurPdfDocument(int $etabId, AnneeScolaire $annee, Enseignant $enseignant): array
    {
        $jours = EmploiDuTemps::jours();
        $creneaux = $this->creneaux($etabId);

        $emplois = EmploiDuTemps::query()
            ->where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('enseignant_id', $enseignant->id)
            ->where('actif', true)
            ->with(['classe', 'matiere', 'salle', 'creneau'])
            ->orderBy('jour')
            ->orderBy('creneau_id')
            ->get();

        $grid = [];
        foreach ($jours as $jour) {
            foreach ($creneaux as $creneau) {
                $grid[$jour][$creneau->id] = null;
            }
        }

        foreach ($emplois as $seance) {
            $grid[$seance->jour][$seance->creneau_id] = $seance;
        }

        $parClasse = $emplois
            ->groupBy(fn ($item) => $item->classe_id ?: ('x-' . $item->id))
            ->map(function (Collection $items) {
                $first = $items->first();
                $classe = $first?->classe;
                $nbHeures = $items->count();

                return [
                    'classe' => $classe?->nom ?? '—',
                    'effectif' => $this->resolveClasseEffectif($classe),
                    'discipline' => $items
                        ->pluck('matiere.code')
                        ->filter()
                        ->unique()
                        ->implode('/'),
                    'heures_a' => $nbHeures,
                    'heures_a_label' => $this->formatHourValueInt($nbHeures),
                ];
            })
            ->values();

        $slotCount = max(8, $parClasse->count());

        $slots = collect(range(1, $slotCount))
            ->map(function ($i) use ($parClasse) {
                $row = $parClasse->get($i - 1);

                return $row ?: [
                    'classe' => '',
                    'effectif' => '',
                    'discipline' => '',
                    'heures_a' => 0,
                    'heures_a_label' => '',
                ];
            })
            ->all();

        $totalA = (int) $parClasse->sum('heures_a');
        $complementB = (int) ($enseignant->complement_service ?? 0);

        $decharges = [
            'PP' => (int) ($enseignant->decharge_pp ?? 0),
            'CE' => (int) ($enseignant->decharge_ce ?? 0),
            'LABO' => (int) ($enseignant->decharge_labo ?? 0),
            'BIBLIO/CDI' => (int) ($enseignant->decharge_biblio ?? 0),
            'UP' => (int) ($enseignant->decharge_up ?? 0),
        ];
        $totalC = array_sum($decharges);

        $augmentationD = (int) ($enseignant->augmentation_service ?? 0);

        $serviceTotal = $totalA + $complementB + $totalC - $augmentationD;
        $maxService = $this->resolveMaxService($enseignant);
        $heuresSup = max(0, $serviceTotal - $maxService);

        $disciplineFromSchedule = $emplois->pluck('matiere.code')->filter()->unique()->sort()->implode('/');

        return [
            'type' => 'professeur',
            'enseignant' => $enseignant,
            'jours' => $jours,
            'creneaux' => $creneaux,
            'grid' => $grid,
            'emplois' => $emplois,
            'discipline' => $disciplineFromSchedule ?: ($enseignant->specialite ?? '—'),
            'corps' => $enseignant->corps ?? ($enseignant->statut ?? 'ENSEIGNANT'),
            'matricule' => $enseignant->matricule_mena ?? '—',
            'recap' => [
                'slots' => $slots,
                'total_a' => $this->formatHourValueInt($totalA),
                'total_b' => $this->formatHourValueOrBlankInt($complementB),
                'total_c' => $this->formatHourValueOrBlankInt($totalC),
                'total_d' => $this->formatHourValueOrBlankInt($augmentationD),
                'service_total' => $this->formatHourValueInt($serviceTotal),
                'max_service' => $this->formatHourValueInt($maxService),
                'heures_sup' => $this->formatHourValueOrBlankInt($heuresSup),
                'decharges_labels' => ['PP', 'CE', 'LABO', 'BIBLIO/CDI', 'UP'],
                'decharges_values' => [
                    $this->formatHourValueOrBlankInt($decharges['PP']),
                    $this->formatHourValueOrBlankInt($decharges['CE']),
                    $this->formatHourValueOrBlankInt($decharges['LABO']),
                    $this->formatHourValueOrBlankInt($decharges['BIBLIO/CDI']),
                    $this->formatHourValueOrBlankInt($decharges['UP']),
                ],
                'vacataires' => [
                    'Prof.Agr' => '04H',
                    'PL' => '06H',
                    'PC' => '08H',
                ],
                'permanents' => '25H',
                'signature_place' => strtoupper(optional($enseignant->etablissement)->ville ?? 'ABIDJAN'),
                'signature_date' => now()->locale('fr')->translatedFormat('d F Y'),
            ],
        ];
    }

    private function resolveClasseEffectif($classe): string
    {
        if (!$classe) {
            return '';
        }

        foreach (['effectif', 'effectif_total', 'nb_eleves', 'nombre_eleves', 'total_eleves'] as $attr) {
            if (isset($classe->{$attr}) && $classe->{$attr} !== null && $classe->{$attr} !== '') {
                return (string) $classe->{$attr};
            }
        }

        if (method_exists($classe, 'eleves')) {
            try {
                return (string) $classe->eleves()->count();
            } catch (\Throwable $e) {
            }
        }

        return '';
    }

    private function resolveMaxService(Enseignant $enseignant): int
    {
        $corps = Str::lower(
            Str::ascii(
                trim(
                    ($enseignant->corps ?? '') . ' ' .
                    ($enseignant->statut_libelle ?? '') . ' ' .
                    ($enseignant->statut ?? '')
                )
            )
        );

        if (str_contains($corps, 'vacataire') || str_contains($corps, 'vacat')) {
            if (str_contains($corps, 'agr')) {
                return 4;
            }

            if (preg_match('/\bpl\b/', $corps)) {
                return 6;
            }

            if (preg_match('/\bpc\b/', $corps)) {
                return 8;
            }

            return 8;
        }

        return 25;
    }

    private function formatHourValueInt(int $value): string
    {
        return max(0, $value) . 'H';
    }

    private function formatHourValueOrBlankInt(int $value): string
    {
        return $value > 0 ? ($value . 'H') : '';
    }

    private function safeFilename(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-');
    }

    private function resolveEdtParametres(int $etablissementId, ?int $anneeScolaireId): array
    {
        if (!Schema::hasTable('edt_parametres')) {
            return [];
        }

        $row = DB::table('edt_parametres')
            ->where('etablissement_id', $etablissementId)
            ->where('actif', 1)
            ->where(function ($q) use ($anneeScolaireId) {
                if ($anneeScolaireId) {
                    $q->where('annee_scolaire_id', $anneeScolaireId)
                        ->orWhereNull('annee_scolaire_id');
                } else {
                    $q->whereNull('annee_scolaire_id');
                }
            })
            ->orderByRaw('CASE WHEN annee_scolaire_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();

        if (!$row) {
            return [];
        }

        $data = (array) $row;

        foreach (['jours_autorises_json', 'creneaux_autorises_json', 'salles_autorisees_json'] as $jsonField) {
            $data[$jsonField] = !empty($data[$jsonField])
                ? (json_decode($data[$jsonField], true) ?: [])
                : [];
        }

        return $data;
    }

    private function resolveLastIaRun(int $etablissementId, ?int $anneeScolaireId): ?array
    {
        if (!Schema::hasTable('edt_generation_runs')) {
            return null;
        }

        $run = DB::table('edt_generation_runs')
            ->where('etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->latest('id')
            ->first();

        if (!$run) {
            return null;
        }

        $summary = !empty($run->summary_json)
            ? (json_decode($run->summary_json, true) ?: [])
            : [];

        $conformite = !empty($run->conformite_json)
            ? (json_decode($run->conformite_json, true) ?: [])
            : [];

        return [
            'id' => $run->id,
            'run_uuid' => $run->run_uuid,
            'status' => $run->status,
            'score_global' => $run->score_global ?? ($conformite['score_global'] ?? null),
            'assignments_count' => $summary['assignments_count'] ?? 0,
            'issues_count' => $summary['issues_count'] ?? 0,
            'created_at' => $run->created_at,
            'started_at' => $run->started_at,
            'finished_at' => $run->finished_at,
        ];
    }

    private function resolveVacatairesStats(int $etablissementId, ?int $anneeScolaireId): array
    {
        $vacatairesTotal = Enseignant::query()
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->where('statut', Enseignant::STATUT_VACATAIRE)
            ->count();

        if (!Schema::hasTable('edt_vacataire_imports')) {
            return [
                'vacataires_total' => $vacatairesTotal,
                'imports_total' => 0,
                'imports_uploades' => 0,
                'imports_parsed' => 0,
                'imports_valides' => 0,
            ];
        }

        $importsBase = DB::table('edt_vacataire_imports')
            ->where('etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId));

        return [
            'vacataires_total' => $vacatairesTotal,
            'imports_total' => (clone $importsBase)->count(),
            'imports_uploades' => (clone $importsBase)->where('status', 'uploaded')->count(),
            'imports_parsed' => (clone $importsBase)->where('status', 'parsed')->count(),
            'imports_valides' => (clone $importsBase)->where('status', 'validated')->count(),
        ];
    }

    private function resolveRecentVacataireImports(int $etablissementId, ?int $anneeScolaireId, int $limit = 10)
    {
        if (!Schema::hasTable('edt_vacataire_imports')) {
            return collect();
        }

        return EdtVacataireImport::query()
            ->where('etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->with(['enseignant'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function resolveLastAppliedGenerationUuid(
        int $etablissementId,
        ?int $anneeScolaireId = null,
        ?int $classeId = null,
        ?int $enseignantId = null
    ): ?string {
        if (!Schema::hasColumn('emploi_du_temps', 'generation_uuid')) {
            return null;
        }

        return EmploiDuTemps::query()
            ->where('etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
            ->when($enseignantId, fn ($q) => $q->where('enseignant_id', $enseignantId))
            ->whereNotNull('generation_uuid')
            ->latest('id')
            ->value('generation_uuid');
    }

    private function resolveAdjustmentsCount(
        int $etablissementId,
        ?int $anneeScolaireId = null,
        ?int $classeId = null,
        ?int $enseignantId = null
    ): int {
        if (!Schema::hasTable('emploi_du_temps_adjustments')) {
            return 0;
        }

        $query = DB::table('emploi_du_temps_adjustments as a')
            ->leftJoin('emploi_du_temps as e', 'e.id', '=', 'a.emploi_du_temps_id')
            ->where('a.etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('a.annee_scolaire_id', $anneeScolaireId));

        if ($classeId) {
            $query->where('e.classe_id', $classeId);
        }

        if ($enseignantId) {
            $query->where('e.enseignant_id', $enseignantId);
        }

        return (int) $query->count();
    }
}