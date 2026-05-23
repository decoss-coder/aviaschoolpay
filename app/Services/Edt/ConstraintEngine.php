<?php

namespace App\Services\Edt;

use App\Models\EdtGenerationScenario;
use Illuminate\Support\Collection;

class ConstraintEngine
{
    // ── Codes contraintes durs ────────────────────────────────────────
    private const HARD_NO_CLASS_COLLISION            = 'HARD_NO_CLASS_COLLISION';
    private const HARD_NO_TEACHER_COLLISION          = 'HARD_NO_TEACHER_COLLISION';
    private const HARD_NO_ROOM_COLLISION             = 'HARD_NO_ROOM_COLLISION';
    private const HARD_RESPECT_VACATAIRE_IMPORT      = 'HARD_RESPECT_VACATAIRE_IMPORT';
    private const HARD_NO_TEACHER_EXTERNAL_COLLISION = 'HARD_NO_TEACHER_EXTERNAL_COLLISION';
    private const HARD_RESPECT_CLASSE_PLAGE_HORAIRE  = 'HARD_RESPECT_CLASSE_PLAGE_HORAIRE';

    // ── Codes contraintes souples ─────────────────────────────────────
    private const SOFT_EPS_HEURES_CHAUDES           = 'SOFT_EPS_HEURES_CHAUDES';
    private const PRIVATE_GROUP_VACATAIRE_DAYS      = 'PRIVATE_GROUP_VACATAIRE_DAYS';
    private const SOFT_CONSECUTIVE_DISCIPLINE       = 'SOFT_CONSECUTIVE_DISCIPLINE';
    private const SOFT_TP_CONSECUTIVE_SAME_DAY      = 'SOFT_TP_CONSECUTIVE_SAME_DAY';
    private const SOFT_EQUITABLE_REPARTITION_SEMAINE = 'SOFT_EQUITABLE_REPARTITION_SEMAINE';
    private const SOFT_NO_ISOLATED_HOUR             = 'SOFT_NO_ISOLATED_HOUR';
    private const SOFT_MAX_3_NIVEAUX_PAR_PROF       = 'SOFT_MAX_3_NIVEAUX_PAR_PROF';

    public function resolveScenarioConstraints(EdtGenerationScenario $scenario): Collection
    {
        return $scenario->constraints()->with('constraint')->get()->keyBy('constraint.code');
    }

    public function allHardSatisfied(array $candidate, array $unit, array $state, Collection $constraints): bool
    {
        // ── Collision classe ──────────────────────────────────────────
        if ($this->enabled($constraints, self::HARD_NO_CLASS_COLLISION)) {
            if ($state['classes'][$candidate['jour']][$candidate['creneau_id']][$unit['classe_id']] ?? false) {
                return false;
            }
        }

        // ── Collision prof (même école) ───────────────────────────────
        if ($this->enabled($constraints, self::HARD_NO_TEACHER_COLLISION) && !empty($candidate['enseignant_id'])) {
            if ($state['enseignants'][$candidate['jour']][$candidate['creneau_id']][$candidate['enseignant_id']] ?? false) {
                return false;
            }
        }

        // ── Collision salle ───────────────────────────────────────────
        if ($this->enabled($constraints, self::HARD_NO_ROOM_COLLISION) && !empty($candidate['salle_id'])) {
            if ($state['salles'][$candidate['jour']][$candidate['creneau_id']][$candidate['salle_id']] ?? false) {
                return false;
            }
        }

        // ── Disponibilité vacataire ───────────────────────────────────
        if ($this->enabled($constraints, self::HARD_RESPECT_VACATAIRE_IMPORT) && !empty($candidate['vacataire_forbidden'])) {
            return false;
        }

        // ── Collision prof dans une AUTRE école ───────────────────────
        if ($this->enabled($constraints, self::HARD_NO_TEACHER_EXTERNAL_COLLISION) && !empty($candidate['external_busy'])) {
            return false;
        }

        // ── Plage horaire classe (matin / après-midi) ─────────────────
        // Vérifie que le créneau est dans la plage autorisée pour la classe
        if ($this->enabled($constraints, self::HARD_RESPECT_CLASSE_PLAGE_HORAIRE)) {
            $classePlageMap = $state['classe_plage_map'] ?? [];
            if (!empty($classePlageMap)) {
                $plage = $candidate['creneau_plage'] ?? null;
                if ($plage) {
                    $allowed = \App\Models\EdtClassePlageHoraire::isAllowed(
                        $classePlageMap,
                        $unit['classe_id'],
                        $candidate['jour'],
                        $plage
                    );
                    if (!$allowed) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function score(array $candidate, array $unit, array $state, Collection $constraints): float
    {
        $score = 0.0;

        // ── Préférence vacataire ──────────────────────────────────────
        if ($this->enabled($constraints, self::PRIVATE_GROUP_VACATAIRE_DAYS) && !empty($candidate['vacataire_preferred'])) {
            $score += 180;
        }

        // ── EPS hors heures chaudes ───────────────────────────────────
        // Heures chaudes = après 10h le matin ET avant 16h l'après-midi
        // ordre >= 4 = heures chaudes selon guide ACE (variable selon établissement)
        if ($this->enabled($constraints, self::SOFT_EPS_HEURES_CHAUDES) && ($unit['matiere_code'] ?? null) === 'EPS') {
            $ordre = $candidate['creneau_ordre'] ?? 0;
            // Pénalise si placé en heure chaude (créneaux de milieu de journée)
            if (in_array($ordre, [4, 5, 6, 7], true)) {
                $score -= 120;
            }
        }

        // ── Pénalité heures creuses prof ──────────────────────────────
        if (!empty($candidate['teacher_gap_penalty'])) {
            $score -= $candidate['teacher_gap_penalty'];
        }

        // ── Priorité politique ────────────────────────────────────────
        if (!empty($candidate['policy_priority'])) {
            $score += (10 - (int) $candidate['policy_priority']) * 10;
        }

        // ── 2h consécutives Maths / Français (1er cycle) ─────────────
        // +80 si on place la 2ème heure juste après une heure déjà placée
        // de la même matière pour cette classe
        if ($this->enabled($constraints, self::SOFT_CONSECUTIVE_DISCIPLINE)) {
            $score += $this->scoreConsecutiveDiscipline($candidate, $unit, $state);
        }

        // ── Tandem TP PC/SVT consécutifs le même jour ────────────────
        if ($this->enabled($constraints, self::SOFT_TP_CONSECUTIVE_SAME_DAY)) {
            $score += $this->scoreTpConsecutif($candidate, $unit, $state);
        }

        // ── Répartition équitable sur la semaine ──────────────────────
        if ($this->enabled($constraints, self::SOFT_EQUITABLE_REPARTITION_SEMAINE)) {
            $score += $this->scoreEquitableRepartition($candidate, $state);
        }

        // ── Pas d'heure isolée pour le prof ───────────────────────────
        if ($this->enabled($constraints, self::SOFT_NO_ISOLATED_HOUR)) {
            $score += $this->scoreNoIsolatedHour($candidate, $state);
        }

        // ── Max 3 niveaux par prof ────────────────────────────────────
        if ($this->enabled($constraints, self::SOFT_MAX_3_NIVEAUX_PAR_PROF)) {
            $score += $this->scoreMaxNiveaux($candidate, $unit, $state);
        }

        return $score;
    }

    // ─────────────────────────────────────────────────────────────────
    // Méthodes de scoring souples
    // ─────────────────────────────────────────────────────────────────

    /**
     * +80 si on pose la 2ème heure d'une matière en créneau consécutif
     * à une 1ère heure déjà placée. Concerne Maths et Français en 1er cycle.
     * Conforme guide ACE : 2h consécutives pour ces disciplines.
     */
    private function scoreConsecutiveDiscipline(array $candidate, array $unit, array $state): float
    {
        $matiereCode = $unit['matiere_code'] ?? null;

        // N'applique que pour Maths et Français
        if (!in_array($matiereCode, ['MATHS', 'FRANC', 'FRANCAIS', 'MATH'], true)) {
            return 0;
        }

        $ordreCandidat = $candidate['creneau_ordre'] ?? null;
        if (!$ordreCandidat) {
            return 0;
        }

        // Cherche si la même matière+classe est déjà placée au créneau précédent ou suivant
        foreach ($state['assignments'] as $placed) {
            if (
                $placed['classe_id'] === $unit['classe_id']
                && $placed['matiere_id'] === $unit['matiere_id']
                && $placed['jour'] === $candidate['jour']
            ) {
                $ordrePlace = $candidate['creneau_ordre_by_id'][$placed['creneau_id']] ?? null;
                if ($ordrePlace !== null && abs($ordrePlace - $ordreCandidat) === 1) {
                    return 80;
                }
            }
        }

        return 0;
    }

    /**
     * +70 si on place un TP (PC ou SVT) en créneau consécutif d'un autre TP
     * de la même classe sur le même jour. Tandem guide ACE.
     */
    private function scoreTpConsecutif(array $candidate, array $unit, array $state): float
    {
        $matiereCode = $unit['matiere_code'] ?? null;

        if (!in_array($matiereCode, ['PC', 'SVT', 'PHYS', 'PHYS_CHIM'], true)) {
            return 0;
        }

        $ordreCandidat = $candidate['creneau_ordre'] ?? null;
        if (!$ordreCandidat) {
            return 0;
        }

        foreach ($state['assignments'] as $placed) {
            if (
                $placed['classe_id'] === $unit['classe_id']
                && $placed['jour'] === $candidate['jour']
                && in_array($placed['matiere_code'] ?? null, ['PC', 'SVT', 'PHYS', 'PHYS_CHIM'], true)
            ) {
                $ordrePlace = $candidate['creneau_ordre_by_id'][$placed['creneau_id']] ?? null;
                if ($ordrePlace !== null && abs($ordrePlace - $ordreCandidat) === 1) {
                    return 70;
                }
            }
        }

        return 0;
    }

    /**
     * Pénalise -30 par heure supplémentaire si le prof a déjà >= 3 heures
     * ce jour-là. Encourage la répartition sur toute la semaine.
     */
    private function scoreEquitableRepartition(array $candidate, array $state): float
    {
        if (empty($candidate['enseignant_id'])) {
            return 0;
        }

        $jour = $candidate['jour'];
        $eid  = $candidate['enseignant_id'];
        $heuresCeJour = 0;

        foreach ($state['assignments'] as $placed) {
            if ($placed['enseignant_id'] === $eid && $placed['jour'] === $jour) {
                $heuresCeJour++;
            }
        }

        // Au-delà de 3 heures dans la journée, pénalité croissante
        if ($heuresCeJour >= 3) {
            return -($heuresCeJour - 2) * 30;
        }

        return 0;
    }

    /**
     * -50 si le prof n'a qu'une seule heure sur ce jour et qu'elle n'est
     * adjacente à aucune autre. Évite les déplacements inutiles.
     * Guide ACE Annexe 2 : "Éviter de déplacer un prof pour une seule heure de cours."
     */
    private function scoreNoIsolatedHour(array $candidate, array $state): float
    {
        if (empty($candidate['enseignant_id'])) {
            return 0;
        }

        $jour = $candidate['jour'];
        $eid  = $candidate['enseignant_id'];
        $ordreCandidat = $candidate['creneau_ordre'] ?? null;

        if (!$ordreCandidat) {
            return 0;
        }

        $heuresDuJour = [];
        foreach ($state['assignments'] as $placed) {
            if ($placed['enseignant_id'] === $eid && $placed['jour'] === $jour) {
                $ordrePlace = $candidate['creneau_ordre_by_id'][$placed['creneau_id']] ?? null;
                if ($ordrePlace) {
                    $heuresDuJour[] = $ordrePlace;
                }
            }
        }

        if (empty($heuresDuJour)) {
            // Premier cours de ce prof ce jour : pas encore isolé, neutre
            return 0;
        }

        // Vérifie si le candidat est adjacent à une heure existante
        foreach ($heuresDuJour as $ordre) {
            if (abs($ordre - $ordreCandidat) <= 2) {
                return 0; // Adjacent ou proche → pas isolé
            }
        }

        // Vraiment isolé (écart > 2 créneaux)
        return -50;
    }

    /**
     * -40 si le prof enseigne déjà 3 niveaux différents et que ce placement
     * en ajouterait un 4ème. Guide ACE Annexe 2.
     */
    private function scoreMaxNiveaux(array $candidate, array $unit, array $state): float
    {
        if (empty($candidate['enseignant_id'])) {
            return 0;
        }

        $eid = $candidate['enseignant_id'];
        $niveauCourant = $unit['classe_niveau_id'] ?? null;

        if (!$niveauCourant) {
            return 0;
        }

        $niveauxDuProf = collect($state['assignments'])
            ->where('enseignant_id', $eid)
            ->pluck('classe_niveau_id')
            ->filter()
            ->unique()
            ->values();

        if ($niveauxDuProf->count() >= 3 && !$niveauxDuProf->contains($niveauCourant)) {
            return -40;
        }

        return 0;
    }

    private function enabled(Collection $constraints, string $code): bool
    {
        return (bool) optional($constraints->get($code))->enabled;
    }
}
