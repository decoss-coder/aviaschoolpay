<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\MoyenneMatiere;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Grille admin lecture seule : affiche toutes les moyennes publiées
 * par les enseignants, organisées par matière × trimestre pour une classe.
 */
class MoyennesGrilleAdminController extends Controller
{
    private function etabId(Request $request): int
    {
        return (int) $request->user()->etablissement_id;
    }

    public function index(Request $request)
    {
        $etabId = $this->etabId($request);
        $annee  = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);

        $classes = Classe::where('etablissement_id', $etabId)->where('active', true)
            ->when($annee, fn ($q) => $q->where('annee_scolaire_id', $annee->id))
            ->orderBy('nom')->get();

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $classeId = (int) $request->input('classe_id', $classes->first()?->id);
        $classe   = $classes->firstWhere('id', $classeId);

        // Detect premier cycle
        $classe?->load('niveau');
        $estPremierCycle = $classe && strtolower($classe->niveau?->cycle ?? '') === 'premier';

        $eleves = $classe ? Eleve::where('classe_id', $classe->id)->where('actif', true)
            ->when($annee, fn ($q) => $q->inscritsCetteAnnee($annee->id))
            ->orderBy('nom')->orderBy('prenom')->get() : collect();

        // Matières affectées (top-level only), with sous-disciplines eager-loaded
        $affectationIds = $classe
            ? DB::table('affectations')
                ->where('classe_id', $classe->id)
                ->where('active', true)
                ->pluck('matiere_id')
            : collect();

        $matieres = $classe
            ? Matiere::whereIn('id', $affectationIds)
                ->whereNull('parent_matiere_id')
                ->with('sousDisciplines')
                ->orderBy('nom')
                ->get()
            : collect();

        // All moyennes for this class (includes sous-discipline IDs if they exist as MoyenneMatiere entries)
        $moyennes = $classe && $trimestres->isNotEmpty()
            ? MoyenneMatiere::where('classe_id', $classe->id)
                ->whereIn('trimestre_id', $trimestres->pluck('id'))
                ->where('publie', true)
                ->get()
                ->groupBy(fn ($m) => $m->matiere_id . '_' . $m->trimestre_id)
                ->map(fn ($g) => $g->keyBy('eleve_id'))
            : collect();

        return view('admin.rh.moyennes-grille.index',
            compact('classes', 'classe', 'classeId', 'trimestres', 'eleves', 'matieres', 'moyennes', 'annee', 'estPremierCycle'));
    }
}
