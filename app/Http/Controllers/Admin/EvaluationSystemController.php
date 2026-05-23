<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Trimestre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationSystemController extends Controller
{
    public function index(Request $request)
    {
        $etab = Etablissement::findOrFail($request->user()->etablissement_id);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        return view('admin.rh.evaluation-system.index', compact('etab', 'annee', 'trimestres'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'systeme_evaluation' => 'required|in:trimestre,semestre,quadrimestre',
            'regenerer_periodes' => 'nullable|boolean',
        ]);

        $etab = Etablissement::findOrFail($request->user()->etablissement_id);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

        DB::transaction(function () use ($etab, $annee, $data) {
            $etab->update(['systeme_evaluation' => $data['systeme_evaluation']]);

            if ($annee && !empty($data['regenerer_periodes'])) {
                $this->regenererPeriodes($annee, $etab);
            }
        });

        return back()->with('success', 'Système d\'évaluation mis à jour.');
    }

    /**
     * Met à jour les coefficients des trimestres (système ivoirien : T1=1, T2=2, T3=2).
     */
    public function updateCoefs(Request $request)
    {
        $data = $request->validate([
            'coefs'      => 'required|array',
            'coefs.*'    => 'required|numeric|min:0.1|max:10',
        ]);

        $etabId = $request->user()->etablissement_id;

        foreach ($data['coefs'] as $trimId => $coef) {
            Trimestre::whereHas('anneeScolaire', fn ($q) => $q->where('etablissement_id', $etabId))
                ->where('id', (int) $trimId)
                ->update(['coefficient' => (float) $coef]);
        }

        return back()->with('success', 'Coefficients des périodes mis à jour.');
    }

    public function regenerer(Request $request)
    {
        $etab = Etablissement::findOrFail($request->user()->etablissement_id);
        $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_if(!$annee, 422, 'Aucune année scolaire en cours.');

        $this->regenererPeriodes($annee, $etab);

        return back()->with('success', 'Périodes régénérées : ' . $etab->nbPeriodes() . ' ' . $etab->systeme_evaluation . '(s).');
    }

    /**
     * (Re)génère les périodes (trimestres/semestres/quadrimestres) selon le système
     * configuré sur l'établissement. Conserve les périodes existantes si on garde
     * le même système, sinon supprime et recrée.
     */
    private function regenererPeriodes(AnneeScolaire $annee, Etablissement $etab): void
    {
        $nb = $etab->nbPeriodes();
        $debut = Carbon::parse($annee->date_debut);
        $fin   = Carbon::parse($annee->date_fin);

        $totalJours = $debut->diffInDays($fin);
        $joursParPeriode = (int) floor($totalJours / $nb);

        // Supprimer les périodes existantes (cascade vers évaluations etc.
        // — utilisateur a été averti dans l'UI)
        Trimestre::where('annee_scolaire_id', $annee->id)->delete();

        for ($i = 1; $i <= $nb; $i++) {
            $start = $debut->copy()->addDays(($i - 1) * $joursParPeriode);
            $end = $i === $nb ? $fin : $debut->copy()->addDays($i * $joursParPeriode - 1);

            Trimestre::create([
                'annee_scolaire_id' => $annee->id,
                'numero'            => $i,
                'libelle'           => $etab->labelPeriode($i),
                'date_debut'        => $start->toDateString(),
                'date_fin'          => $end->toDateString(),
                'en_cours'          => today()->between($start, $end),
            ]);
        }
    }
}
