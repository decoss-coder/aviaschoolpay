<?php

namespace App\Services\Presence;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\Eleve;
use App\Models\PresenceEleve;
use App\Models\Trimestre;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service de calcul des bilans de présence pour la direction.
 *
 * Fournit :
 *  - Bilan par élève (sur période ou trimestre/année)
 *  - Bilan par classe
 *  - Statistiques globales établissement
 *  - Calcul des heures d'absence en multipliant la durée des créneaux
 */
class PresenceBilanService
{
    /**
     * Bilan complet d'un élève sur une période donnée.
     *
     * @return array{
     *   eleve: array,
     *   periode: array{debut: string, fin: string, label: ?string},
     *   total: int,
     *   presents: int,
     *   absents: int,
     *   retards: int,
     *   justifies: int,
     *   non_justifies: int,
     *   heures_absence: float,
     *   minutes_absence: int,
     *   heures_retard: float,
     *   par_jour: array,
     *   par_creneau: array,
     *   par_matiere: array,
     *   absences_recentes: array,
     * }
     */
    public function bilanEleve(Eleve $eleve, Carbon $debut, Carbon $fin, ?string $periodeLabel = null): array
    {
        $presences = PresenceEleve::where('eleve_id', $eleve->id)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->with(['classe:id,nom', 'matiere:id,nom,code', 'creneau:id,heure_debut,heure_fin,libelle,type', 'enseignant:id,nom,prenom'])
            ->orderByDesc('date')
            ->get();

        return $this->compileBilan($eleve, $presences, $debut, $fin, $periodeLabel);
    }

    /**
     * Bilan d'un élève sur un trimestre.
     */
    public function bilanEleveTrimestre(Eleve $eleve, Trimestre $trimestre): array
    {
        $debut = Carbon::parse($trimestre->date_debut);
        $fin = Carbon::parse($trimestre->date_fin);
        return $this->bilanEleve($eleve, $debut, $fin, $trimestre->libelle ?? "Trimestre {$trimestre->numero}");
    }

    /**
     * Bilan d'un élève sur une année scolaire.
     */
    public function bilanEleveAnnee(Eleve $eleve, AnneeScolaire $annee): array
    {
        $debut = Carbon::parse($annee->date_debut);
        $fin = Carbon::parse($annee->date_fin);
        return $this->bilanEleve($eleve, $debut, $fin, $annee->libelle);
    }

    /**
     * Bilan d'une classe sur une période : agrège les bilans par élève.
     *
     * @return array
     */
    public function bilanClasse(Classe $classe, Carbon $debut, Carbon $fin, ?string $periodeLabel = null): array
    {
        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        // Toutes les présences en une seule requête, regroupées par élève
        $presences = PresenceEleve::whereIn('eleve_id', $eleves->pluck('id'))
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->with(['creneau:id,heure_debut,heure_fin,libelle'])
            ->get()
            ->groupBy('eleve_id');

        $rows = [];
        $totaux = ['total' => 0, 'presents' => 0, 'absents' => 0, 'retards' => 0, 'justifies' => 0, 'minutes_absence' => 0];

        foreach ($eleves as $eleve) {
            $eleveP = $presences->get($eleve->id) ?? collect();
            $counts = $this->countByStatut($eleveP);
            $minutesAbs = $this->totalMinutesAbsence($eleveP);

            $rows[] = [
                'eleve' => [
                    'id'              => $eleve->id,
                    'nom'             => $eleve->nom,
                    'prenom'          => $eleve->prenom,
                    'matricule'       => $eleve->matricule_interne ?? $eleve->matricule_desps,
                    'matricule_desps' => $eleve->matricule_desps,
                ],
                'total'           => $counts['total'],
                'presents'        => $counts['presents'],
                'absents'         => $counts['absents'],
                'retards'         => $counts['retards'],
                'justifies'       => $counts['justifies'],
                'non_justifies'   => $counts['absents'] - $counts['justifies'],
                'minutes_absence' => $minutesAbs,
                'heures_absence'  => round($minutesAbs / 60, 2),
                'taux_absence'    => $counts['total'] > 0
                    ? round(($counts['absents'] / $counts['total']) * 100, 1)
                    : 0,
            ];

            $totaux['total']           += $counts['total'];
            $totaux['presents']        += $counts['presents'];
            $totaux['absents']         += $counts['absents'];
            $totaux['retards']         += $counts['retards'];
            $totaux['justifies']       += $counts['justifies'];
            $totaux['minutes_absence'] += $minutesAbs;
        }

        return [
            'classe' => $classe->only(['id', 'nom']),
            'periode' => [
                'debut' => $debut->toDateString(),
                'fin'   => $fin->toDateString(),
                'label' => $periodeLabel,
            ],
            'eleves' => $rows,
            'totaux' => [
                'nb_eleves'       => $eleves->count(),
                'total'           => $totaux['total'],
                'presents'        => $totaux['presents'],
                'absents'         => $totaux['absents'],
                'retards'         => $totaux['retards'],
                'justifies'       => $totaux['justifies'],
                'non_justifies'   => $totaux['absents'] - $totaux['justifies'],
                'minutes_absence' => $totaux['minutes_absence'],
                'heures_absence'  => round($totaux['minutes_absence'] / 60, 2),
                'moyenne_heures_absence_par_eleve' => $eleves->count() > 0
                    ? round(($totaux['minutes_absence'] / 60) / $eleves->count(), 2)
                    : 0,
            ],
        ];
    }

    /**
     * Bilan global établissement sur une période :
     * top classes & top élèves avec le plus d'absences.
     */
    public function bilanEtablissement(int $etablissementId, Carbon $debut, Carbon $fin, ?string $periodeLabel = null): array
    {
        // Toutes les présences (absent/retard) sur la période pour l'établissement
        $presences = PresenceEleve::whereHas('classe', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->with(['eleve:id,nom,prenom,matricule_interne,matricule_desps,classe_id', 'classe:id,nom', 'creneau:id,heure_debut,heure_fin'])
            ->get();

        // Top 10 classes (par nb absences)
        $byClasse = $presences->where('statut', 'absent')->groupBy('classe_id');
        $topClasses = $byClasse->map(function ($items, $classeId) {
            $first = $items->first();
            $minutes = $this->totalMinutesAbsence($items);
            return [
                'classe_id' => $classeId,
                'classe'    => $first?->classe?->nom ?? '—',
                'nb_absences' => $items->count(),
                'heures_absence' => round($minutes / 60, 2),
            ];
        })->sortByDesc('nb_absences')->take(10)->values()->all();

        // Top 20 élèves (par nb absences)
        $byEleve = $presences->where('statut', 'absent')->groupBy('eleve_id');
        $topEleves = $byEleve->map(function ($items, $eleveId) {
            $first = $items->first();
            $eleve = $first?->eleve;
            $minutes = $this->totalMinutesAbsence($items);
            $justifies = $items->where('justifie', true)->count();
            return [
                'eleve_id'        => $eleveId,
                'nom'             => $eleve?->nom,
                'prenom'          => $eleve?->prenom,
                'matricule_desps' => $eleve?->matricule_desps,
                'classe'          => $first?->classe?->nom ?? '—',
                'nb_absences'     => $items->count(),
                'nb_justifies'    => $justifies,
                'nb_non_justifies'=> $items->count() - $justifies,
                'heures_absence'  => round($minutes / 60, 2),
            ];
        })->sortByDesc('nb_absences')->take(20)->values()->all();

        $totalAbs = $presences->where('statut', 'absent')->count();
        $totalRet = $presences->where('statut', 'retard')->count();
        $totalJus = $presences->where('statut', 'absent')->where('justifie', true)->count();

        return [
            'periode' => [
                'debut' => $debut->toDateString(),
                'fin'   => $fin->toDateString(),
                'label' => $periodeLabel,
            ],
            'totaux' => [
                'total_appels'    => $presences->count(),
                'absences'        => $totalAbs,
                'retards'         => $totalRet,
                'justifies'       => $totalJus,
                'non_justifies'   => $totalAbs - $totalJus,
                'heures_absence'  => round($this->totalMinutesAbsence($presences) / 60, 2),
            ],
            'top_classes' => $topClasses,
            'top_eleves'  => $topEleves,
        ];
    }

    // ── Helpers internes ─────────────────────────────────────────────────────

    private function compileBilan(Eleve $eleve, Collection $presences, Carbon $debut, Carbon $fin, ?string $label): array
    {
        $counts = $this->countByStatut($presences);

        // Heures d'absence (durée des créneaux × nb absences)
        $minutesAbs = $this->totalMinutesAbsence($presences->where('statut', 'absent'));
        $minutesRet = $this->totalMinutesAbsence($presences->where('statut', 'retard')) / 4; // retard = ~25% du créneau

        // Par jour de la semaine
        $parJour = $presences->where('statut', 'absent')->groupBy(function ($p) {
            return strtolower(Carbon::parse($p->date)->locale('fr')->isoFormat('dddd'));
        })->map(fn ($items) => $items->count())->all();

        // Par créneau (heure de la journée)
        $parCreneau = $presences->where('statut', 'absent')->groupBy('creneau_id')->map(function ($items, $creneauId) {
            $cr = $items->first()->creneau;
            return [
                'creneau_id' => $creneauId,
                'libelle'    => $cr?->libelle ?? ($cr ? substr($cr->heure_debut, 0, 5) . '-' . substr($cr->heure_fin, 0, 5) : '?'),
                'heure'      => $cr ? substr($cr->heure_debut, 0, 5) : null,
                'count'      => $items->count(),
            ];
        })->sortBy('heure')->values()->all();

        // Par matière
        $parMatiere = $presences->where('statut', 'absent')
            ->filter(fn ($p) => $p->matiere_id)
            ->groupBy('matiere_id')
            ->map(function ($items) {
                $mat = $items->first()->matiere;
                return [
                    'matiere_id' => $items->first()->matiere_id,
                    'nom'        => $mat?->nom ?? '—',
                    'code'       => $mat?->code,
                    'count'      => $items->count(),
                ];
            })->sortByDesc('count')->values()->all();

        // 20 dernières absences
        $absencesRecentes = $presences->where('statut', 'absent')->take(20)->map(function ($p) {
            return [
                'id'           => $p->id,
                'date'         => $p->date,
                'creneau'      => $p->creneau ? substr($p->creneau->heure_debut, 0, 5) . '-' . substr($p->creneau->heure_fin, 0, 5) : null,
                'periode'      => $p->periode,
                'classe'       => $p->classe?->nom,
                'matiere'      => $p->matiere?->nom,
                'enseignant'   => $p->enseignant ? $p->enseignant->prenom . ' ' . $p->enseignant->nom : null,
                'justifie'     => (bool) $p->justifie,
                'motif'        => $p->motif,
                'justification' => $p->justification,
                'observation'  => $p->observation,
            ];
        })->values()->all();

        return [
            'eleve' => [
                'id'              => $eleve->id,
                'nom'             => $eleve->nom,
                'prenom'          => $eleve->prenom,
                'matricule'       => $eleve->matricule_interne ?? $eleve->matricule_desps,
                'matricule_desps' => $eleve->matricule_desps,
                'classe_id'       => $eleve->classe_id,
            ],
            'periode' => [
                'debut' => $debut->toDateString(),
                'fin'   => $fin->toDateString(),
                'label' => $label,
            ],
            'total'           => $counts['total'],
            'presents'        => $counts['presents'],
            'absents'         => $counts['absents'],
            'retards'         => $counts['retards'],
            'justifies'       => $counts['justifies'],
            'non_justifies'   => $counts['absents'] - $counts['justifies'],
            'minutes_absence' => $minutesAbs,
            'heures_absence'  => round($minutesAbs / 60, 2),
            'heures_retard'   => round($minutesRet / 60, 2),
            'taux_absence'    => $counts['total'] > 0
                ? round(($counts['absents'] / $counts['total']) * 100, 1)
                : 0,
            'par_jour'        => $parJour,
            'par_creneau'     => $parCreneau,
            'par_matiere'     => $parMatiere,
            'absences_recentes' => $absencesRecentes,
        ];
    }

    private function countByStatut(Collection $presences): array
    {
        return [
            'total'     => $presences->count(),
            'presents'  => $presences->where('statut', 'present')->count(),
            'absents'   => $presences->where('statut', 'absent')->count(),
            'retards'   => $presences->where('statut', 'retard')->count(),
            'justifies' => $presences->where('statut', 'absent')->where('justifie', true)->count(),
        ];
    }

    /**
     * Calcule le total de minutes d'absence en sommant la durée des créneaux concernés.
     * Pour les présences journalières (sans créneau), utilise une durée par défaut.
     */
    private function totalMinutesAbsence(Collection $presences): int
    {
        $total = 0;
        $journeeMin = $this->dureeJourneeDefault();

        foreach ($presences as $p) {
            if ($p->creneau) {
                $total += $this->dureeCreneauMinutes($p->creneau);
            } else {
                // Pas de créneau (mode journée) :
                // matin/apres_midi = demi-journée, journee = journée complète
                $total += match ($p->periode ?? 'journee') {
                    'matin', 'apres_midi' => intdiv($journeeMin, 2),
                    default => $journeeMin,
                };
            }
        }

        return $total;
    }

    private function dureeCreneauMinutes(Creneau $creneau): int
    {
        if (! $creneau->heure_debut || ! $creneau->heure_fin) return 55;
        $debut = Carbon::parse($creneau->heure_debut);
        $fin = Carbon::parse($creneau->heure_fin);
        return max(0, $debut->diffInMinutes($fin));
    }

    private function dureeJourneeDefault(): int
    {
        return 6 * 60; // 6h par défaut pour une journée scolaire
    }
}
