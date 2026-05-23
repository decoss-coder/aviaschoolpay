<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyseRentabilite;
use App\Models\Classe;
use App\Models\Depense;
use App\Models\Enseignant;
use App\Models\Inscription;
use App\Models\ExerciceComptable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentabiliteController extends Controller
{
    public function parEleve(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $request->user()->etablissement_id);

        if (! $annee) {
            return response()->json(['error' => "Pas d'année scolaire en cours"], 404);
        }

        $totalInscriptions = Inscription::where('etablissement_id', $etab)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->count();

        $totalRevenus = Inscription::where('etablissement_id', $etab)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->sum('montant_net');

        $totalDepenses = $exercice
            ? Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant')
            : 0;

        $coutParEleve   = $totalInscriptions > 0 ? round($totalDepenses / $totalInscriptions) : 0;
        $revenuParEleve = $totalInscriptions > 0 ? round($totalRevenus  / $totalInscriptions) : 0;
        $margeParEleve  = $revenuParEleve - $coutParEleve;

        return response()->json([
            'nb_eleves'       => $totalInscriptions,
            'revenu_par_eleve' => $revenuParEleve,
            'cout_par_eleve'  => $coutParEleve,
            'marge_par_eleve' => $margeParEleve,
            'rentable'        => $margeParEleve > 0,
        ]);
    }

    public function seuilRentabilite(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $request->user()->etablissement_id);

        $chargesFixes    = $exercice ? Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant') : 0;
        $nbEleves        = $annee
            ? Inscription::where('etablissement_id', $etab)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->count()
            : 0;
        $revenuMoyenEleve = ($nbEleves > 0 && $annee)
            ? round(Inscription::where('etablissement_id', $etab)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->sum('montant_net') / $nbEleves)
            : 0;

        $seuilEleves = $revenuMoyenEleve > 0 ? (int) ceil($chargesFixes / $revenuMoyenEleve) : null;

        return response()->json([
            'charges_fixes_totales' => $chargesFixes,
            'revenu_moyen_par_eleve' => $revenuMoyenEleve,
            'seuil_nb_eleves'       => $seuilEleves,
            'nb_eleves_actuel'      => $nbEleves,
            'couverture_pourcent'   => ($seuilEleves && $seuilEleves > 0) ? round(($nbEleves / $seuilEleves) * 100, 1) : null,
            'atteint'               => $seuilEleves !== null && $nbEleves >= $seuilEleves,
        ]);
    }

    public function centresProfit(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();

        $analyses = AnalyseRentabilite::where('etablissement_id', $etab)
            ->where('exercice_id', $exercice?->id)
            ->where('niveau_analyse', 'centre_profit')
            ->orderByDesc('taux_marge')
            ->get();

        return response()->json([
            'centres'    => $analyses,
            'rentables'  => $analyses->where('rentable', true)->count(),
            'deficitaires' => $analyses->where('rentable', false)->count(),
            'total'      => $analyses->count(),
        ]);
    }

    public function coutsParClasse(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();

        $analyses = AnalyseRentabilite::where('etablissement_id', $etab)
            ->where('exercice_id', $exercice?->id)
            ->where('niveau_analyse', 'classe')
            ->orderByDesc('total_charges')
            ->get(['entite_nom', 'total_charges', 'total_revenus', 'taux_marge', 'nb_eleves', 'cout_par_eleve']);

        return response()->json(['classes' => $analyses]);
    }

    public function coutsParEnseignant(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $enseignants = Enseignant::where('etablissement_id', $etab)
            ->where('actif', true)
            ->select('id', 'nom', 'prenom', 'salaire_base', 'type_contrat')
            ->get()
            ->map(function ($e) {
                return [
                    'nom'          => $e->prenom.' '.$e->nom,
                    'type_contrat' => $e->type_contrat,
                    'cout_mensuel' => $e->salaire_base,
                    'cout_annuel'  => $e->salaire_base * 12,
                ];
            });

        $totalMasseSalariale = $enseignants->sum('cout_mensuel');

        return response()->json([
            'enseignants'          => $enseignants,
            'total_masse_salariale' => $totalMasseSalariale,
            'nb_enseignants'       => $enseignants->count(),
        ]);
    }

    public function analyseMarge(Request $request): JsonResponse
    {
        $etab     = $request->user()->etablissement_id;
        $exercice = ExerciceComptable::where('etablissement_id', $etab)->enCours()->first();
        $annee    = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $request->user()->etablissement_id);

        $revenus  = $annee
            ? Inscription::where('etablissement_id', $etab)->where('annee_scolaire_id', $annee->id)->where('statut', 'validee')->sum('montant_net')
            : 0;
        $charges  = $exercice ? Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant') : 0;
        $marge    = $revenus - $charges;
        $tauxMarge = $revenus > 0 ? round(($marge / $revenus) * 100, 2) : 0;

        return response()->json([
            'revenus_totaux'  => $revenus,
            'charges_totales' => $charges,
            'marge_brute'     => $marge,
            'taux_marge'      => $tauxMarge,
            'beneficiaire'    => $marge > 0,
            'exercice'        => $exercice?->libelle,
        ]);
    }
}
