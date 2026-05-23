<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ConseilClasse;
use App\Models\Trimestre;
use App\Services\Scolarite\AnneeScolaireService;
use Illuminate\Http\Request;

class ConseilClasseController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::courantePourEtablissement($etab->id);

        $conseils = ConseilClasse::where('etablissement_id', $etab->id)
            ->with(['classe', 'trimestre', 'creePar:id,nom,prenom'])
            ->orderByDesc('date_conseil')
            ->paginate(20);

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get()
            : collect();

        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();

        return view('conseils-classe.index', compact('conseils', 'classes', 'trimestres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'classe_id'     => 'required|exists:classes,id',
            'trimestre_id'  => 'required|exists:trimestres,id',
            'date_conseil'  => 'required|date',
            'heure_debut'   => 'required',
            'heure_fin'     => 'nullable',
            'lieu'          => 'required|string|max:200',
            'ordre_du_jour' => 'required|string',
            'participants'  => 'nullable|string',
        ]);

        ConseilClasse::create([
            ...$validated,
            'etablissement_id' => $request->user()->etablissement_id,
            'statut'           => 'planifie',
            'cree_par'         => $request->user()->id,
        ]);

        return back()->with('success', 'Conseil de classe planifié.');
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $conseil = ConseilClasse::where('etablissement_id', $etab)->findOrFail($id);
        $conseil->delete();
        return back()->with('success', 'Conseil supprimé.');
    }
}
