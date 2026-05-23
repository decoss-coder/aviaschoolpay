<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EmploiDuTemps;
use App\Models\EmploiDuTempsLearningRule;
use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\Salle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmploiDuTempsIAService
{
    public function suggererPourClasse(Classe $classe, AnneeScolaire $annee, array $params = []): array
    {
        $jours = collect($params['jours'] ?? EmploiDuTemps::jours())->filter()->values()->all();
        $strategie = $params['strategie'] ?? 'equilibree';

        $creneaux = $this->chargerCreneaux($classe->etablissement_id, $params['creneau_ids'] ?? []);
        $salles = $this->chargerSalles($classe->etablissement_id, $params['salle_ids'] ?? []);
        $matieres = $this->chargerMatieres($classe->etablissement_id);
        $enseignants = $this->chargerEnseignants($classe->etablissement_id);
        $rules = $this->chargerRules($classe->etablissement_id, $annee->id);
        $lockedSessions = $this->chargerLockedSessions($classe->etablissement_id, $annee->id);

        if (empty($jours)) {
            return $this->errorResult($classe->nom, 'Aucun jour autorisé.');
        }

        if ($creneaux->isEmpty()) {
            return $this->errorResult($classe->nom, 'Aucun créneau de cours actif disponible.');
        }

        if ($salles->isEmpty()) {
            return $this->errorResult($classe->nom, 'Aucune salle active disponible.');
        }

        if ($matieres->isEmpty()) {
            return $this->errorResult($classe->nom, 'Aucune matière active disponible.');
        }

        [$occupiedClass, $occupiedTeacher, $occupiedRoom] = $this->buildOccupationMaps(
            $classe->etablissement_id,
            $annee->id
        );

        $plans = $this->buildSubjectPlans($classe, $matieres, $enseignants);

        if (empty($plans)) {
            return $this->errorResult($classe->nom, 'Aucune matière exploitable n’a été trouvée pour cette classe.');
        }

        $propositions = [];
        $alertes = [];
        $nonPlaces = [];

        foreach ($plans as $plan) {
            $subjectDays = [];

            for ($i = 0; $i < $plan['weekly_slots']; $i++) {
                $bestCandidate = null;
                $bestScore = -INF;

                foreach ($jours as $jour) {
                    foreach ($creneaux as $creneau) {
                        $key = $this->slotKey($jour, $creneau->id);

                        if (($occupiedClass[$classe->id][$key] ?? false) === true) {
                            continue;
                        }

                        if ($this->isLockedSlotForAnotherClass($lockedSessions, $classe->id, $jour, $creneau->id)) {
                            continue;
                        }

                        $teacher = $this->pickTeacherForSlot(
                            $plan['teacher_pool'],
                            $occupiedTeacher,
                            $jour,
                            $creneau->id
                        );

                        if ($plan['teacher_required'] && !$teacher) {
                            continue;
                        }

                        $salle = $this->bestAvailableSalle($salles, $occupiedRoom, $jour, $creneau->id);

                        if (!$salle) {
                            continue;
                        }

                        $score = $this->scoreSlot(
                            strategie: $strategie,
                            jour: $jour,
                            creneau: $creneau,
                            classeId: $classe->id,
                            enseignantId: $teacher?->id,
                            occupiedClass: $occupiedClass,
                            occupiedTeacher: $occupiedTeacher,
                            subjectDays: $subjectDays
                        );

                        $score = $this->applyLearningScore(
                            $score,
                            $rules,
                            $classe->id,
                            $plan['matiere']->id,
                            $teacher?->id,
                            $salle->id,
                            $jour,
                            $creneau->id
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

                $key = $this->slotKey($bestCandidate['jour'], $bestCandidate['creneau']->id);

                $occupiedClass[$classe->id][$key] = true;

                if ($bestCandidate['teacher']) {
                    $occupiedTeacher[$bestCandidate['teacher']->id][$key] = true;
                }

                $occupiedRoom[$bestCandidate['salle']->id][$key] = true;
                $subjectDays[] = $bestCandidate['jour'];

                $propositions[] = [
                    'etablissement_id' => $classe->etablissement_id,
                    'annee_scolaire_id' => $annee->id,
                    'classe_id' => $classe->id,
                    'matiere_id' => $plan['matiere']->id,
                    'enseignant_id' => $bestCandidate['teacher']?->id,
                    'salle_id' => $bestCandidate['salle']->id,
                    'creneau_id' => $bestCandidate['creneau']->id,
                    'jour' => $bestCandidate['jour'],
                    'valide_du' => $params['valide_du'] ?? $annee->date_debut,
                    'valide_au' => $params['valide_au'] ?? $annee->date_fin,
                    'actif' => true,
                    'source' => 'ia',
                    'ia_score' => round($bestCandidate['score'], 2),
                    '_meta' => [
                        'classe' => $classe->nom,
                        'matiere' => $plan['matiere']->nom,
                        'enseignant' => $bestCandidate['teacher']?->nom_complet ?? 'Non affecté',
                        'salle' => $bestCandidate['salle']->nom,
                        'creneau' => $bestCandidate['creneau']->libelle ?? $bestCandidate['creneau']->id,
                    ],
                ];
            }
        }

        return [
            'success' => true,
            'message' => null,
            'classe' => $classe->nom,
            'propositions' => $propositions,
            'alertes' => $alertes,
            'non_places' => $nonPlaces,
            'score' => $this->computeScore($propositions, $nonPlaces),
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

        DB::transaction(function () use ($result, &$created, &$ignored, $generationUuid, $classe, $annee) {
            foreach ($result['propositions'] as $item) {
                $base = EmploiDuTemps::query()
                    ->where('annee_scolaire_id', $item['annee_scolaire_id'])
                    ->where('jour', $item['jour'])
                    ->where('creneau_id', $item['creneau_id']);

                if ((clone $base)->where('classe_id', $item['classe_id'])->exists()) {
                    $ignored++;
                    continue;
                }

                if (!empty($item['enseignant_id']) && (clone $base)->where('enseignant_id', $item['enseignant_id'])->exists()) {
                    $ignored++;
                    continue;
                }

                if ((clone $base)->where('salle_id', $item['salle_id'])->exists()) {
                    $ignored++;
                    continue;
                }

                if (EmploiDuTemps::query()
                    ->where('etablissement_id', $classe->etablissement_id)
                    ->where('annee_scolaire_id', $annee->id)
                    ->where('jour', $item['jour'])
                    ->where('creneau_id', $item['creneau_id'])
                    ->where('locked_by_user', true)
                    ->exists()) {
                    $ignored++;
                    continue;
                }

                unset($item['_meta']);
                $item['generation_uuid'] = $generationUuid;

                EmploiDuTemps::create($item);
                $created++;
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
        ];
    }

    public function genererGlobal(int $etablissementId, int $anneeScolaireId, array $params = []): array
    {
        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etablissementId)
            ->findOrFail($anneeScolaireId);

        $classes = Classe::query()
            ->where('etablissement_id', $etablissementId)
            ->where('active', true)
            ->orderBy('nom')
            ->get();

        $summary = [
            'success' => true,
            'classes_traitees' => 0,
            'created' => 0,
            'ignored' => 0,
            'alertes' => [],
            'non_places' => [],
        ];

        foreach ($classes as $classe) {
            $res = $this->genererPourClasse($classe, $annee, $params);

            $summary['classes_traitees']++;
            $summary['created'] += $res['created'] ?? 0;
            $summary['ignored'] += $res['ignored'] ?? 0;
            $summary['alertes'] = array_merge($summary['alertes'], $res['alertes'] ?? []);
            $summary['non_places'] = array_merge($summary['non_places'], $res['non_places'] ?? []);
        }

        return $summary;
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

        $this->pushGroupedConflicts($conflicts, $items, fn ($i) => $i->classe_id . '|' . $i->jour . '|' . $i->creneau_id, 'classe');
        $this->pushGroupedConflicts($conflicts, $items->filter(fn ($i) => !empty($i->enseignant_id)), fn ($i) => $i->enseignant_id . '|' . $i->jour . '|' . $i->creneau_id, 'enseignant');
        $this->pushGroupedConflicts($conflicts, $items, fn ($i) => $i->salle_id . '|' . $i->jour . '|' . $i->creneau_id, 'salle');

        return $conflicts;
    }

    private function chargerRules(int $etablissementId, int $anneeScolaireId): Collection
    {
        return EmploiDuTempsLearningRule::query()
            ->where('etablissement_id', $etablissementId)
            ->where('active', true)
            ->where(function ($q) use ($anneeScolaireId) {
                $q->whereNull('annee_scolaire_id')->orWhere('annee_scolaire_id', $anneeScolaireId);
            })
            ->get();
    }

    private function chargerLockedSessions(int $etablissementId, int $anneeScolaireId): Collection
    {
        return EmploiDuTemps::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('locked_by_user', true)
            ->where('actif', true)
            ->get(['id', 'classe_id', 'jour', 'creneau_id']);
    }

    private function isLockedSlotForAnotherClass(Collection $lockedSessions, int $classeId, string $jour, int $creneauId): bool
    {
        return $lockedSessions->contains(function ($item) use ($classeId, $jour, $creneauId) {
            return (int) $item->classe_id !== $classeId
                && $item->jour === $jour
                && (int) $item->creneau_id === $creneauId;
        });
    }

    private function chargerCreneaux(int $etablissementId, array $ids = []): Collection
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
            $query->orderBy('id');
        }

        return $query->get();
    }

    private function chargerSalles(int $etablissementId, array $ids = []): Collection
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

    private function chargerMatieres(int $etablissementId): Collection
    {
        return Matiere::query()
            ->where('etablissement_id', $etablissementId)
            ->where('active', true)
            ->orderBy('groupe')
            ->orderBy('nom')
            ->get();
    }

    private function chargerEnseignants(int $etablissementId): Collection
    {
        return Enseignant::query()
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    private function buildOccupationMaps(int $etablissementId, int $anneeScolaireId): array
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
            $key = $this->slotKey($edt->jour, $edt->creneau_id);
            $occupiedClass[$edt->classe_id][$key] = true;

            if (!empty($edt->enseignant_id)) {
                $occupiedTeacher[$edt->enseignant_id][$key] = true;
            }

            $occupiedRoom[$edt->salle_id][$key] = true;
        }

        return [$occupiedClass, $occupiedTeacher, $occupiedRoom];
    }

    private function buildSubjectPlans(Classe $classe, Collection $matieres, Collection $enseignants): array
    {
        $plans = [];
        $preferredLv2 = $matieres->firstWhere('code', 'ESP') ?? $matieres->firstWhere('code', 'ALL');

        foreach ($matieres as $matiere) {
            if (!$this->matiereAutoriseePourClasse($matiere, $classe, $preferredLv2?->id)) {
                continue;
            }

            $slots = $this->weeklyTargetForSubject($matiere, $classe);

            if ($slots <= 0) {
                continue;
            }

            $teacherPool = $this->teachersForSubject($matiere, $enseignants);

            $plans[] = [
                'matiere' => $matiere,
                'weekly_slots' => $slots,
                'teacher_pool' => $teacherPool,
                'teacher_required' => false,
            ];
        }

        usort($plans, fn ($a, $b) => $b['weekly_slots'] <=> $a['weekly_slots']);

        return $plans;
    }

    private function teachersForSubject(Matiere $matiere, Collection $enseignants): Collection
    {
        $aliases = $this->subjectAliases($matiere);

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

    private function pickTeacherForSlot(Collection $teacherPool, array $occupiedTeacher, string $jour, int $creneauId): ?Enseignant
    {
        if ($teacherPool->isEmpty()) {
            return null;
        }

        $key = $this->slotKey($jour, $creneauId);

        return $teacherPool
            ->filter(fn ($teacher) => !($occupiedTeacher[$teacher->id][$key] ?? false))
            ->sortBy(fn ($teacher) => count($occupiedTeacher[$teacher->id] ?? []))
            ->first();
    }

    private function bestAvailableSalle(Collection $salles, array $occupiedRoom, string $jour, int $creneauId): ?Salle
    {
        $key = $this->slotKey($jour, $creneauId);

        return $salles
            ->filter(fn ($salle) => !($occupiedRoom[$salle->id][$key] ?? false))
            ->sortBy(fn ($salle) => count($occupiedRoom[$salle->id] ?? []))
            ->first();
    }

    private function matiereAutoriseePourClasse(Matiere $matiere, Classe $classe, ?int $preferredLv2Id): bool
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

    private function weeklyTargetForSubject(Matiere $matiere, Classe $classe): int
    {
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

    private function subjectAliases(Matiere $matiere): array
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
            'EPS'   => ['eps', 'sport'],
            'PHILO' => ['philosophie'],
            'AP'    => ['arts plastiques', 'art plastique'],
            'EM'    => ['education musicale', 'musique'],
            default => [Str::lower(Str::ascii((string) $matiere->nom))],
        };
    }

    private function slotKey(string $jour, int $creneauId): string
    {
        return $jour . '|' . $creneauId;
    }

    private function scoreSlot(
        string $strategie,
        string $jour,
        $creneau,
        int $classeId,
        ?int $enseignantId,
        array $occupiedClass,
        array $occupiedTeacher,
        array $subjectDays
    ): int {
        $score = 100;

        $classLoad = count(array_filter(
            array_keys($occupiedClass[$classeId] ?? []),
            fn ($k) => str_starts_with($k, $jour . '|')
        ));

        $teacherLoad = 0;
        if ($enseignantId) {
            $teacherLoad = count(array_filter(
                array_keys($occupiedTeacher[$enseignantId] ?? []),
                fn ($k) => str_starts_with($k, $jour . '|')
            ));
        }

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

        return $score;
    }

    private function applyLearningScore(
        int $baseScore,
        Collection $rules,
        int $classeId,
        int $matiereId,
        ?int $enseignantId,
        ?int $salleId,
        string $jour,
        int $creneauId
    ): int {
        foreach ($rules as $rule) {
            if ($rule->classe_id && (int) $rule->classe_id !== $classeId) continue;
            if ($rule->matiere_id && (int) $rule->matiere_id !== $matiereId) continue;
            if ($rule->enseignant_id && (int) $rule->enseignant_id !== (int) $enseignantId) continue;
            if ($rule->salle_id && (int) $rule->salle_id !== (int) $salleId) continue;
            if ($rule->jour && $rule->jour !== $jour) continue;
            if ($rule->creneau_id && (int) $rule->creneau_id !== $creneauId) continue;

            if (in_array($rule->rule_type, ['prefer_slot', 'prefer_teacher', 'prefer_room', 'fixed_slot'], true)) {
                $baseScore += (int) round((float) $rule->weight * 10);
            }

            if ($rule->rule_type === 'avoid_slot') {
                $baseScore -= (int) round((float) $rule->weight * 10);
            }
        }

        return $baseScore;
    }

    private function computeScore(array $propositions, array $nonPlaces): int
    {
        $placed = count($propositions);
        $unplaced = count($nonPlaces);

        if (($placed + $unplaced) === 0) {
            return 0;
        }

        return max(0, (int) round(($placed / ($placed + $unplaced)) * 100));
    }

    private function pushGroupedConflicts(array &$conflicts, Collection $items, callable $groupBy, string $type): void
    {
        $groups = $items->groupBy($groupBy);

        foreach ($groups as $groupKey => $groupItems) {
            if ($groupItems->count() <= 1) {
                continue;
            }

            $conflicts[] = [
                'type' => $type,
                'key' => $groupKey,
                'items' => $groupItems->values(),
            ];
        }
    }

    private function errorResult(string $classeNom, string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'classe' => $classeNom,
            'propositions' => [],
            'alertes' => [$message],
            'non_places' => [],
            'score' => 0,
        ];
    }
}