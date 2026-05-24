<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceImpayesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $etabId = (int) $user->ecoleActiveId();
        abort_unless($etabId, 403, 'Aucun établissement associé.');

        $annee = AnneeScolaireContext::courantePourEtablissement($etabId);
        abort_unless($annee, 403, 'Aucune année scolaire en cours.');

        $filters = [
            'poste' => $request->input('poste', 'tous'),
            'niveau_id' => $request->input('niveau_id'),
            'classe_id' => $request->input('classe_id'),
            'q' => trim((string) $request->input('q', '')),
        ];

        $niveaux = Niveau::where('etablissement_id', $etabId)->orderBy('ordre')->get();
        $classes = Classe::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau')
            ->orderBy('niveau_id')
            ->orderBy('nom')
            ->get();

        $paiements = DB::table('paiements')
            ->selectRaw('inscription_id,
                COALESCE(SUM(montant), 0) as total_paye,
                COALESCE(SUM(montant_inscription), 0) as inscription_payee,
                COALESCE(SUM(montant_scolarite), 0) as scolarite_payee')
            ->where('statut', 'confirme')
            ->groupBy('inscription_id');

        $query = Inscription::query()
            ->where('inscriptions.etablissement_id', $etabId)
            ->where('inscriptions.annee_scolaire_id', $annee->id)
            ->where('inscriptions.statut', 'validee')
            ->leftJoinSub($paiements, 'pay', fn ($join) => $join->on('pay.inscription_id', '=', 'inscriptions.id'))
            ->with(['eleve:id,nom,prenom,matricule_interne,telephone_parent', 'classe.niveau'])
            ->select('inscriptions.*')
            ->selectRaw('COALESCE(pay.total_paye, 0) as total_paye_calc')
            ->selectRaw('COALESCE(pay.inscription_payee, 0) as inscription_payee_calc')
            ->selectRaw('COALESCE(pay.scolarite_payee, 0) as scolarite_payee_calc');

        if ($filters['niveau_id']) {
            $query->whereHas('classe', fn ($q) => $q->where('niveau_id', (int) $filters['niveau_id']));
        }

        if ($filters['classe_id']) {
            $query->where('classe_id', (int) $filters['classe_id']);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->whereHas('eleve', function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('prenom', 'like', "%{$q}%")
                    ->orWhere('matricule_interne', 'like', "%{$q}%");
            });
        }

        $impayes = $query->get()->map(function (Inscription $inscription) {
            $montantInscription = (int) $inscription->montant_inscription;
            $montantScolarite = (int) $inscription->montant_scolarite;
            $totalDu = (int) $inscription->montant_net;

            $paidInscription = (int) $inscription->inscription_payee_calc;
            $paidScolarite = (int) $inscription->scolarite_payee_calc;
            $totalPaye = (int) $inscription->total_paye_calc;

            if ($paidInscription === 0 && $paidScolarite === 0 && $totalPaye > 0) {
                $paidInscription = min($montantInscription, $totalPaye);
                $paidScolarite = max(0, $totalPaye - $paidInscription);
            }

            $resteInscription = max(0, $montantInscription - $paidInscription);
            $resteScolarite = max(0, $montantScolarite - $paidScolarite);
            $resteTotal = max(0, $totalDu - $totalPaye);

            $inscription->reste_inscription_calc = $resteInscription;
            $inscription->reste_scolarite_calc = $resteScolarite;
            $inscription->reste_total_calc = $resteTotal;
            $inscription->total_paye_calc = $totalPaye;

            return $inscription;
        })->filter(function ($inscription) use ($filters) {
            return match ($filters['poste']) {
                'inscription' => $inscription->reste_inscription_calc > 0,
                'scolarite' => $inscription->reste_scolarite_calc > 0,
                default => $inscription->reste_total_calc > 0,
            };
        })->sortBy([
            fn ($a, $b) => ($a->classe?->niveau?->ordre ?? 999) <=> ($b->classe?->niveau?->ordre ?? 999),
            fn ($a, $b) => strcmp($a->classe?->nom ?? '', $b->classe?->nom ?? ''),
            fn ($a, $b) => strcmp($a->eleve?->nom ?? '', $b->eleve?->nom ?? ''),
        ])->values();

        $totaux = [
            'inscription' => $impayes->sum('reste_inscription_calc'),
            'scolarite' => $impayes->sum('reste_scolarite_calc'),
            'total' => $impayes->sum('reste_total_calc'),
            'effectif' => $impayes->count(),
        ];

        $totauxParNiveau = $impayes->groupBy(fn ($i) => $i->classe?->niveau?->libelle ?? 'Sans niveau')
            ->map(fn ($rows) => [
                'effectif' => $rows->count(),
                'inscription' => $rows->sum('reste_inscription_calc'),
                'scolarite' => $rows->sum('reste_scolarite_calc'),
                'total' => $rows->sum('reste_total_calc'),
            ]);

        $totauxParClasse = $impayes->groupBy(fn ($i) => $i->classe?->nom ?? 'Sans classe')
            ->map(fn ($rows) => [
                'niveau' => $rows->first()?->classe?->niveau?->libelle ?? '—',
                'effectif' => $rows->count(),
                'inscription' => $rows->sum('reste_inscription_calc'),
                'scolarite' => $rows->sum('reste_scolarite_calc'),
                'total' => $rows->sum('reste_total_calc'),
            ]);

        $printMode = $request->boolean('print');

        return view('finances.impayes', compact(
            'annee', 'niveaux', 'classes', 'impayes', 'totaux',
            'totauxParNiveau', 'totauxParClasse', 'filters', 'printMode'
        ));
    }
}
