<?php

namespace App\Services\Platform;

use App\Models\AnneeScolaire;
use App\Models\AnneeScolaireRestaurationDemande;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Niveau;
use App\Models\Notification;
use App\Models\Paiement;
use App\Models\Pointage;
use App\Models\User;
use App\Services\Finance\TarificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlatformStatsService
{
    /**
     * Statistiques globales plateforme Avia.
     * @return array<string, mixed>
     */
    public static function globales(): array
    {
        $etabs   = Etablissement::query()->count();
        $actifs  = Etablissement::query()->where('actif', true)->count();
        $bloques = $etabs - $actifs;

        $totalUsers   = User::query()->where('role', '!=', 'super_admin')->count();
        $usersActifs  = User::query()->where('role', '!=', 'super_admin')->where('actif', true)->count();
        $totalParents = User::query()->where('role', 'parent')->count();
        // Total élèves plateforme = inscrits cette année (cumul sur toutes les écoles)
        $totalEleves = Eleve::query()->where('actif', true)
            ->whereHas('inscriptions', function ($q) {
                $q->where('statut', 'validee')
                  ->whereHas('anneeScolaire', fn($a) => $a->where('en_cours', true));
            })
            ->count();
        $totalEnseignants = Enseignant::query()->where('actif', true)->count();

        $moisActuel = now()->format('Y-m');
        $paiementsMois = (int) Paiement::query()
            ->where('statut', 'confirme')
            ->where('date_paiement', 'like', "$moisActuel%")
            ->sum('montant');

        $paiementsAnnee = (int) Paiement::query()
            ->where('statut', 'confirme')
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');

        $nbPaiementsAujourdhui = Paiement::query()
            ->where('statut', 'confirme')
            ->whereDate('date_paiement', today())
            ->count();

        // Revenus Avia (restaurations payées)
        $revenusAviaMois = (int) AnneeScolaireRestaurationDemande::query()
            ->whereIn('statut', ['paye', 'cle_livree', 'restauree'])
            ->where('paye_at', '>=', now()->startOfMonth())
            ->sum('montant_fcfa');

        $revenusAviaTotal = (int) AnneeScolaireRestaurationDemande::query()
            ->whereIn('statut', ['paye', 'cle_livree', 'restauree'])
            ->sum('montant_fcfa');

        $demandesEnAttente = AnneeScolaireRestaurationDemande::query()
            ->where('statut', 'en_attente_paiement')
            ->count();

        $demandesPayees = AnneeScolaireRestaurationDemande::query()
            ->where('statut', 'paye')
            ->count();

        // Pointages aujourd'hui (santé app mobile)
        $pointagesJour = Pointage::query()->whereDate('date', today())->count();

        // Notifications envoyées 7 jours
        $notifsSemaine = Notification::query()
            ->where('envoyee', true)
            ->where('envoyee_at', '>=', now()->subDays(7))
            ->count();

        // Wave
        $wavActifs = Etablissement::query()->where('wave_actif', true)->count();

        // Archives chiffrées
        $archivesTotal = AnneeScolaire::query()->where('archivee', true)->count();

        // Taux d'activation = établissements avec année en cours / total
        $etabsAvecAnnee = AnneeScolaire::query()
            ->where('en_cours', true)
            ->distinct('etablissement_id')
            ->count('etablissement_id');
        $tauxActivation = $etabs > 0 ? round(($etabsAvecAnnee / $etabs) * 100) : 0;

        return [
            'etablissements_total'    => $etabs,
            'etablissements_actifs'   => $actifs,
            'etablissements_bloques'  => $bloques,
            'taux_activation'         => $tauxActivation,
            'utilisateurs_total'      => $totalUsers,
            'utilisateurs_actifs'     => $usersActifs,
            'total_parents'           => $totalParents,
            'eleves_total'            => $totalEleves,
            'enseignants_total'       => $totalEnseignants,
            'paiements_mois'          => $paiementsMois,
            'paiements_annee'         => $paiementsAnnee,
            'paiements_jour_nombre'   => $nbPaiementsAujourdhui,
            'revenus_avia_mois'       => $revenusAviaMois,
            'revenus_avia_total'      => $revenusAviaTotal,
            'demandes_en_attente'     => $demandesEnAttente,
            'demandes_payees'         => $demandesPayees,
            'pointages_jour'          => $pointagesJour,
            'notifs_semaine'          => $notifsSemaine,
            'wave_actifs'             => $wavActifs,
            'archives_total'          => $archivesTotal,
        ];
    }

    /**
     * Évolution des paiements sur N jours (toutes écoles).
     */
    public static function evolutionPaiements(int $jours = 14): array
    {
        $data = [];
        for ($i = $jours - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $row = Paiement::query()
                ->where('statut', 'confirme')
                ->whereDate('date_paiement', $date)
                ->selectRaw('COUNT(*) as nombre, COALESCE(SUM(montant), 0) as montant')
                ->first();
            $data[] = [
                'date'    => Carbon::parse($date)->format('d/m'),
                'montant' => (int) ($row?->montant ?? 0),
                'nombre'  => (int) ($row?->nombre ?? 0),
            ];
        }
        return $data;
    }

    /**
     * Top établissements par CA mensuel.
     */
    public static function topEtablissementsCA(int $limit = 5): Collection
    {
        $moisActuel = now()->format('Y-m');

        return Paiement::query()
            ->where('statut', 'confirme')
            ->where('date_paiement', 'like', "$moisActuel%")
            ->selectRaw('etablissement_id, SUM(montant) as ca, COUNT(*) as nb')
            ->groupBy('etablissement_id')
            ->orderByDesc('ca')
            ->limit($limit)
            ->with('etablissement:id,nom,ville')
            ->get()
            ->map(fn($row) => [
                'etablissement' => $row->etablissement,
                'ca'            => (int) $row->ca,
                'nb_paiements'  => (int) $row->nb,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function etablissementsAvecStats(): Collection
    {
        return Etablissement::query()
            ->orderBy('nom')
            ->get()
            ->map(fn (Etablissement $etab) => self::statsPourEtablissement($etab));
    }

    /**
     * Détail complet d'un établissement (pour vue admin show).
     * @return array<string, mixed>
     */
    public static function detailEtablissement(Etablissement $etab): array
    {
        $base = self::statsPourEtablissement($etab);

        $annee = $base['annee_courante'];

        // Inscriptions des 30 derniers jours
        $inscriptions30j = $annee
            ? \App\Models\Inscription::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count()
            : 0;

        // Liste des utilisateurs de l'établissement
        $users = User::where('etablissement_id', $etab->id)
            ->where('role', '!=', 'super_admin')
            ->orderBy('role')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->limit(50)
            ->get();

        // Liste des niveaux de l'établissement
        $niveaux = Niveau::where('etablissement_id', $etab->id)
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get();

        // Derniers paiements (10)
        $paiementsRecents = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->with('eleve:id,nom,prenom')
            ->latest('date_paiement')
            ->limit(10)
            ->get();

        // Toutes les années de l'établissement
        $annees = AnneeScolaire::where('etablissement_id', $etab->id)
            ->orderByDesc('date_debut')
            ->get();

        return [
            'etablissement'      => $etab,
            'annee_courante'     => $annee,
            'eleves'             => $base['eleves'],
            'utilisateurs_actifs' => $base['utilisateurs_actifs'],
            'recouvrement'       => $base['recouvrement'],
            'wave_actif'         => $base['wave_actif'],
            'dernier_paiement'   => $base['dernier_paiement'],
            'inscriptions_30j'   => $inscriptions30j,
            'users'              => $users,
            'niveaux'            => $niveaux,
            'paiements_recents'  => $paiementsRecents,
            'annees'             => $annees,
        ];
    }

    public static function statsPourEtablissement(Etablissement $etab): array
    {
        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etab->id)
            ->where('en_cours', true)
            ->where('cloturee', false)
            ->first();

        // Élèves inscrits pour l'année courante (cohérence avec le dashboard école)
        $eleves = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('actif', true)
                ->inscritsCetteAnnee($annee->id)->count()
            : 0;
        $usersActifs = User::where('etablissement_id', $etab->id)->where('actif', true)->where('role', '!=', 'super_admin')->count();

        $recouvrement = $annee
            ? TarificationService::recouvrementEtablissement($etab->id, $annee->id)
            : ['total_du' => 0, 'total_paye' => 0, 'reste' => 0, 'taux' => 0, 'par_statut' => []];

        $dernierPaiement = Paiement::query()
            ->where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->latest('date_paiement')
            ->value('date_paiement');

        return [
            'etablissement'     => $etab,
            'annee_courante'    => $annee,
            'eleves'            => $eleves,
            'utilisateurs_actifs' => $usersActifs,
            'recouvrement'      => $recouvrement,
            'wave_actif'        => (bool) $etab->wave_actif,
            'dernier_paiement'  => $dernierPaiement,
        ];
    }
}
