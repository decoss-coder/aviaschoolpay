<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use Illuminate\Http\Request;

class EcoleSwitcherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user->isEnseignant(), 403);

        $enseignants = $user->enseignants()
            ->where('actif', true)
            ->with('etablissement')
            ->get();

        $activeId = session('active_etablissement_id');

        return view('mon-espace.ecole-switcher', compact('enseignants', 'activeId'));
    }

    public function select(Request $request)
    {
        $data = $request->validate([
            'etablissement_id' => 'required|integer|exists:etablissements,id',
        ]);

        $user = $request->user();
        $ok = $user->enseignants()
            ->where('actif', true)
            ->where('etablissement_id', $data['etablissement_id'])
            ->exists();

        abort_if(!$ok, 403, "Vous n'êtes pas affecté à cet établissement.");

        session(['active_etablissement_id' => (int) $data['etablissement_id']]);

        return redirect()->route('mon-espace.dashboard')
            ->with('success', 'École active mise à jour.');
    }
}
