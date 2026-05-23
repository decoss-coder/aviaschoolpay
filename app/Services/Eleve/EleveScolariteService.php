<?php

namespace App\Services\Eleve;

use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use Illuminate\Support\Collection;

class EleveScolariteService
{
    public const MODE_INSCRIPTION_SEULE = 'inscription_seule';

    public const MODE_INSCRIPTION_ET_SCOLARITE = 'inscription_et_scolarite';

    /**
     * Synthèse financière pour portail élève / parent.
     *
     * @return array<string, mixed>
     */
    public static function resumePourEleve(Eleve $eleve, ?int $anneeScolaireId = null): array
    {
        $eleve->loadMissing('etablissement:id,nom');

        $annee = $anneeScolaireId
            ? AnneeScolaire::where('etablissement_id', $eleve->etablissement_id)->find($anneeScolaireId)
            : AnneeScolaire::where('etablissement_id', $eleve->etablissement_id)->where('en_cours', true)->first();

        $estNaff = $eleve->estNonAffecte();
        $estAff = $eleve->estAffecte();

        if (! $estNaff && ! $estAff) {
            return [
                'mode_facturation' => null,
                'inscription_applicable' => false,
                'scolarite_applicable' => false,
                'statut_eleve' => $eleve->statut_eleve,
                'statut_eleve_libelle' => $eleve->statut_eleve_libelle ?: '—',
                'message' => 'Statut élève (AFF/NAFF) non renseigné : contactez l\'administration.',
                'annee' => $annee?->only(['id', 'libelle']),
                'resume' => self::resumeVide('inconnu'),
                'inscriptions' => [],
            ];
        }

        $mode = $estNaff ? self::MODE_INSCRIPTION_ET_SCOLARITE : self::MODE_INSCRIPTION_SEULE;

        $inscriptions = self::inscriptionsQuery($eleve, $annee?->id)
            ->map(fn (Inscription $insc) => self::formatInscription($insc, $eleve))
            ->values()
            ->all();

        // Fallback : aucune inscription en base, mais l'élève est dans une classe.
        // On calcule une "inscription virtuelle" basée sur la classe actuelle pour
        // que les montants apparaissent quand même côté dashboard et fiche élève.
        if ($inscriptions === []) {
            $virtuelle = self::inscriptionVirtuelle($eleve, $annee);
            if ($virtuelle !== null) {
                $inscriptions = [$virtuelle];
            }
        }

        $resume = self::agregerResume($inscriptions);

        $message = null;
        if ($inscriptions === []) {
            $message = 'Aucune inscription validée pour l\'année en cours.';
        } elseif ($estAff) {
            $message = 'Élève affecté (AFF) : seuls les frais d\'inscription sont à régler à l\'école.';
        } else {
            $message = 'Élève non affecté (NAFF) : frais d\'inscription et scolarité annuelle à régler.';
        }

        return [
            'mode_facturation' => $mode,
            'inscription_applicable' => true,
            'scolarite_applicable' => $estNaff,
            'statut_eleve' => $eleve->statut_eleve,
            'statut_eleve_libelle' => $estNaff ? 'Non affecté' : 'Affecté',
            'message' => $message,
            'annee' => $annee?->only(['id', 'libelle']),
            'resume' => $resume,
            'inscriptions' => $inscriptions,
        ];
    }

    /**
     * Montant total dû pour une inscription selon AFF / NAFF.
     *
     * @return array{
     *   montant_inscription: int,
     *   montant_scolarite: int,
     *   montant_reduction: int,
     *   montant_total_du: int,
     *   libelle_inscription: string
     * }
     */
    public static function montantsDu(Inscription $insc, Eleve $eleve): array
    {
        $insc->loadMissing('classe');
        $classe = $insc->classe;
        $reduction = (int) $insc->reduction;

        $libelleInscription = $insc->type === 'renouvellement'
            ? 'Frais de réinscription'
            : 'Frais d\'inscription';

        if ($classe) {
            $fraisInscription = $insc->type === 'renouvellement'
                ? (int) $classe->frais_reinscription
                : (int) $classe->frais_inscription;
            $scolariteAnnuelle = (int) $classe->scolarite_annuelle;

            // Fallback : si la classe n'a pas de tarifs (0), on lit ceux du niveau parent.
            $niveau = $classe->niveau ?? null;
            if ($niveau) {
                if ($fraisInscription <= 0) {
                    $fraisInscription = $insc->type === 'renouvellement'
                        ? (int) ($niveau->frais_reinscription_defaut ?? 0)
                        : (int) ($niveau->frais_inscription_defaut ?? 0);
                }
                if ($scolariteAnnuelle <= 0) {
                    $scolariteAnnuelle = (int) ($niveau->frais_scolarite_defaut ?? 0);
                }
            }
        } else {
            $fraisInscription = 0;
            $scolariteAnnuelle = (int) $insc->montant_scolarite;
        }

        if ($eleve->estAffecte()) {
            $total = max(0, $fraisInscription - $reduction);

            return [
                'montant_inscription' => $fraisInscription,
                'montant_scolarite' => 0,
                'montant_reduction' => $reduction,
                'montant_total_du' => $total,
                'libelle_inscription' => $libelleInscription,
            ];
        }

        if ($eleve->estNonAffecte()) {
            if ($classe) {
                $total = max(0, $fraisInscription + $scolariteAnnuelle - $reduction);
            } else {
                $total = max(0, (int) $insc->montant_net);
                $fraisInscription = 0;
                $scolariteAnnuelle = (int) $insc->montant_scolarite;
            }

            return [
                'montant_inscription' => $fraisInscription,
                'montant_scolarite' => $scolariteAnnuelle,
                'montant_reduction' => $reduction,
                'montant_total_du' => $total,
                'libelle_inscription' => $libelleInscription,
            ];
        }

        return [
            'montant_inscription' => 0,
            'montant_scolarite' => (int) $insc->montant_scolarite,
            'montant_reduction' => $reduction,
            'montant_total_du' => max(0, (int) $insc->montant_net),
            'libelle_inscription' => $libelleInscription,
        ];
    }

    public static function resteAPayer(Inscription $insc, Eleve $eleve): int
    {
        $du = self::montantsDu($insc, $eleve);

        return max(0, $du['montant_total_du'] - $insc->montantPaye());
    }

    /** @return Collection<int, Inscription> */
    private static function inscriptionsQuery(Eleve $eleve, ?int $anneeId): Collection
    {
        $q = Inscription::query()
            ->where('eleve_id', $eleve->id)
            ->where('statut', 'validee')
            ->with([
                'anneeScolaire:id,libelle',
                'classe:id,nom,niveau_id,frais_inscription,frais_reinscription,scolarite_annuelle',
                'classe.niveau:id,libelle,frais_scolarite_defaut,frais_inscription_defaut,frais_reinscription_defaut',
                'paiements' => fn ($pq) => $pq->orderByDesc('date_paiement'),
                'echeances' => fn ($eq) => $eq->orderBy('date_echeance'),
            ])
            ->orderByDesc('date_inscription');

        if ($anneeId) {
            $q->where('annee_scolaire_id', $anneeId);
        }

        return $q->get();
    }

    /** @return array<string, mixed> */
    private static function formatInscription(Inscription $insc, Eleve $eleve): array
    {
        $du = self::montantsDu($insc, $eleve);
        $paye = $insc->montantPaye();
        $reste = max(0, $du['montant_total_du'] - $paye);
        $taux = $du['montant_total_du'] > 0
            ? round(($paye / $du['montant_total_du']) * 100, 1)
            : 0.0;

        $echeances = self::filtrerEcheances($insc, $eleve);

        $nbRetard = collect($echeances)->where('en_retard', true)->count();
        $montantRetard = collect($echeances)->where('en_retard', true)->sum('reste_a_payer');

        $paiements = $insc->paiements->map(fn ($p) => [
            'id' => $p->id,
            'reference' => $p->reference,
            'montant' => (int) $p->montant,
            'date_paiement' => $p->date_paiement?->toDateString(),
            'mode' => $p->mode,
            'statut' => $p->statut,
            'numero_recu' => $p->numero_recu,
        ])->values()->all();

        return [
            'id' => $insc->id,
            'annee_scolaire' => $insc->anneeScolaire?->only(['id', 'libelle']),
            'classe' => $insc->classe?->only(['id', 'nom']),
            'date_inscription' => $insc->date_inscription?->toDateString(),
            'type' => $insc->type,
            'statut_inscription' => $insc->statut,
            'montant_inscription' => $du['montant_inscription'],
            'montant_scolarite' => $du['montant_scolarite'],
            'montant_reduction' => $du['montant_reduction'],
            'montant_total_du' => $du['montant_total_du'],
            'montant_scolarite_enregistre' => (int) $insc->montant_scolarite,
            'montant_net_enregistre' => (int) $insc->montant_net,
            'montant_paye' => $paye,
            'reste_a_payer' => $reste,
            'taux_paiement' => $taux,
            'statut_paiement' => self::statutPaiement($du['montant_total_du'], $paye, $reste),
            'nb_echeances_en_retard' => $nbRetard,
            'montant_en_retard' => (int) $montantRetard,
            'echeances' => $echeances,
            'paiements' => $paiements,
            'libelle_inscription' => $du['libelle_inscription'],
        ];
    }

    /**
     * AFF : échéances liées à l'inscription uniquement.
     * NAFF : toutes les échéances.
     *
     * @return list<array<string, mixed>>
     */
    private static function filtrerEcheances(Inscription $insc, Eleve $eleve): array
    {
        return $insc->echeances
            ->filter(function ($ech) use ($eleve) {
                if ($eleve->estNonAffecte()) {
                    return true;
                }

                if ($eleve->estAffecte()) {
                    $libelle = mb_strtolower((string) $ech->libelle);

                    return str_contains($libelle, 'inscription')
                        || str_contains($libelle, 'réinscription')
                        || str_contains($libelle, 'reinscription')
                        || (int) $ech->numero_echeance === 1;
                }

                return true;
            })
            ->map(function ($ech) {
                $resteEch = (int) ($ech->reste_a_payer ?? max(0, ($ech->montant ?? 0) - ($ech->montant_paye ?? 0)));
                $enRetard = $ech->date_echeance && $ech->date_echeance->isPast() && $resteEch > 0;

                return [
                    'id' => $ech->id,
                    'libelle' => $ech->libelle,
                    'numero_echeance' => $ech->numero_echeance,
                    'montant' => (int) $ech->montant,
                    'montant_paye' => (int) ($ech->montant_paye ?? 0),
                    'reste_a_payer' => $resteEch,
                    'date_echeance' => $ech->date_echeance?->toDateString(),
                    'statut' => $ech->statut,
                    'en_retard' => $enRetard,
                    'type_poste' => self::typePosteEcheance($ech->libelle),
                ];
            })
            ->values()
            ->all();
    }

    private static function typePosteEcheance(?string $libelle): string
    {
        $l = mb_strtolower((string) $libelle);

        if (str_contains($l, 'inscription') || str_contains($l, 'réinscription') || str_contains($l, 'reinscription')) {
            return 'inscription';
        }

        return 'scolarite';
    }

    private static function statutPaiement(int $montantDu, int $paye, int $reste): string
    {
        if ($montantDu <= 0) {
            return 'sans_montant';
        }

        if ($reste <= 0) {
            return 'solde';
        }

        if ($paye > 0) {
            return 'partiel';
        }

        return 'impaye';
    }

    /**
     * Construit une "inscription virtuelle" basée sur la classe actuelle de l'élève
     * + tarifs du niveau si la classe n'a pas de tarifs configurés.
     * Permet d'afficher les montants même tant qu'aucune inscription n'a été créée.
     *
     * @return array<string, mixed>|null
     */
    private static function inscriptionVirtuelle(Eleve $eleve, ?AnneeScolaire $annee): ?array
    {
        $eleve->loadMissing(['classe.niveau']);
        $classe = $eleve->classe;
        if (! $classe) {
            return null;
        }

        $niveau = $classe->niveau;
        $fraisIns = (int) $classe->frais_inscription;
        if ($fraisIns <= 0 && $niveau) {
            $fraisIns = (int) ($niveau->frais_inscription_defaut ?? 0);
        }
        $scolarite = (int) $classe->scolarite_annuelle;
        if ($scolarite <= 0 && $niveau) {
            $scolarite = (int) ($niveau->frais_scolarite_defaut ?? 0);
        }

        if ($fraisIns <= 0 && $scolarite <= 0) {
            return null;
        }

        $isAff = $eleve->estAffecte();
        $montantIns = $fraisIns;
        $montantScol = $isAff ? 0 : $scolarite;
        $total = $montantIns + $montantScol;

        return [
            'id' => null,
            'annee_scolaire' => $annee?->only(['id', 'libelle']),
            'classe' => $classe->only(['id', 'nom']),
            'date_inscription' => null,
            'type' => 'nouvelle',
            'statut_inscription' => 'projetee',
            'montant_inscription' => $montantIns,
            'montant_scolarite' => $montantScol,
            'montant_reduction' => 0,
            'montant_total_du' => $total,
            'montant_scolarite_enregistre' => $montantScol,
            'montant_net_enregistre' => $total,
            'montant_paye' => 0,
            'reste_a_payer' => $total,
            'taux_paiement' => 0.0,
            'statut_paiement' => $total > 0 ? 'impaye' : 'a_jour',
            'nb_echeances_en_retard' => 0,
            'montant_en_retard' => 0,
            'echeances' => [],
            'paiements' => [],
            'libelle_inscription' => 'Frais d\'inscription',
            'projetee' => true,
        ];
    }

    /** @param list<array<string, mixed>> $inscriptions */
    private static function agregerResume(array $inscriptions): array
    {
        if ($inscriptions === []) {
            return self::resumeVide('aucune_inscription');
        }

        $total = array_sum(array_column($inscriptions, 'montant_total_du'));
        $totalInscription = array_sum(array_column($inscriptions, 'montant_inscription'));
        $totalScolarite = array_sum(array_column($inscriptions, 'montant_scolarite'));
        $paye = array_sum(array_column($inscriptions, 'montant_paye'));
        $reste = array_sum(array_column($inscriptions, 'reste_a_payer'));
        $nbRetard = array_sum(array_column($inscriptions, 'nb_echeances_en_retard'));
        $montantRetard = array_sum(array_column($inscriptions, 'montant_en_retard'));

        $taux = $total > 0 ? round(($paye / $total) * 100, 1) : 0.0;

        $statutGlobal = 'impaye';
        if ($total <= 0) {
            $statutGlobal = 'sans_montant';
        } elseif ($reste <= 0) {
            $statutGlobal = 'solde';
        } elseif ($paye > 0) {
            $statutGlobal = 'partiel';
        }

        $prochaineEcheance = null;
        foreach ($inscriptions as $insc) {
            foreach ($insc['echeances'] as $ech) {
                if (($ech['reste_a_payer'] ?? 0) > 0 && $ech['date_echeance']) {
                    if (! $prochaineEcheance || $ech['date_echeance'] < $prochaineEcheance['date_echeance']) {
                        $prochaineEcheance = $ech;
                    }
                }
            }
        }

        return [
            'montant_inscription' => $totalInscription,
            'montant_scolarite' => $totalScolarite,
            'montant_total' => $total,
            'montant_paye' => $paye,
            'reste_a_payer' => $reste,
            'taux_paiement' => $taux,
            'statut_paiement' => $statutGlobal,
            'en_retard' => $nbRetard > 0,
            'nb_echeances_en_retard' => $nbRetard,
            'montant_en_retard' => $montantRetard,
            'prochaine_echeance' => $prochaineEcheance,
        ];
    }

    /** @return array<string, mixed> */
    private static function resumeVide(string $statut): array
    {
        return [
            'montant_inscription' => 0,
            'montant_scolarite' => 0,
            'montant_total' => 0,
            'montant_paye' => 0,
            'reste_a_payer' => 0,
            'taux_paiement' => 0,
            'statut_paiement' => $statut,
            'en_retard' => false,
            'nb_echeances_en_retard' => 0,
            'montant_en_retard' => 0,
            'prochaine_echeance' => null,
        ];
    }
}
