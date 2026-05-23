<?php

namespace App\Http\Controllers;

use App\Models\CompteComptable;
use App\Models\EcritureComptable;
use App\Models\Etablissement;
use App\Models\ExerciceComptable;
use App\Services\Comptabilite\ComptabilisationService;
use App\Services\Comptabilite\SyscohadaPlanService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ComptabiliteWebController extends Controller
{
    private const DEBIT_NORMAL_TYPES = ['actif', 'charge', 'tresorerie'];

    public function __construct(
        private SyscohadaPlanService $syscohada,
        private ComptabilisationService $comptabilisation
    ) {
    }

    public function index(Request $request): View
    {
        $etab = $this->resolveEtablissement($request);
        $setup = $this->syscohada->ensureBase($etab);
        $comptes = $this->comptes($etab);
        $exercice = $this->exerciceCourant($etab);
        $stats = $this->stats($etab, $comptes, $exercice);
        $classes = $this->classes($comptes);
        $recentes = $this->recentes($etab);
        $paiementsAComptabiliser = $this->comptabilisation->countPaiementsAComptabiliser($etab->id);

        return view('comptabilite.index', compact(
            'etab',
            'setup',
            'comptes',
            'exercice',
            'stats',
            'classes',
            'recentes',
            'paiementsAComptabiliser'
        ));
    }

    public function initialiser(Request $request): RedirectResponse
    {
        $etab = $this->resolveEtablissement($request);
        $setup = $this->syscohada->ensureBase($etab);

        return back()->with(
            'success',
            "Plan SYSCOHADA synchronisé : {$setup['created_accounts']} compte(s) ajouté(s)."
        );
    }

    public function synchroniserPaiements(Request $request): RedirectResponse
    {
        $etab = $this->resolveEtablissement($request);
        $result = $this->comptabilisation->posterPaiementsConfirmes($etab, $request->user()->id);

        $message = "{$result['posted']} écriture(s) générée(s) depuis les paiements confirmés.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} paiement(s) ignoré(s).";
        }

        return back()->with($result['errors'] ? 'error' : 'success', $message);
    }

    public function journal(Request $request): View
    {
        $etab = $this->resolveEtablissement($request);
        $this->syscohada->ensureBase($etab);

        $mois = $this->mois($request);
        $debut = Carbon::parse($mois.'-01')->startOfMonth();
        $fin = (clone $debut)->endOfMonth();

        $ecritures = EcritureComptable::where('etablissement_id', $etab->id)
            ->whereBetween('date_ecriture', [$debut->toDateString(), $fin->toDateString()])
            ->with(['compteDebit:id,numero,libelle', 'compteCredit:id,numero,libelle', 'saisiePar:id,nom,prenom'])
            ->orderBy('date_ecriture')
            ->orderBy('id')
            ->paginate(40)
            ->withQueryString();

        $total = EcritureComptable::where('etablissement_id', $etab->id)
            ->whereBetween('date_ecriture', [$debut->toDateString(), $fin->toDateString()])
            ->sum('montant');

        $paiementsAComptabiliser = $this->comptabilisation->countPaiementsAComptabiliser($etab->id);

        return view('comptabilite.journal', compact(
            'etab',
            'mois',
            'ecritures',
            'total',
            'paiementsAComptabiliser'
        ));
    }

    public function grandLivre(Request $request): View
    {
        $etab = $this->resolveEtablissement($request);
        $this->syscohada->ensureBase($etab);

        $comptes = $this->comptes($etab);
        $compte = $request->filled('compte_id')
            ? $comptes->firstWhere('id', (int) $request->compte_id)
            : $comptes->first();

        $ecritures = collect();
        if ($compte) {
            $solde = (float) $compte->solde_initial;
            $normalDebit = in_array($compte->type, self::DEBIT_NORMAL_TYPES, true);

            $ecritures = EcritureComptable::where('etablissement_id', $etab->id)
                ->where(function ($query) use ($compte) {
                    $query->where('compte_debit_id', $compte->id)
                        ->orWhere('compte_credit_id', $compte->id);
                })
                ->with(['compteDebit:id,numero,libelle', 'compteCredit:id,numero,libelle'])
                ->where('valide', true)
                ->orderBy('date_ecriture')
                ->orderBy('id')
                ->get()
                ->map(function (EcritureComptable $ecriture) use (&$solde, $compte, $normalDebit) {
                    $estDebit = (int) $ecriture->compte_debit_id === (int) $compte->id;
                    $variation = $estDebit
                        ? ($normalDebit ? (float) $ecriture->montant : -1 * (float) $ecriture->montant)
                        : ($normalDebit ? -1 * (float) $ecriture->montant : (float) $ecriture->montant);

                    $solde += $variation;
                    $ecriture->sens_compte = $estDebit ? 'Débit' : 'Crédit';
                    $ecriture->variation_compte = $variation;
                    $ecriture->solde_progressif = $solde;

                    return $ecriture;
                });
        }

        return view('comptabilite.grand-livre', compact('etab', 'comptes', 'compte', 'ecritures'));
    }

    public function bilan(Request $request): View
    {
        $etab = $this->resolveEtablissement($request);
        $this->syscohada->ensureBase($etab);

        $comptes = $this->comptes($etab);
        $actifs = $comptes->filter(fn ($compte) => in_array($compte->type, ['actif', 'tresorerie'], true));
        $passifs = $comptes->where('type', 'passif');
        $produits = (float) $comptes->where('type', 'produit')->sum('solde_actuel');
        $charges = (float) $comptes->where('type', 'charge')->sum('solde_actuel');
        $resultat = $produits - $charges;

        $totalActif = (float) $actifs->sum('solde_actuel') + ($resultat < 0 ? abs($resultat) : 0);
        $totalPassif = (float) $passifs->sum('solde_actuel') + ($resultat > 0 ? $resultat : 0);
        $ecart = $totalActif - $totalPassif;

        return view('comptabilite.bilan', compact(
            'etab',
            'actifs',
            'passifs',
            'resultat',
            'totalActif',
            'totalPassif',
            'ecart'
        ));
    }

    public function resultat(Request $request): View
    {
        $etab = $this->resolveEtablissement($request);
        $this->syscohada->ensureBase($etab);

        $comptes = $this->comptes($etab);
        $produits = $comptes->where('type', 'produit')->values();
        $charges = $comptes->where('type', 'charge')->values();
        $totalProduits = (float) $produits->sum('solde_actuel');
        $totalCharges = (float) $charges->sum('solde_actuel');
        $resultatNet = $totalProduits - $totalCharges;

        return view('comptabilite.resultat', compact(
            'etab',
            'produits',
            'charges',
            'totalProduits',
            'totalCharges',
            'resultatNet'
        ));
    }

    private function resolveEtablissement(Request $request): Etablissement
    {
        $user = $request->user();
        $activeId = method_exists($user, 'ecoleActiveId') ? $user->ecoleActiveId() : null;
        $etablissement = $activeId ? Etablissement::find($activeId) : $user->etablissement;

        abort_unless($etablissement, 403, 'Aucun établissement associé.');

        return $etablissement;
    }

    private function comptes(Etablissement $etab): Collection
    {
        return CompteComptable::where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->orderBy('numero')
            ->get();
    }

    private function exerciceCourant(Etablissement $etab): ?ExerciceComptable
    {
        return ExerciceComptable::where('etablissement_id', $etab->id)
            ->where('en_cours', true)
            ->first();
    }

    private function recentes(Etablissement $etab): Collection
    {
        return EcritureComptable::where('etablissement_id', $etab->id)
            ->with(['compteDebit:id,numero,libelle', 'compteCredit:id,numero,libelle'])
            ->latest('date_ecriture')
            ->latest('id')
            ->take(8)
            ->get();
    }

    private function stats(Etablissement $etab, Collection $comptes, ?ExerciceComptable $exercice): array
    {
        $produits = (float) $comptes->where('type', 'produit')->sum('solde_actuel');
        $charges = (float) $comptes->where('type', 'charge')->sum('solde_actuel');

        return [
            'nb_comptes' => $comptes->count(),
            'nb_ecritures' => EcritureComptable::where('etablissement_id', $etab->id)->count(),
            'tresorerie' => (float) $comptes->where('type', 'tresorerie')->sum('solde_actuel'),
            'creances' => (float) $comptes->where('categorie', 'creances')->sum('solde_actuel'),
            'produits' => $produits,
            'charges' => $charges,
            'resultat' => $produits - $charges,
            'exercice' => $exercice?->libelle ?? 'Non ouvert',
        ];
    }

    private function classes(Collection $comptes): Collection
    {
        return collect(SyscohadaPlanService::CLASSES)
            ->map(function (string $label, string $classe) use ($comptes) {
                $items = $comptes->filter(fn ($compte) => str_starts_with($compte->numero, $classe));

                return [
                    'numero' => $classe,
                    'label' => $label,
                    'count' => $items->count(),
                    'solde' => (float) $items->sum('solde_actuel'),
                    'comptes' => $items->values(),
                ];
            })
            ->values();
    }

    private function mois(Request $request): string
    {
        $mois = (string) $request->query('mois', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $mois) ? $mois : now()->format('Y-m');
    }
}
