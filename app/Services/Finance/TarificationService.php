<?php

namespace App\Services\Finance;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\Paiement;
use App\Services\Eleve\EleveScolariteService;
use Illuminate\Support\Collection;

class TarificationService
{
    public const CYCLE_COLLEGE = 'premier_cycle';

    public const CYCLE_LYCEE = 'second_cycle';

    public static function isCollege(Niveau|string|null $niveau): bool
    {
        $cycle = $niveau instanceof Niveau ? $niveau->cycle : null;

        return $cycle === self::CYCLE_COLLEGE;
    }

    public static function isLycee(Niveau|string|null $niveau): bool
    {
        $cycle = $niveau instanceof Niveau ? $niveau->cycle : null;

        return $cycle === self::CYCLE_LYCEE;
    }

    /**
     * Applique les tarifs d'un niveau à toutes ses classes (année en cours).
     */
    public static function appliquerNiveauSurClasses(Niveau $niveau, int $anneeScolaireId): int
    {
        $classes = Classe::query()
            ->where('niveau_id', $niveau->id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->get();

        foreach ($classes as $classe) {
            $classe->update([
                'scolarite_annuelle' => (int) ($niveau->frais_scolarite_defaut ?? 0),
                'frais_inscription' => (int) ($niveau->frais_inscription_defaut ?? 0),
                'frais_reinscription' => (int) ($niveau->frais_reinscription_defaut ?? 0),
            ]);
        }

        return $classes->count();
    }

    /**
     * Collège — applique une grille tarifaire à un sous-ensemble de niveaux collège.
     * Si $niveauIds est null, applique à TOUS les niveaux collège (rétrocompat).
     *
     * @param  array<int>|null  $niveauIds  Restreint l'application à ces niveaux uniquement.
     */
    public static function appliquerCollegeUniforme(
        int $etablissementId,
        int $anneeScolaireId,
        int $scolariteAnnuelle,
        int $fraisInscription,
        int $fraisReinscription,
        ?array $niveauIds = null
    ): int {
        $query = Niveau::query()
            ->where('etablissement_id', $etablissementId)
            ->where('cycle', self::CYCLE_COLLEGE);

        if (! empty($niveauIds)) {
            $query->whereIn('id', $niveauIds);
        }

        $niveaux = $query->get();
        $count = 0;

        foreach ($niveaux as $niveau) {
            $niveau->update([
                'frais_scolarite_defaut' => $scolariteAnnuelle,
                'frais_inscription_defaut' => $fraisInscription,
                'frais_reinscription_defaut' => $fraisReinscription,
            ]);
            $count += self::appliquerNiveauSurClasses($niveau, $anneeScolaireId);
        }

        return $count;
    }

    /**
     * Identifie le niveau "3ème" (dernière année du collège) parmi les niveaux collège.
     * Détection : code commence par "3" OU libelle contient "3" OU plus grand ordre.
     */
    public static function niveauTroisieme(int $etablissementId): ?Niveau
    {
        $college = Niveau::query()
            ->where('etablissement_id', $etablissementId)
            ->where('cycle', self::CYCLE_COLLEGE)
            ->orderByDesc('ordre')
            ->get();

        if ($college->isEmpty()) {
            return null;
        }

        // 1. Cherche par code
        $byCode = $college->first(fn ($n) => str_starts_with(strtoupper((string) $n->code), '3'));
        if ($byCode) {
            return $byCode;
        }

        // 2. Cherche par libelle
        $byLibelle = $college->first(fn ($n) => str_contains((string) $n->libelle, '3'));
        if ($byLibelle) {
            return $byLibelle;
        }

        // 3. Fallback : dernier niveau collège (ordre le plus grand)
        return $college->first();
    }

    /**
     * Identifie le(s) niveau(x) "Terminale" du lycée. Plusieurs séries possibles
     * (TleA, TleC, TleD, TleG, ...). Détection : code commence par "T" / "TLE"
     * OU libellé contient "terminale". À défaut, dernier niveau lycée par ordre.
     *
     * @return \Illuminate\Support\Collection<int, Niveau>
     */
    public static function niveauxTerminale(int $etablissementId): Collection
    {
        $lycee = Niveau::query()
            ->where('etablissement_id', $etablissementId)
            ->where('cycle', self::CYCLE_LYCEE)
            ->orderByDesc('ordre')
            ->get();

        if ($lycee->isEmpty()) {
            return collect();
        }

        // 1. Cherche par code/libelle
        $matches = $lycee->filter(function ($n) {
            $code = strtoupper((string) $n->code);
            $lib = mb_strtolower((string) $n->libelle);
            return str_starts_with($code, 'T')
                || str_starts_with($code, 'TLE')
                || str_contains($lib, 'terminale')
                || str_contains($lib, 'tle');
        });

        if ($matches->isNotEmpty()) {
            return $matches->values();
        }

        // 2. Fallback : dernier niveau lycée
        return collect([$lycee->first()]);
    }

    /**
     * Lycée — applique une grille tarifaire à un sous-ensemble de niveaux lycée.
     * Si $niveauIds est null, applique à TOUS les niveaux lycée.
     *
     * @param  array<int>|null  $niveauIds
     */
    public static function appliquerLyceeUniforme(
        int $etablissementId,
        int $anneeScolaireId,
        int $scolariteAnnuelle,
        int $fraisInscription,
        int $fraisReinscription,
        ?array $niveauIds = null
    ): int {
        $query = Niveau::query()
            ->where('etablissement_id', $etablissementId)
            ->where('cycle', self::CYCLE_LYCEE);

        if (! empty($niveauIds)) {
            $query->whereIn('id', $niveauIds);
        }

        $niveaux = $query->get();
        $count = 0;

        foreach ($niveaux as $niveau) {
            $niveau->update([
                'frais_scolarite_defaut' => $scolariteAnnuelle,
                'frais_inscription_defaut' => $fraisInscription,
                'frais_reinscription_defaut' => $fraisReinscription,
            ]);
            $count += self::appliquerNiveauSurClasses($niveau, $anneeScolaireId);
        }

        return $count;
    }

    /**
     * Calcule et enregistre montants sur l'inscription (source unique pour paiements).
     */
    public static function synchroniserInscription(Inscription $inscription, ?Eleve $eleve = null): Inscription
    {
        $inscription->loadMissing(['classe.niveau']);
        $eleve ??= $inscription->eleve;

        if (! $eleve) {
            return $inscription;
        }

        $du = EleveScolariteService::montantsDu($inscription, $eleve);

        $inscription->update([
            'montant_inscription' => $du['montant_inscription'],
            'montant_scolarite' => $du['montant_scolarite'],
            'montant_net' => $du['montant_total_du'],
        ]);

        return $inscription->fresh();
    }

    /**
     * Recalcule toutes les inscriptions validées de l'établissement pour l'année en cours.
     */
    public static function synchroniserEtablissement(int $etablissementId, int $anneeScolaireId): int
    {
        $inscriptions = Inscription::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'validee')
            ->with(['eleve', 'classe.niveau'])
            ->get();

        foreach ($inscriptions as $inscription) {
            if ($inscription->eleve) {
                self::synchroniserInscription($inscription, $inscription->eleve);
            }
        }

        return $inscriptions->count();
    }

    /**
     * @return array{total_du: int, total_paye: int, reste: int, taux: float, par_statut: array<string, int>}
     */
    public static function recouvrementEtablissement(int $etablissementId, int $anneeScolaireId): array
    {
        $inscriptions = Inscription::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'validee')
            ->with(['eleve', 'classe.niveau', 'paiements'])
            ->get();

        $totalDu = 0;
        $totalPaye = 0;
        $parStatut = ['AFF' => 0, 'NAFF' => 0, 'autre' => 0];

        foreach ($inscriptions as $inscription) {
            $eleve = $inscription->eleve;
            if (! $eleve) {
                continue;
            }

            $du = EleveScolariteService::montantsDu($inscription, $eleve);
            $paye = $inscription->montantPaye();
            $totalDu += $du['montant_total_du'];
            $totalPaye += $paye;

            $key = $eleve->estAffecte() ? 'AFF' : ($eleve->estNonAffecte() ? 'NAFF' : 'autre');
            $parStatut[$key] = ($parStatut[$key] ?? 0) + max(0, $du['montant_total_du'] - $paye);
        }

        $reste = max(0, $totalDu - $totalPaye);

        return [
            'total_du' => $totalDu,
            'total_paye' => $totalPaye,
            'reste' => $reste,
            'taux' => $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 1) : 0,
            'par_statut' => $parStatut,
        ];
    }

    /**
     * Recouvrement PROJETÉ : basé sur les élèves actifs × tarifs niveau/classe selon AFF/NAFF.
     * Fonctionne même sans inscriptions synchronisées — utile pour le tableau de bord.
     *
     * @return array{
     *   total_du: int, total_paye: int, reste: int, taux: float,
     *   par_statut: array<string, int>,
     *   nb_eleves: int, nb_aff: int, nb_naff: int, nb_inconnu: int
     * }
     */
    public static function recouvrementProjete(int $etablissementId, int $anneeScolaireId): array
    {
        // Effectifs par classe par statut (1 seul query agrégé)
        $effectifs = Eleve::query()
            ->select('classe_id', 'statut_eleve', \Illuminate\Support\Facades\DB::raw('COUNT(*) as nb'))
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->whereNotNull('classe_id')
            ->groupBy('classe_id', 'statut_eleve')
            ->get()
            ->groupBy('classe_id');

        // Classes de l'année (avec niveau pour fallback tarifs)
        $classes = Classe::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->with('niveau:id,frais_scolarite_defaut,frais_inscription_defaut,frais_reinscription_defaut')
            ->get()
            ->keyBy('id');

        $totalDu = 0;
        $nbEleves = 0;
        $nbAff = 0;
        $nbNaff = 0;
        $nbInconnu = 0;
        $duAff = 0;
        $duNaff = 0;

        foreach ($effectifs as $classeId => $rows) {
            $classe = $classes->get($classeId);
            if (! $classe) {
                continue;
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

            foreach ($rows as $row) {
                $statut = strtoupper((string) $row->statut_eleve);
                $nb = (int) $row->nb;
                $nbEleves += $nb;

                if ($statut === 'AFF') {
                    $nbAff += $nb;
                    $duAff += $nb * $fraisIns;
                } elseif ($statut === 'NAFF') {
                    $nbNaff += $nb;
                    $duNaff += $nb * ($fraisIns + $scolarite);
                } else {
                    $nbInconnu += $nb;
                }
            }
        }

        $totalDu = $duAff + $duNaff;

        // Encaissé : somme des paiements confirmés de l'année (peu importe la synchro inscription)
        $totalPaye = (int) Paiement::query()
            ->where('etablissement_id', $etablissementId)
            ->where('statut', 'confirme')
            ->whereHas('inscription', fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->sum('montant');

        $reste = max(0, $totalDu - $totalPaye);

        // Imputation du payé par statut : proportionnellement au dû
        $payeAff = $totalDu > 0 ? (int) round($totalPaye * ($duAff / $totalDu)) : 0;
        $payeNaff = $totalPaye - $payeAff;

        return [
            'total_du' => $totalDu,
            'total_paye' => $totalPaye,
            'reste' => $reste,
            'taux' => $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 1) : 0,
            'par_statut' => [
                'AFF' => max(0, $duAff - $payeAff),
                'NAFF' => max(0, $duNaff - $payeNaff),
                'autre' => 0,
            ],
            'nb_eleves' => $nbEleves,
            'nb_aff' => $nbAff,
            'nb_naff' => $nbNaff,
            'nb_inconnu' => $nbInconnu,
            'du_aff' => $duAff,
            'du_naff' => $duNaff,
        ];
    }

    /** @return Collection<int, Niveau> */
    public static function niveauxParCycle(int $etablissementId): Collection
    {
        return Niveau::query()
            ->where('etablissement_id', $etablissementId)
            ->orderBy('ordre')
            ->get()
            ->groupBy('cycle');
    }
}
