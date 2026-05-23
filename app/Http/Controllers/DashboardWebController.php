<?php

namespace App\Http\Controllers;

use App\Models\{Etablissement, Inscription, Paiement, Pointage, Enseignant, Eleve, MoyenneGenerale, Trimestre, Classe, AlertePointage, RemonteeSigfne};
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardWebController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isSuperAdmin() && ! session('super_admin_impersonate_etab_id')) {
            return redirect()->route('admin.platform.dashboard');
        }

        $etabId = $request->user()->ecoleActiveId();
        $etab = $etabId ? \App\Models\Etablissement::find($etabId) : $request->user()->etablissement;
        abort_unless($etab, 403, 'Aucun établissement associé.');
        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $trimestre = $annee ? Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first() : null;

        // ── Stats principales — filtrage strict année courante ──
        $totalEleves = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('actif', true)->pourAnneeScolaire($annee)->count()
            : 0;
        // Enseignants : ceux ACTIFS de l'école (le personnel persiste entre années,
        // les affectations classes/matières sont mises à jour chaque année).
        $totalEnseignants = Enseignant::where('etablissement_id', $etab->id)->where('actif', true)->count();
        $totalClasses = $annee ? Classe::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->count() : 0;

        // Répartition sexe — uniquement inscrits cette année
        $elevesF = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('actif', true)->pourAnneeScolaire($annee)->where('sexe', 'F')->count()
            : 0;
        $elevesM = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('actif', true)->pourAnneeScolaire($annee)->where('sexe', 'M')->count()
            : 0;

        // ── Pointage du jour ──
        $pointages_jour = Pointage::where('etablissement_id', $etab->id)
            ->where('date', today())->where('type_scan', 'arrivee')
            ->with(['enseignant:id,nom,prenom,photo_path,specialite,telephone', 'salle:id,nom'])
            ->orderBy('heure_scan')->get();

        $presentsCount = $pointages_jour->whereIn('statut', ['present', 'retard'])->unique('enseignant_id')->count();
        $retardsCount = $pointages_jour->where('statut', 'retard')->unique('enseignant_id')->count();

        $enseignantsPointes = $pointages_jour->pluck('enseignant_id');
        $absents = Enseignant::where('etablissement_id', $etab->id)->where('actif', true)
            ->whereNotIn('id', $enseignantsPointes)
            ->select('id', 'nom', 'prenom', 'photo_path', 'telephone', 'specialite')->get();

        // ── Finances ──
        $totalAttendu = $annee ? Inscription::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->sum('montant_net') : 0;
        $totalPaye = $annee ? Paiement::where('etablissement_id', $etab->id)->where('statut', 'confirme')
            ->whereHas('inscription', fn($q) => $q->where('annee_scolaire_id', $annee->id))->sum('montant') : 0;
        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 1) : 0;

        // Derniers paiements
        $derniersPaiements = Paiement::where('etablissement_id', $etab->id)->where('statut', 'confirme')
            ->with('eleve:id,nom,prenom,matricule_interne')
            ->latest('date_paiement')->take(6)->get();

        // Paiements par mode (pour graphique)
        $paiementsParMode = Paiement::where('etablissement_id', $etab->id)->where('statut', 'confirme')
            ->select('mode', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as nombre'))
            ->groupBy('mode')->get();

        // Paiements des 6 derniers mois
        $paiementsMensuels = Paiement::where('etablissement_id', $etab->id)->where('statut', 'confirme')
            ->where('date_paiement', '>=', now()->subMonths(6))
            ->select(DB::raw('DATE_FORMAT(date_paiement, "%Y-%m") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')->orderBy('mois')->get();

        // ── Pédagogie ──
        $moyenneGenerale = $trimestre ? MoyenneGenerale::whereHas('classe', fn($q) => $q->where('etablissement_id', $etab->id))
            ->where('trimestre_id', $trimestre->id)->avg('moyenne_generale') : null;

        // Top 5 élèves
        $topEleves = $trimestre ? MoyenneGenerale::where('trimestre_id', $trimestre->id)
            ->whereHas('classe', fn($q) => $q->where('etablissement_id', $etab->id))
            ->with(['eleve:id,nom,prenom,matricule_interne', 'classe:id,nom'])
            ->orderByDesc('moyenne_generale')->take(5)->get() : collect();

        // Élèves en difficulté (moyenne < 10)
        $elevesEnDifficulte = $trimestre ? MoyenneGenerale::where('trimestre_id', $trimestre->id)
            ->whereHas('classe', fn($q) => $q->where('etablissement_id', $etab->id))
            ->where('moyenne_generale', '<', 10)
            ->with(['eleve:id,nom,prenom', 'classe:id,nom'])->count() : 0;

        // ── Alertes ──
        $alertesNonLues = AlertePointage::where('etablissement_id', $etab->id)->where('traitee', false)->count();

        // ── SIGFNE ──
        $trimestres = $annee ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get() : collect();

        // ── Activité récente ──
        $activiteRecente = collect();
        // Dernières inscriptions
        $dernieresInscriptions = $annee ? Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with('eleve:id,nom,prenom')->latest()->take(3)->get() : collect();

        // ── Assembler les stats ──
        $stats = [
            'eleves_inscrits' => $totalEleves,
            'eleves_f' => $elevesF,
            'eleves_m' => $elevesM,
            'enseignants_total' => $totalEnseignants,
            'enseignants_presents' => $presentsCount,
            'enseignants_absents' => $totalEnseignants - $presentsCount,
            'enseignants_retards' => $retardsCount,
            'total_classes' => $totalClasses,
            'taux_recouvrement' => $tauxRecouvrement,
            'total_paye_fcfa' => $totalPaye,
            'total_attendu_fcfa' => $totalAttendu,
            'reste_fcfa' => $totalAttendu - $totalPaye,
            'moyenne_generale' => $moyenneGenerale ? round($moyenneGenerale, 2) : null,
            'eleves_en_difficulte' => $elevesEnDifficulte,
            'alertes_non_lues' => $alertesNonLues,
            'taux_presence' => $totalEnseignants > 0 ? round(($presentsCount / $totalEnseignants) * 100, 1) : 0,
        ];

        $graphiques = [
            'paiements_par_mode' => $paiementsParMode,
            'paiements_mensuels' => $paiementsMensuels,
        ];

        return view('dashboard.index', compact(
            'stats', 'graphiques', 'pointages_jour', 'absents',
            'derniersPaiements', 'topEleves', 'trimestres',
            'dernieresInscriptions', 'etab', 'annee', 'trimestre'
        ));
    }
}