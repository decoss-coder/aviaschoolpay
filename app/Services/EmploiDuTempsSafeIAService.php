<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\EnseignantHoraireExterne;
use App\Models\Matiere;
use App\Models\Salle;
use App\Services\EmploiDuTemps\EmploiDuTempsConflictGuard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmploiDuTempsSafeIAService extends EmploiDuTempsIAService
{
    public function suggererPourClasse(Classe $classe, AnneeScolaire $annee, array $params = []): array
    {
        $settings = $this->resolveGenerationSettings($classe->etablissement_id, $annee->id, $params);

        $jours = collect($settings['jours'])
            ->filter()
            ->values()
            ->all();

        $creneaux = $this->chargerCreneauxSafe($classe->etablissement_id, $settings['creneau_ids']);
        $salles = $this->chargerSallesSafe($classe->etablissement_id, $settings['salle_ids']);
        $matieres = $this->chargerMatieresSafe($classe->etablissement_id);
        $enseignants = $this->chargerEnseignantsSafe($classe->etablissement_id);
        $affectations = $this->chargerAffectationsSafe($classe, $annee);
        $lockedSessions = $this->chargerLockedSessionsSafe($classe->etablissement_id, $annee->id);
        $externalSlots = $this->chargerHorairesExternesSafe($classe->etablissement_id, $annee->id, $enseignants);

        if (empty($jours)) {
            return $this->errorResultSafe($classe->nom, 'Aucun jour autorisé.');
        }

        if ($creneaux->isEmpty()) {
            return $this->errorResultSafe($classe->nom, 'Aucun créneau de cours actif disponible.');
        }

        if ($salles->isEmpty()) {
            return $this->errorResultSafe($classe->nom, 'Aucune salle active disponible.');
        }

        if ($matieres->isEmpty()) {
            return $this->errorResultSafe($classe->nom, 'Aucune matière active disponible.');
        }

        [$occupiedClass, $occupiedTeacher, $occupiedRoom] = $this->buildOccupationMapsSafe(
            $classe->etablissement_id,
            $annee->id
        );

        $plans = $this->buildSubjectPlansSafe($classe, $matieres, $enseignants, $affectations);

        if (empty($plans)) {
            return $this->errorResultSafe($classe->nom, 'Aucune matière exploitable n’a été trouvée pour cette classe.');
        }

        $propositions = [];
        $alertes = [];
        $nonPlaces = [];

        foreach ($plans as $plan) {
            if ($plan['is_eps']) {
                $this->placeEpsBlocks(
                    classe: $classe,
                    annee: $annee,
                    plan: $plan,
                    settings: $settings,
                    jours: $jours,
                    creneaux: $creneaux,
                    salles: $salles,
                    lockedSessions: $lockedSessions,
                    externalSlots: $externalSlots,
                    occupiedClass: $occupiedClass,
                    occupiedTeacher: $occupiedTeacher,
                    occupiedRoom: $occupiedRoom,
                    propositions: $propositions,
                    alertes: $alertes,
                    nonPlaces: $nonPlaces
                );

                continue;
            }

            $subjectDays = [];

            for ($i = 0; $i < $plan['weekly_slots']; $i++) {
                $bestCandidate = null;
                $bestScore = -INF;

                foreach ($jours as $jour) {
                    foreach ($creneaux as $creneau) {
                        $key = $this->slotKeySafe($jour, $creneau->id);

                        if (($occupiedClass[$classe->id][$key] ?? false) === true) {
                            continue;
                        }

                        if ($this->isLockedSlotForAnotherClassSafe($lockedSessions, $classe->id, $jour, $creneau->id)) {
                            continue;
                        }

                        $teacher = $this->pickTeacherForSlotSafe(
                            $plan['teacher_pool'],
                            $occupiedTeacher,
                            $externalSlots,
                            $settings,
                            $jour,
                            $creneau
                        );

                        if ($plan['teacher_required'] && !$teacher) {
                            continue;
                        }

                        $salle = $this->bestAvailableSalleSafe($salles, $occupiedRoom, $jour, $creneau->id);

                        if (!$salle) {
                            continue;
                        }

                        $score = $this->scoreSlotSafe(
                            settings: $settings,
                            jour: $jour,
                            creneau: $creneau,
                            matiere: $plan['matiere'],
                            classeId: $classe->id,
                            enseignant: $teacher,
                            occupiedClass: $occupiedClass,
                            occupiedTeacher: $occupiedTeacher,
                            subjectDays: $subjectDays
                        );

                        if ($bestCandidate === null || $score > $bestScore) {
                            $bestScore = $score;
                            $bestCandidate = [
                                'jour' => $jour,
                                'creneau' => $creneau,
                                'salle' => $salle,
                                'teacher' => $teacher,
                                'score' => $score,
                            ];
                        }
                    }
                }

                if (!$bestCandidate) {
                    $nonPlaces[] = [
                        'classe' => $classe->nom,
                        'matiere' => $plan['matiere']->nom,
                        'enseignant' => $plan['teacher_required'] ? 'Aucun créneau compatible' : 'Non affecté',
                        'session_index' => $i + 1,
                    ];

                    $alertes[] = "Impossible de placer {$plan['matiere']->nom} pour {$classe->nom} (séance " . ($i + 1) . ").";
                    continue;
                }

                $key = $this->slotKeySafe($bestCandidate['jour'], $bestCandidate['creneau']->id);
                $occupiedClass[$classe->id][$key] = true;

                if ($bestCandidate['teacher']) {
                    $occupiedTeacher[$bestCandidate['teacher']->id][$key] = true;
                }

                $occupiedRoom[$bestCandidate['salle']->id][$key] = true;
                $subjectDays[] = $bestCandidate['jour'];

                $propositions[] = $this->buildProposalSafe(
                    $classe,
                    $annee,
                    $plan['matiere'],
                    $bestCandidate['teacher'],
                    $bestCandidate['salle'],
                    $bestCandidate['creneau'],
                    $bestCandidate['jour'],
                    $bestCandidate['score'],
                    $settings,
                    null
                );
            }
        }

        return [
            'success' => true,
            'message' => null,
            'classe' => $classe->nom,
            'propositions' => $propositions,
            'alertes' => $alertes,
            'non_places' => $nonPlaces,
            'score' => $this->computeScoreSafe($propositions, $nonPlaces),
            'summary' => [
                'params_applied' => $this->summarizeSettingsSafe($settings),
                'hard_constraints' => [
                    'eps_block_2h' => true,
                    'class_unique_slot' => true,
                    'teacher_unique_slot' => true,
                    'exclusive_room_unique_slot' => true,
                    'allowed_days_slots_rooms' => true,
                    'external_vacataire_slots' => (bool) $settings['respecter_imports_vacataires'],
                ],
            ],
        ];
    }

    public function genererPourClasse(Classe $classe, AnneeScolaire $annee, array $params = []): array
    {
        $result = $this->suggererPourClasse($classe, $annee, $params);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $created = 0;
        $ignored = 0;
        $generationUuid = (string) Str::uuid();
        $guard = app(EmploiDuTempsConflictGuard::class);

        DB::transaction(function () use ($result, &$created, &$ignored, $generationUuid, $classe, $annee, $guard) {
            foreach ($result['propositions'] as $item) {
                if (EmploiDuTemps::query()
                    ->where('etablissement_id', $classe->etablissement_id)
                    ->where('annee_scolaire_id', $annee->id)
                    ->where('jour', $item['jour'])
                    ->where('creneau_id', $item['creneau_id'])
                    ->where('locked_by_user', true)
                    ->where('actif', true)
                    ->exists()) {
                    $ignored++;
                    continue;
                }

                unset($item['_meta']);
                $item['generation_uuid'] = $generationUuid;
                $item['etablissement_id'] = $classe->etablissement_id;
                $item['annee_scolaire_id'] = $annee->id;
                $item['actif'] = $item['actif'] ?? true;

                try {
                    $guard->createSafely($item);
                    $created++;
                } catch (ValidationException $e) {
                    $ignored++;
                }
            }
        });

        return [
            'success' => true,
            'message' => null,
            'generation_uuid' => $generationUuid,
            'created' => $created,
            'ignored' => $ignored,
            'alertes' => $result['alertes'],
            'non_places' => $result['non_places'],
            'score' => $result['score'],
            'summary' => $result['summary'] ?? [],
        ];
    }

    public function detecterConflits(int $etablissementId, ?int $anneeScolaireId = null): array
    {
        $query = EmploiDuTemps::query()
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->with(['classe', 'enseignant', 'salle', 'creneau', 'matiere']);

        if ($anneeScolaireId) {
            $query->where('annee_scolaire_id', $anneeScolaireId);
        }

        $items = $query->get();
        $conflicts = [];

        $this->pushSafeGroupedConflicts($conflicts, $items, fn ($i) => $i->classe_id . '|' . $i->jour . '|' . $i->creneau_id, 'classe');
        $this->pushSafeGroupedConflicts($conflicts, $items->filter(fn ($i) => !empty($i->enseignant_id)), fn ($i) => $i->enseignant_id . '|' . $i->jour . '|' . $i->creneau_id, 'enseignant');

        $colonneCapaciteExiste = Schema::hasColumn('salles', 'capacite_groupes');
        $roomItems = $items->filter(function ($i) use ($colonneCapaciteExiste) {
            if (empty($i->salle_id)) {
                return false;
            }

            if (! $colonneCapaciteExiste) {
                return true;
            }

            return optional($i->salle)->capacite_groupes <= 1;
        });

        $this->pushSafeGroupedConflicts($conflicts, $roomItems, fn ($i) => $i->salle_id . '|' . $i->jour . '|' . $i->creneau_id, 'salle');

        return $conflicts;
    }

    private function placeEpsBlocks(
        Classe $classe,
        AnneeScolaire $annee,
        array $plan,
        array $settings,
        array $jours,
        Collection $creneaux,
        Collection $salles,
        Collection $lockedSessions,
        Collection $externalSlots,
        array &$occupiedClass,
        array &$occupiedTeacher,
        array &$occupiedRoom,
        array &$propositions,
        array &$alertes,
        array &$nonPlaces
    ): void {
        $blocksToPlace = max(1, (int) ceil($plan['weekly_slots'] / 2));
        $usedDays = [];

        for ($blockIndex = 0; $blockIndex < $blocksToPlace; $blockIndex++) {
            $bestCandidate = null;
            $bestScore = -INF;

            foreach ($jours as $jour) {
                foreach ($this->findConsecutiveSlotPairs($creneaux) as $pair) {
                    [$first, $second] = $pair;

                    if (!$this->pairIsFreeForClass($occupiedClass, $classe->id, $jour, $first, $second)) {
                        continue;
                    }

                    if ($this->isLockedSlotForAnotherClassSafe($lockedSessions, $classe->id, $jour, $first->id)
                        || $this->isLockedSlotForAnotherClassSafe($lockedSessions, $classe->id, $jour, $second->id)) {
                        continue;
                    }

                    $teacher = $this->pickTeacherForConsecutiveSlotsSafe(
                        $plan['teacher_pool'],
                        $occupiedTeacher,
                        $externalSlots,
                        $settings,
                        $jour,
                        $first,
                        $second
                    );

                    if ($plan['teacher_required'] && !$teacher) {
                        continue;
                    }

                    $salle = $this->bestAvailableSalleForPairSafe($salles, $occupiedRoom, $jour, $first, $second);

                    if (!$salle) {
                        continue;
                    }

                    $score = $this->scoreEpsPairSafe($settings, $jour, $first, $second, $teacher, $usedDays);

                    if ($bestCandidate === null || $score > $bestScore) {
                        $bestScore = $score;
                        $bestCandidate = [
                            'jour' => $jour,
                            'first' => $first,
                            'second' => $second,
                            'teacher' => $teacher,
                            'salle' => $salle,
                            'score' => $score,
                        ];
                    }
                }
            }

            if (!$bestCandidate) {
                $nonPlaces[] = [
                    'classe' => $classe->nom,
                    'matiere' => $plan['matiere']->nom,
                    'enseignant' => 'Aucun bloc de 2 heures consécutives compatible',
                    'session_index' => $blockIndex + 1,
                    'constraint' => 'eps_block_2h_required',
                ];
                $alertes[] = "EPS non plaçable en bloc de 2 heures consécutives pour {$classe->nom}. Aucun placement disparate n’a été créé.";
                continue;
            }

            foreach ([$bestCandidate['first'], $bestCandidate['second']] as $slot) {
                $key = $this->slotKeySafe($bestCandidate['jour'], $slot->id);
                $occupiedClass[$classe->id][$key] = true;

                if ($bestCandidate['teacher']) {
                    $occupiedTeacher[$bestCandidate['teacher']->id][$key] = true;
                }

                $occupiedRoom[$bestCandidate['salle']->id][$key] = true;

                $propositions[] = $this->buildProposalSafe(
                    $classe,
                    $annee,
                    $plan['matiere'],
                    $bestCandidate['teacher'],
                    $bestCandidate['salle'],
                    $slot,
                    $bestCandidate['jour'],
                    $bestCandidate['score'],
                    $settings,
                    'eps_block_' . ($blockIndex + 1)
                );
            }

            $usedDays[] = $bestCandidate['jour'];
        }
    }

    private function resolveGenerationSettings(int $etablissementId, int $anneeId, array $input): array
    {
        $stored = [];

        if (Schema::hasTable('edt_parametres')) {
            $query = DB::table('edt_parametres');

            if (Schema::hasColumn('edt_parametres', 'etablissement_id')) {
                $query->where('etablissement_id', $etablissementId);
            }

            if (Schema::hasColumn('edt_parametres', 'annee_scolaire_id')) {
                $query->where(function ($q) use ($anneeId) {
                    $q->where('annee_scolaire_id', $anneeId)->orWhereNull('annee_scolaire_id');
                });
            }

            $stored = (array) $query->orderByDesc('id')->first();
        }

        $jsonArray = function (string $key, array $fallback = []) use ($stored, $input): array {
            if (isset($input[$key]) && is_array($input[$key])) {
                return array_values($input[$key]);
            }

            if (isset($stored[$key])) {
                $decoded = json_decode((string) $stored[$key], true);
                return is_array($decoded) ? array_values($decoded) : $fallback;
            }

            return $fallback;
        };

        $boolValue = function (string $key, bool $fallback = false) use ($stored, $input): bool {
            if (array_key_exists($key, $input)) {
                return filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
            }

            if (array_key_exists($key, $stored)) {
                return filter_var($stored[$key], FILTER_VALIDATE_BOOLEAN);
            }

            return $fallback;
        };

        $intValue = function (string $key, int $fallback = 0) use ($stored, $input): int {
            if (isset($input[$key]) && $input[$key] !== '') {
                return (int) $input[$key];
            }

            if (isset($stored[$key]) && $stored[$key] !== '') {
                return (int) $stored[$key];
            }

            return $fallback;
        };

        return [
            'jours' => $jsonArray('jours_autorises_json', $input['jours'] ?? EmploiDuTemps::jours()),
            'creneau_ids' => $jsonArray('creneaux_autorises_json', $input['creneau_ids'] ?? []),
            'salle_ids' => $jsonArray('salles_autorisees_json', $input['salle_ids'] ?? []),
            'strategie' => $input['strategie'] ?? ($stored['mode_generation_defaut'] ?? 'equilibree'),
            'policy_id' => $intValue('policy_id'),
            'attendre_horaires_vacataires' => $boolValue('attendre_horaires_vacataires', true),
            'bloquer_si_vacataire_sans_horaire' => $boolValue('bloquer_si_vacataire_sans_horaire', true),
            'respecter_imports_vacataires' => $boolValue('respecter_imports_vacataires', true),
            'prioriser_classes_examen' => $boolValue('prioriser_classes_examen', false),
            'prioriser_permanents' => $boolValue('prioriser_permanents', true),
            'autoriser_reduction_heures' => $boolValue('autoriser_reduction_heures', false),
            'max_reduction_minutes_par_classe' => $intValue('max_reduction_minutes_par_classe'),
            'max_reduction_minutes_par_matiere' => $intValue('max_reduction_minutes_par_matiere'),
            'respecter_tp_consecutifs' => $boolValue('respecter_tp_consecutifs', true),
            'eviter_eps_heures_chaudes' => $boolValue('eviter_eps_heures_chaudes', true),
            'limiter_niveaux_prof' => $boolValue('limiter_niveaux_prof', true),
            'max_niveaux_par_prof' => $intValue('max_niveaux_par_prof', 3),
            'limiter_heures_creuses' => $boolValue('limiter_heures_creuses', true),
            'max_heures_creuses_prof' => $intValue('max_heures_creuses_prof', 2),
            'autoriser_trous' => $boolValue('autoriser_trous', false),
            'tolerer_surcharge_legere' => $boolValue('tolerer_surcharge_legere', (bool) ($input['tolerer_surcharge'] ?? false)),
            'valide_du' => $input['valide_du'] ?? null,
            'valide_au' => $input['valide_au'] ?? null,
            'notes_generation' => $input['notes_generation'] ?? ($stored['notes_generation'] ?? null),
        ];
    }

    private function chargerCreneauxSafe(int $etablissementId, array $ids = []): Collection
    {
        $query = Creneau::query();

        if (Schema::hasColumn('creneaux', 'etablissement_id')) {
            $query->where('etablissement_id', $etablissementId);
        }

        if (Schema::hasColumn('creneaux', 'actif')) {
            $query->where('actif', true);
        }

        if (Schema::hasColumn('creneaux', 'type')) {
            $query->where('type', Creneau::TYPE_COURS);
        }

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        if (Schema::hasColumn('creneaux', 'ordre')) {
            $query->orderBy('ordre');
        } else {
            $query->orderBy('heure_debut')->orderBy('id');
        }

        return $query->get();
    }

    private function chargerSallesSafe(int $etablissementId, array $ids = []): Collection
    {
        $query = Salle::query()
            ->where('etablissement_id', $etablissementId)
            ->where('active', true)
            ->orderBy('nom');

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->get();
    }

    private function chargerMatieresSafe(int $etablissementId): Collection
    {
        return Matiere::query()
            ->where('etablissement_id', $etablissementId)
            ->where('active', true)
            ->orderBy('groupe')
            ->orderBy('nom')
            ->get();
    }

    private function chargerEnseignantsSafe(int $etablissementId): Collection
    {
        return Enseignant::query()
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    private function chargerAffectationsSafe(Classe $classe, AnneeScolaire $annee): Collection
    {
        return Affectation::query()
            ->where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('active', true)
            ->with(['matiere', 'enseignant'])
            ->get();
    }

    private function chargerLockedSessionsSafe(int $etablissementId, int $anneeScolaireId): Collection
    {
        return EmploiDuTemps::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('locked_by_user', true)
            ->where('actif', true)
            ->get(['id', 'classe_id', 'jour', 'creneau_id']);
    }

    private function chargerHorairesExternesSafe(int $etablissementId, int $anneeScolaireId, Collection $enseignants): Collection
    {
        if (!Schema::hasTable('edt_enseignant_horaires_externes')) {
            return collect();
        }

        return EnseignantHoraireExterne::query()
            ->whereIn('enseignant_id', $enseignants->pluck('id'))
            ->where('valide', true)
            ->where(function ($q) use ($anneeScolaireId) {
                $q->where('annee_scolaire_id', $anneeScolaireId)->orWhereNull('annee_scolaire_id');
            })
            ->get()
            ->groupBy('enseignant_id');
    }

    private function buildOccupationMapsSafe(int $etablissementId, int $anneeScolaireId): array
    {
        $existants = EmploiDuTemps::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('actif', true)
            ->get(['classe_id', 'enseignant_id', 'salle_id', 'jour', 'creneau_id']);

        $occupiedClass = [];
        $occupiedTeacher = [];
        $occupiedRoom = [];

        foreach ($existants as $edt) {
            $key = $this->slotKeySafe($edt->jour, $edt->creneau_id);
            $occupiedClass[$edt->classe_id][$key] = true;

            if (!empty($edt->enseignant_id)) {
                $occupiedTeacher[$edt->enseignant_id][$key] = true;
            }

            if (!empty($edt->salle_id)) {
                $occupiedRoom[$edt->salle_id][$key] = true;
            }
        }

        return [$occupiedClass, $occupiedTeacher, $occupiedRoom];
    }

    private function buildSubjectPlansSafe(Classe $classe, Collection $matieres, Collection $enseignants, Collection $affectations): array
    {
        $plans = [];
        $preferredLv2 = $matieres->firstWhere('code', 'ESP') ?? $matieres->firstWhere('code', 'ALL');

        if ($affectations->isNotEmpty()) {
            foreach ($affectations as $affectation) {
                if (!$affectation->matiere) {
                    continue;
                }

                $matiere = $affectation->matiere;
                if (!$this->matiereAutoriseePourClasseSafe($matiere, $classe, $preferredLv2?->id)) {
                    continue;
                }

                $slots = (int) round((float) ($affectation->volume_horaire_hebdo ?? 0));
                if ($slots <= 0) {
                    $slots = $this->weeklyTargetForSubjectSafe($matiere, $classe);
                }

                if ($slots <= 0) {
                    continue;
                }

                $teacherPool = $affectation->enseignant
                    ? collect([$affectation->enseignant])
                    : $this->teachersForSubjectSafe($matiere, $enseignants);

                $plans[] = [
                    'matiere' => $matiere,
                    'weekly_slots' => $this->isEpsSubject($matiere) ? max(2, $slots) : $slots,
                    'teacher_pool' => $teacherPool,
                    'teacher_required' => $teacherPool->isNotEmpty(),
                    'is_eps' => $this->isEpsSubject($matiere),
                ];
            }
        } else {
            foreach ($matieres as $matiere) {
                if (!$this->matiereAutoriseePourClasseSafe($matiere, $classe, $preferredLv2?->id)) {
                    continue;
                }

                $slots = $this->weeklyTargetForSubjectSafe($matiere, $classe);
                if ($slots <= 0) {
                    continue;
                }

                $teacherPool = $this->teachersForSubjectSafe($matiere, $enseignants);
                $plans[] = [
                    'matiere' => $matiere,
                    'weekly_slots' => $this->isEpsSubject($matiere) ? max(2, $slots) : $slots,
                    'teacher_pool' => $teacherPool,
                    'teacher_required' => false,
                    'is_eps' => $this->isEpsSubject($matiere),
                ];
            }
        }

        usort($plans, function ($a, $b) {
            if ($a['is_eps'] !== $b['is_eps']) {
                return $a['is_eps'] ? -1 : 1;
            }

            return $b['weekly_slots'] <=> $a['weekly_slots'];
        });

        return $plans;
    }

    private function isEpsSubject(Matiere $matiere): bool
    {
        $code = strtoupper((string) $matiere->code);
        $name = Str::lower(Str::ascii((string) $matiere->nom));

        return $code === 'EPS'
            || str_contains($name, 'eps')
            || str_contains($name, 'education physique')
            || str_contains($name, 'sport');
    }

    private function findConsecutiveSlotPairs(Collection $creneaux): array
    {
        $values = $creneaux->values();
        $pairs = [];

        for ($i = 0; $i < $values->count() - 1; $i++) {
            $current = $values[$i];
            $next = $values[$i + 1];

            if (isset($current->heure_fin, $next->heure_debut) && substr((string) $current->heure_fin, 0, 5) !== substr((string) $next->heure_debut, 0, 5)) {
                continue;
            }

            $pairs[] = [$current, $next];
        }

        return $pairs;
    }

    private function pairIsFreeForClass(array $occupiedClass, int $classeId, string $jour, Creneau $first, Creneau $second): bool
    {
        return !($occupiedClass[$classeId][$this->slotKeySafe($jour, $first->id)] ?? false)
            && !($occupiedClass[$classeId][$this->slotKeySafe($jour, $second->id)] ?? false);
    }

    private function pickTeacherForSlotSafe(Collection $teacherPool, array $occupiedTeacher, Collection $externalSlots, array $settings, string $jour, Creneau $creneau): ?Enseignant
    {
        if ($teacherPool->isEmpty()) {
            return null;
        }

        $key = $this->slotKeySafe($jour, $creneau->id);

        return $teacherPool
            ->filter(fn ($teacher) => !($occupiedTeacher[$teacher->id][$key] ?? false))
            ->filter(fn ($teacher) => $this->teacherIsAvailableSafe($teacher, $externalSlots, $settings, $jour, $creneau))
            ->sortBy(function ($teacher) use ($occupiedTeacher, $settings) {
                $penalty = count($occupiedTeacher[$teacher->id] ?? []);
                if (($settings['prioriser_permanents'] ?? true) && ($teacher->statut ?? null) === Enseignant::STATUT_VACATAIRE) {
                    $penalty += 100;
                }
                return $penalty;
            })
            ->first();
    }

    private function pickTeacherForConsecutiveSlotsSafe(Collection $teacherPool, array $occupiedTeacher, Collection $externalSlots, array $settings, string $jour, Creneau $first, Creneau $second): ?Enseignant
    {
        if ($teacherPool->isEmpty()) {
            return null;
        }

        $firstKey = $this->slotKeySafe($jour, $first->id);
        $secondKey = $this->slotKeySafe($jour, $second->id);

        return $teacherPool
            ->filter(fn ($teacher) => !($occupiedTeacher[$teacher->id][$firstKey] ?? false) && !($occupiedTeacher[$teacher->id][$secondKey] ?? false))
            ->filter(fn ($teacher) => $this->teacherIsAvailableSafe($teacher, $externalSlots, $settings, $jour, $first))
            ->filter(fn ($teacher) => $this->teacherIsAvailableSafe($teacher, $externalSlots, $settings, $jour, $second))
            ->sortBy(function ($teacher) use ($occupiedTeacher, $settings) {
                $penalty = count($occupiedTeacher[$teacher->id] ?? []);
                if (($settings['prioriser_permanents'] ?? true) && ($teacher->statut ?? null) === Enseignant::STATUT_VACATAIRE) {
                    $penalty += 100;
                }
                return $penalty;
            })
            ->first();
    }

    private function teacherIsAvailableSafe(Enseignant $teacher, Collection $externalSlots, array $settings, string $jour, Creneau $creneau): bool
    {
        if (($teacher->statut ?? null) !== Enseignant::STATUT_VACATAIRE) {
            return true;
        }

        $slots = $externalSlots->get($teacher->id, collect());

        if (($settings['bloquer_si_vacataire_sans_horaire'] ?? true) && $slots->isEmpty()) {
            return false;
        }

        if (!($settings['respecter_imports_vacataires'] ?? true)) {
            return true;
        }

        if ($slots->isEmpty()) {
            return !($settings['attendre_horaires_vacataires'] ?? true);
        }

        $daySlots = $slots->filter(fn ($slot) => Str::lower((string) $slot->jour) === Str::lower($jour));

        foreach ($daySlots as $slot) {
            if ($slot->overlaps((string) $creneau->heure_debut, (string) $creneau->heure_fin)) {
                return false;
            }
        }

        return true;
    }

    private function bestAvailableSalleSafe(Collection $salles, array $occupiedRoom, string $jour, int $creneauId): ?Salle
    {
        $key = $this->slotKeySafe($jour, $creneauId);

        return $salles
            ->filter(fn ($salle) => !($occupiedRoom[$salle->id][$key] ?? false))
            ->sortBy(fn ($salle) => count($occupiedRoom[$salle->id] ?? []))
            ->first();
    }

    private function bestAvailableSalleForPairSafe(Collection $salles, array $occupiedRoom, string $jour, Creneau $first, Creneau $second): ?Salle
    {
        $firstKey = $this->slotKeySafe($jour, $first->id);
        $secondKey = $this->slotKeySafe($jour, $second->id);

        return $salles
            ->filter(fn ($salle) => !($occupiedRoom[$salle->id][$firstKey] ?? false) && !($occupiedRoom[$salle->id][$secondKey] ?? false))
            ->sortBy(fn ($salle) => count($occupiedRoom[$salle->id] ?? []))
            ->first();
    }

    private function teachersForSubjectSafe(Matiere $matiere, Collection $enseignants): Collection
    {
        $aliases = $this->subjectAliasesSafe($matiere);

        return $enseignants->filter(function ($ens) use ($aliases) {
            $specialite = Str::lower(Str::ascii((string) ($ens->specialite ?? '')));
            foreach ($aliases as $alias) {
                if (str_contains($specialite, $alias)) {
                    return true;
                }
            }
            return false;
        })->values();
    }

    private function matiereAutoriseePourClasseSafe(Matiere $matiere, Classe $classe, ?int $preferredLv2Id): bool
    {
        $code = strtoupper((string) $matiere->code);
        $nomClasse = strtoupper(Str::ascii((string) $classe->nom));

        $is6e = str_contains($nomClasse, '6E');
        $is5e = str_contains($nomClasse, '5E');
        $isTerminale = str_contains($nomClasse, 'TLE') || str_contains($nomClasse, 'TERM');

        if ($code === 'PHILO' && !$isTerminale) {
            return false;
        }

        if (in_array($code, ['ESP', 'ALL'], true)) {
            if ($is6e || $is5e) {
                return false;
            }
            return $preferredLv2Id === null || $matiere->id === $preferredLv2Id;
        }

        return true;
    }

    private function weeklyTargetForSubjectSafe(Matiere $matiere, Classe $classe): int
    {
        $nomClasse = strtoupper(Str::ascii((string) $classe->nom));
        $isSecondCycle = str_contains($nomClasse, '2NDE') || str_contains($nomClasse, '1ERE') || str_contains($nomClasse, 'TLE') || str_contains($nomClasse, 'TERM');

        $cycleValue = $isSecondCycle
            ? ($matiere->heures_hebdo_second_cycle ?? null)
            : ($matiere->heures_hebdo_premier_cycle ?? null);

        if ($cycleValue !== null && (float) $cycleValue > 0) {
            return (int) round((float) $cycleValue);
        }

        if (($matiere->heures_hebdo_defaut ?? null) !== null && (float) $matiere->heures_hebdo_defaut > 0) {
            return (int) round((float) $matiere->heures_hebdo_defaut);
        }

        return match (strtoupper((string) $matiere->code)) {
            'FRAN'  => 5,
            'MATH'  => 5,
            'ANG'   => 3,
            'HG'    => 3,
            'SVT'   => 3,
            'PC'    => 3,
            'EDHC'  => 2,
            'EPS'   => 2,
            'ESP',
            'ALL'   => 2,
            'PHILO' => 3,
            'AP',
            'EM'    => 1,
            default => max(1, min(3, (int) ($matiere->coefficient_defaut ?? 1))),
        };
    }

    private function subjectAliasesSafe(Matiere $matiere): array
    {
        $code = strtoupper((string) $matiere->code);

        return match ($code) {
            'FRAN'  => ['francais'],
            'MATH'  => ['mathematique', 'math'],
            'ANG'   => ['anglais'],
            'ESP'   => ['espagnol'],
            'ALL'   => ['allemand'],
            'HG'    => ['histoire', 'geographie'],
            'EDHC'  => ['edhc', 'citoyennete', 'droit'],
            'SVT'   => ['svt', 'science de la vie'],
            'PC'    => ['physique', 'chimie'],
            'EPS'   => ['eps', 'sport', 'education physique'],
            'PHILO' => ['philosophie'],
            'AP'    => ['arts plastiques', 'art plastique'],
            'EM'    => ['education musicale', 'musique'],
            default => [Str::lower(Str::ascii((string) $matiere->nom))],
        };
    }

    private function isLockedSlotForAnotherClassSafe(Collection $lockedSessions, int $classeId, string $jour, int $creneauId): bool
    {
        return $lockedSessions->contains(function ($item) use ($classeId, $jour, $creneauId) {
            return (int) $item->classe_id !== $classeId
                && $item->jour === $jour
                && (int) $item->creneau_id === $creneauId;
        });
    }

    private function scoreSlotSafe(array $settings, string $jour, Creneau $creneau, Matiere $matiere, int $classeId, ?Enseignant $enseignant, array $occupiedClass, array $occupiedTeacher, array $subjectDays): int
    {
        $score = 100;
        $strategie = $settings['strategie'] ?? 'equilibree';

        $classLoad = count(array_filter(array_keys($occupiedClass[$classeId] ?? []), fn ($k) => str_starts_with($k, $jour . '|')));
        $teacherLoad = $enseignant ? count(array_filter(array_keys($occupiedTeacher[$enseignant->id] ?? []), fn ($k) => str_starts_with($k, $jour . '|'))) : 0;

        $score -= ($classLoad * 8);
        $score -= ($teacherLoad * 6);

        if (in_array($jour, $subjectDays, true)) {
            $score -= 20;
        }

        if ($strategie === 'compacte') {
            $score += 10;
        }

        if ($strategie === 'matieres_principales' && isset($creneau->ordre)) {
            $score += max(0, 10 - (int) $creneau->ordre);
        }

        if ($strategie === 'disponibilite_enseignants') {
            $score -= ($teacherLoad * 5);
        }

        if (($settings['prioriser_permanents'] ?? true) && $enseignant && $enseignant->statut !== Enseignant::STATUT_VACATAIRE) {
            $score += 12;
        }

        return $score;
    }

    private function scoreEpsPairSafe(array $settings, string $jour, Creneau $first, Creneau $second, ?Enseignant $enseignant, array $usedDays): int
    {
        $score = 150;

        if (in_array($jour, $usedDays, true)) {
            $score -= 30;
        }

        if (($settings['eviter_eps_heures_chaudes'] ?? true)) {
            $firstStart = substr((string) ($first->heure_debut ?? '00:00'), 0, 5);
            if ($firstStart >= '11:00' && $firstStart <= '15:30') {
                $score -= 25;
            }
        }

        if (($settings['prioriser_permanents'] ?? true) && $enseignant && $enseignant->statut !== Enseignant::STATUT_VACATAIRE) {
            $score += 12;
        }

        return $score;
    }

    private function buildProposalSafe(Classe $classe, AnneeScolaire $annee, Matiere $matiere, ?Enseignant $teacher, Salle $salle, Creneau $creneau, string $jour, int $score, array $settings, ?string $blockKey): array
    {
        return [
            'etablissement_id' => $classe->etablissement_id,
            'annee_scolaire_id' => $annee->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $teacher?->id,
            'salle_id' => $salle->id,
            'creneau_id' => $creneau->id,
            'jour' => $jour,
            'valide_du' => $settings['valide_du'] ?? $annee->date_debut,
            'valide_au' => $settings['valide_au'] ?? $annee->date_fin,
            'actif' => true,
            'source' => 'ia',
            'ia_score' => round($score, 2),
            '_meta' => [
                'classe' => $classe->nom,
                'matiere' => $matiere->nom,
                'enseignant' => $teacher?->nom_complet ?? 'Non affecté',
                'salle' => $salle->nom,
                'creneau' => $creneau->libelle ?? $creneau->id,
                'block_key' => $blockKey,
            ],
        ];
    }

    private function slotKeySafe(string $jour, int $creneauId): string
    {
        return $jour . '|' . $creneauId;
    }

    private function computeScoreSafe(array $propositions, array $nonPlaces): int
    {
        $placed = count($propositions);
        $unplaced = count($nonPlaces);

        if (($placed + $unplaced) === 0) {
            return 0;
        }

        return (int) round(($placed / ($placed + $unplaced)) * 100);
    }

    private function summarizeSettingsSafe(array $settings): array
    {
        return [
            'jours_count' => count($settings['jours'] ?? []),
            'creneaux_count' => count($settings['creneau_ids'] ?? []),
            'salles_count' => count($settings['salle_ids'] ?? []),
            'strategie' => $settings['strategie'] ?? 'equilibree',
            'eps_block_2h' => true,
            'respecter_imports_vacataires' => (bool) ($settings['respecter_imports_vacataires'] ?? true),
            'prioriser_permanents' => (bool) ($settings['prioriser_permanents'] ?? true),
        ];
    }

    private function errorResultSafe(string $classe, string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'classe' => $classe,
            'propositions' => [],
            'alertes' => [$message],
            'non_places' => [],
            'score' => 0,
        ];
    }

    private function pushSafeGroupedConflicts(array &$conflicts, $items, callable $keyResolver, string $type): void
    {
        $items->groupBy($keyResolver)->each(function ($groupItems, $groupKey) use (&$conflicts, $type) {
            if ($groupItems->count() <= 1) {
                return;
            }

            $first = $groupItems->first();
            $conflicts[] = [
                'type' => $type,
                'key' => $groupKey,
                'label' => $this->buildConflictLabel($type, $first),
                'items' => $groupItems->values(),
            ];
        });
    }

    private function buildConflictLabel(string $type, EmploiDuTemps $item): string
    {
        $jour = ucfirst((string) $item->jour);
        $creneau = optional($item->creneau)->libelle ?? '—';

        return match ($type) {
            'classe' => 'Classe : ' . (optional($item->classe)->nom ?? '—') . ' / ' . $jour . ' / ' . $creneau,
            'enseignant' => 'Enseignant : ' . (optional($item->enseignant)->nom_complet ?? '—') . ' / ' . $jour . ' / ' . $creneau,
            'salle' => 'Salle : ' . (optional($item->salle)->nom ?? '—') . ' / ' . $jour . ' / ' . $creneau,
            default => $jour . ' / ' . $creneau,
        };
    }
}
