<?php

namespace App\Http\Controllers;

use App\Models\AlerteFinanciere;
use App\Models\CompteTresorerie;
use App\Models\Depense;
use App\Models\Inscription;
use App\Models\LigneBudgetaire;
use App\Models\ScoreFinancier;
use App\Services\Rentabilite\RentabiliteService;
use Illuminate\Http\Request;

class IAWebController extends Controller
{
    public function __construct(private RentabiliteService $rentabilite) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $id = $etab->id;

        $synth = $this->rentabilite->syntheseGlobale($etab);
        $score = ScoreFinancier::where('etablissement_id', $id)->latest('date_calcul')->first();

        $tresoTotale = (int) CompteTresorerie::where('etablissement_id', $id)->where('actif', true)->sum('solde_actuel');
        $depMois     = (int) Depense::where('etablissement_id', $id)->approuvees()->mois(now()->format('Y-m'))->sum('montant');
        $fondsRoulement = $depMois > 0 ? round($tresoTotale / $depMois, 1) : null;

        // Impayés calculés via sous-requête sur paiements confirmés
        $sqlPaiementsConfirmes = 'COALESCE((SELECT SUM(montant) FROM paiements WHERE paiements.inscription_id = inscriptions.id AND paiements.statut = ?), 0)';

        $impayesTotal = (int) Inscription::where('etablissement_id', $id)
            ->where('statut', 'validee')
            ->selectRaw("SUM(montant_net - $sqlPaiementsConfirmes) as total_reste", ['confirme'])
            ->value('total_reste') ?? 0;
        $impayesTotal = max(0, $impayesTotal);

        $nbImpayes = Inscription::where('etablissement_id', $id)
            ->where('statut', 'validee')
            ->whereRaw("montant_net > $sqlPaiementsConfirmes", ['confirme'])
            ->count();

        $depassements = LigneBudgetaire::whereHas('budget', fn($q) => $q->where('etablissement_id', $id)->whereIn('statut', ['valide', 'en_cours']))
            ->where('alerte_depassement', true)
            ->count();

        $diagnostics     = $this->genererDiagnostics($synth, $tresoTotale, $fondsRoulement, $impayesTotal, $nbImpayes, $depassements);
        $recommandations = $this->genererRecommandations($synth, $tresoTotale, $fondsRoulement, $impayesTotal, $depassements);
        $projections     = $this->genererProjections($synth);

        $alertes = AlerteFinanciere::where('etablissement_id', $id)
            ->where('traitee', false)
            ->orderByRaw("FIELD(gravite, 'critique', 'warning', 'info')")
            ->latest()->take(5)->get();

        return view('ia.index', compact(
            'etab', 'synth', 'score', 'tresoTotale', 'fondsRoulement',
            'impayesTotal', 'nbImpayes', 'depassements', 'diagnostics',
            'recommandations', 'projections', 'alertes'
        ));
    }

    private function genererDiagnostics(array $synth, int $treso, ?float $fonds, int $impayes, int $nbImpayes, int $depassements): array
    {
        $d = [];

        if ($synth['rentable']) {
            $d[] = ['niveau' => 'positif', 'titre' => 'Établissement rentable',
                    'message' => "Taux de marge de {$synth['taux_marge']}% — situation saine."];
        } else {
            $d[] = ['niveau' => 'critique', 'titre' => 'Déficit structurel',
                    'message' => "Les dépenses dépassent les revenus. Action immédiate requise."];
        }

        if ($fonds !== null) {
            if ($fonds >= 3) {
                $d[] = ['niveau' => 'positif', 'titre' => 'Trésorerie confortable',
                        'message' => "Fonds de roulement de {$fonds} mois — bonne réserve."];
            } elseif ($fonds >= 1.5) {
                $d[] = ['niveau' => 'warning', 'titre' => 'Trésorerie correcte',
                        'message' => "Fonds de roulement de {$fonds} mois — surveiller les sorties."];
            } else {
                $d[] = ['niveau' => 'critique', 'titre' => 'Trésorerie tendue',
                        'message' => "Fonds de roulement faible ({$fonds} mois). Risque de tension de liquidité."];
            }
        }

        if ($synth['ratio_ms_revenus'] > 0) {
            if ($synth['ms_saine']) {
                $d[] = ['niveau' => 'positif', 'titre' => 'Masse salariale maîtrisée',
                        'message' => "Ratio MS/CA de {$synth['ratio_ms_revenus']}% (cible ≤ 65%)."];
            } else {
                $d[] = ['niveau' => 'warning', 'titre' => 'Masse salariale élevée',
                        'message' => "Ratio MS/CA de {$synth['ratio_ms_revenus']}% — au-dessus du seuil 65% recommandé."];
            }
        }

        if ($nbImpayes > 0) {
            $niveau = $nbImpayes > 10 ? 'warning' : 'info';
            $d[] = ['niveau' => $niveau, 'titre' => "$nbImpayes impayé(s)",
                    'message' => "Encours impayés : ".number_format($impayes, 0, ',', ' ')." F."];
        }

        if ($depassements > 0) {
            $d[] = ['niveau' => 'warning', 'titre' => "$depassements ligne(s) budgétaire(s) en dépassement",
                    'message' => "Vérifiez les lignes proches ou au-dessus du seuil d'alerte."];
        }

        return $d;
    }

    private function genererRecommandations(array $synth, int $treso, ?float $fonds, int $impayes, int $depassements): array
    {
        $r = [];

        if (! $synth['rentable']) {
            $r[] = ['priorite' => 'haute', 'icon' => '⚡',
                    'titre' => 'Augmenter les revenus ou réduire les coûts',
                    'action' => "Marge négative — simuler une hausse tarifaire ou réduction de coûts via Simulations."];
        }

        if ($fonds !== null && $fonds < 2) {
            $r[] = ['priorite' => 'haute', 'icon' => '💧',
                    'titre' => 'Renforcer la trésorerie',
                    'action' => "Accélérer les encaissements (relances impayés) et reporter les dépenses non essentielles."];
        }

        if (! $synth['ms_saine']) {
            $r[] = ['priorite' => 'moyenne', 'icon' => '👥',
                    'titre' => 'Réviser la masse salariale',
                    'action' => "Ratio MS/CA trop élevé. Étudier le ratio enseignants/élèves ou augmenter le CA."];
        }

        if ($impayes > 1000000) {
            $r[] = ['priorite' => 'haute', 'icon' => '📣',
                    'titre' => 'Campagne de relance impayés',
                    'action' => "Encours impayés de ".number_format($impayes, 0, ',', ' ')." F. Lancer une campagne SMS+notification ciblée."];
        }

        if ($depassements > 0) {
            $r[] = ['priorite' => 'moyenne', 'icon' => '📊',
                    'titre' => 'Réviser les lignes budgétaires en dépassement',
                    'action' => "$depassements ligne(s) à examiner dans la vue Budgets."];
        }

        if (empty($r)) {
            $r[] = ['priorite' => 'info', 'icon' => '✓',
                    'titre' => 'Situation financière saine',
                    'action' => "Continuez le suivi régulier. Pensez à investir le surplus pour augmenter la capacité."];
        }

        return $r;
    }

    private function genererProjections(array $synth): array
    {
        $projections = [];
        $tendanceMarge = $synth['marge'] / 12;

        for ($i = 1; $i <= 6; $i++) {
            $date = now()->addMonths($i);
            $projections[] = [
                'mois'          => $date->translatedFormat('M Y'),
                'revenus'       => (int) ($synth['revenus'] / 12),
                'depenses'      => (int) ($synth['depenses'] / 12),
                'marge'         => (int) $tendanceMarge,
                'treso_projete' => (int) ($tendanceMarge * $i),
            ];
        }

        return $projections;
    }
}
