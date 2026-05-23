<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\FournitureItem;
use App\Models\ListeFournitures;
use App\Services\Scolarite\AnneeScolaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FournituresController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::courantePourEtablissement($etab->id);

        $listes = $annee
            ? ListeFournitures::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with(['classe', 'creePar:id,nom,prenom'])
                ->withCount('items')
                ->orderBy('classe_id')
                ->get()
            : collect();

        // Classes sans liste
        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->orderBy('nom')->get()
            : collect();

        return view('fournitures.index', compact('listes', 'classes', 'annee'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'titre'     => 'nullable|string|max:200',
            'notes'     => 'nullable|string',
        ]);

        $etab = $request->user()->etablissement;
        $annee = AnneeScolaireService::couranteOuEchec($etab->id);

        $existe = ListeFournitures::where('classe_id', $validated['classe_id'])
            ->where('annee_scolaire_id', $annee->id)->exists();
        abort_if($existe, 422, 'Une liste existe déjà pour cette classe cette année.');

        $liste = ListeFournitures::create([
            'etablissement_id'  => $etab->id,
            'classe_id'         => $validated['classe_id'],
            'annee_scolaire_id' => $annee->id,
            'titre'             => $validated['titre'] ?? 'Liste de fournitures',
            'notes'             => $validated['notes'] ?? null,
            'publie'            => false,
            'cree_par'          => $request->user()->id,
        ]);

        return redirect()->route('fournitures.show', $liste->id)->with('success', 'Liste créée. Ajoutez les fournitures.');
    }

    public function show(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $liste = ListeFournitures::with(['classe', 'items', 'creePar:id,nom,prenom'])
            ->where('etablissement_id', $etab)->findOrFail($id);

        return view('fournitures.show', compact('liste'));
    }

    public function ajouterItem(Request $request, $id)
    {
        $validated = $request->validate([
            'libelle'         => 'required|string|max:200',
            'categorie'       => 'nullable|string|max:60',
            'quantite'        => 'required|integer|min:1|max:1000',
            'unite'           => 'nullable|string|max:20',
            'marque_suggeree' => 'nullable|string|max:100',
            'obligatoire'     => 'nullable|boolean',
            'observations'    => 'nullable|string',
        ]);

        $etab = $request->user()->etablissement_id;
        $liste = ListeFournitures::where('etablissement_id', $etab)->findOrFail($id);

        FournitureItem::create([
            ...$validated,
            'liste_id'    => $liste->id,
            'obligatoire' => ! empty($validated['obligatoire']),
            'ordre'       => FournitureItem::where('liste_id', $liste->id)->max('ordre') + 1,
        ]);

        return back()->with('success', 'Fourniture ajoutée.');
    }

    public function supprimerItem(Request $request, $id, $itemId)
    {
        $etab = $request->user()->etablissement_id;
        $liste = ListeFournitures::where('etablissement_id', $etab)->findOrFail($id);
        FournitureItem::where('liste_id', $liste->id)->findOrFail($itemId)->delete();
        return back()->with('success', 'Fourniture supprimée.');
    }

    public function publier(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $liste = ListeFournitures::where('etablissement_id', $etab)->findOrFail($id);
        $liste->update(['publie' => ! $liste->publie]);
        return back()->with('success', $liste->publie ? 'Liste publiée — visible par les parents.' : 'Liste masquée.');
    }

    public function destroy(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $liste = ListeFournitures::where('etablissement_id', $etab)->findOrFail($id);
        $liste->delete();
        return redirect()->route('fournitures.index')->with('success', 'Liste supprimée.');
    }

    public function pdf(Request $request, $id)
    {
        $etab = $request->user()->etablissement;
        $liste = ListeFournitures::with(['classe.niveau', 'items', 'anneeScolaire'])
            ->where('etablissement_id', $etab->id)->findOrFail($id);

        $pdf = Pdf::loadView('fournitures.pdf', compact('liste', 'etab'))->setPaper('a4', 'portrait');
        $nom = 'fournitures-'.$liste->classe?->nom.'-'.$liste->anneeScolaire?->libelle.'.pdf';
        return $request->boolean('download') ? $pdf->download($nom) : $pdf->stream($nom);
    }
}
