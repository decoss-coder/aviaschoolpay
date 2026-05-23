<?php

namespace App\Http\Controllers;

use App\Models\EvenementScolaire;
use App\Services\Scolarite\AnneeScolaireService;
use Illuminate\Http\Request;

class EvenementScolaireController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::courantePourEtablissement($etab->id);

        $evenements = $annee
            ? EvenementScolaire::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('creePar:id,nom,prenom')
                ->orderBy('date_debut')->get()
            : collect();

        return view('evenements.index', compact('evenements', 'annee'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre'         => 'required|string|max:200',
            'type'          => 'required|in:rentree,vacances,examen,conseil_classe,reunion_parents,fete,sortie,ferie,autre',
            'date_debut'    => 'required|date',
            'date_fin'      => 'nullable|date|after_or_equal:date_debut',
            'description'   => 'nullable|string',
            'lieu'          => 'nullable|string|max:200',
            'couleur'       => 'nullable|string|max:7',
            'toute_journee' => 'nullable|boolean',
            'heure_debut'   => 'nullable',
            'heure_fin'     => 'nullable',
            'publie'        => 'nullable|boolean',
        ]);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);

        EvenementScolaire::create([
            ...$validated,
            'etablissement_id'   => $etab->id,
            'annee_scolaire_id'  => $annee->id,
            'toute_journee'      => ! empty($validated['toute_journee']),
            'publie'             => ! empty($validated['publie']),
            'cree_par'           => $request->user()->id,
        ]);

        return back()->with('success', 'Événement ajouté au calendrier.');
    }

    public function publier(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $ev = EvenementScolaire::where('etablissement_id', $etab)->findOrFail($id);
        $ev->update(['publie' => ! $ev->publie]);
        return back()->with('success', $ev->publie ? 'Événement publié.' : 'Événement masqué.');
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $ev = EvenementScolaire::where('etablissement_id', $etab)->findOrFail($id);
        $ev->delete();
        return back()->with('success', 'Événement supprimé.');
    }
}
