<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PointageParametreWebController extends Controller
{
    public function edit(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        return view('pointage.parametres', compact('etab'));
    }

    public function update(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $validated = $request->validate([
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'gps_rayon_metres' => ['nullable', 'integer', 'min:50', 'max:500'],
        ]);

        $etab->update([
            'gps_latitude' => $validated['gps_latitude'],
            'gps_longitude' => $validated['gps_longitude'],
            'gps_rayon_metres' => $validated['gps_rayon_metres'] ?? 100,
        ]);

        return redirect()
            ->route('pointage.parametres.edit')
            ->with('success', 'Coordonnées GPS de l\'établissement enregistrées.');
    }
}
