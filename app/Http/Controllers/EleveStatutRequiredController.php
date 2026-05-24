<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EleveStatutRequiredController extends Controller
{
    public function edit(Request $request, Eleve $eleve)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab && (int) $eleve->etablissement_id === (int) $etab->id, 403);

        $redirect = $request->query('redirect', route('eleves.show', $eleve));

        return view('eleves.statut-required', compact('eleve', 'redirect'));
    }

    public function update(Request $request, Eleve $eleve)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab && (int) $eleve->etablissement_id === (int) $etab->id, 403);

        $validated = $request->validate([
            'statut_eleve' => ['required', Rule::in(['AFF', 'NAFF'])],
            'redirect' => ['nullable', 'string', 'max:500'],
        ]);

        $eleve->update([
            'statut_eleve' => $validated['statut_eleve'],
        ]);

        return redirect($validated['redirect'] ?: route('eleves.show', $eleve))
            ->with('success', 'Statut élève mis à jour. Les frais seront maintenant calculés selon la grille tarifaire.');
    }
}
