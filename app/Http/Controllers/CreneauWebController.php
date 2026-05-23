<?php

namespace App\Http\Controllers;

use App\Models\Creneau;
use Illuminate\Http\Request;

class CreneauWebController extends Controller
{
    public function index(Request $request)
    {
        $etab     = $request->user()->etablissement;
        $creneaux = Creneau::where('etablissement_id', $etab->id)
            ->orderBy('ordre')
            ->get();

        return view('emploi-du-temps.creneaux.index', compact('creneaux'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle'    => 'required|string|max:50',
            'heure_debut'=> 'required|date_format:H:i',
            'heure_fin'  => 'required|date_format:H:i|after:heure_debut',
            'type'       => 'required|in:cours,recreation,pause_dejeuner',
        ]);

        $etabId   = $request->user()->etablissement_id;
        $maxOrdre = Creneau::where('etablissement_id', $etabId)->max('ordre') ?? 0;

        Creneau::create($data + [
            'etablissement_id' => $etabId,
            'ordre'            => $maxOrdre + 1,
        ]);

        return back()->with('success', 'Créneau ajouté.');
    }

    public function update(Request $request, Creneau $creneau)
    {
        $this->authorizeEtab($request, $creneau);

        $data = $request->validate([
            'libelle'    => 'required|string|max:50',
            'heure_debut'=> 'required|date_format:H:i',
            'heure_fin'  => 'required|date_format:H:i|after:heure_debut',
            'type'       => 'required|in:cours,recreation,pause_dejeuner',
        ]);

        $creneau->update($data);

        return back()->with('success', 'Créneau mis à jour.');
    }

    public function destroy(Request $request, Creneau $creneau)
    {
        $this->authorizeEtab($request, $creneau);

        if ($creneau->emploisDuTemps()->exists()) {
            return back()->with('error', 'Ce créneau est utilisé dans des emplois du temps.');
        }

        // Compacter les ordres
        $creneau->delete();
        Creneau::where('etablissement_id', $request->user()->etablissement_id)
            ->orderBy('ordre')
            ->get()
            ->each(fn ($c, $i) => $c->update(['ordre' => $i + 1]));

        return back()->with('success', 'Créneau supprimé.');
    }

    public function reorder(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $ids    = $request->validate(['ids' => 'required|array'])['ids'];

        foreach ($ids as $i => $id) {
            Creneau::where('id', $id)->where('etablissement_id', $etabId)
                ->update(['ordre' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeEtab(Request $request, Creneau $creneau): void
    {
        abort_if($creneau->etablissement_id !== $request->user()->etablissement_id, 403);
    }
}
