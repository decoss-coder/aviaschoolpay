<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlertePointageTraitementRequest;
use App\Models\AlertePointage;
use App\Models\Enseignant;
use Illuminate\Http\Request;

class AlertePointageAdminController extends Controller
{
    public function index(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $date = $request->input('date', today()->toDateString());

        $query = AlertePointage::query()
            ->where('etablissement_id', $etabId)
            ->with(['enseignant', 'pointage', 'traiteePar'])
            ->whereDate('date', $date);

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->integer('enseignant_id'));
        }

        if ($request->filled('gravite')) {
            $query->where('gravite', $request->input('gravite'));
        }

        if ($request->filled('traitee')) {
            $query->where('traitee', $request->boolean('traitee'));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('message', 'like', "%{$s}%")
                    ->orWhereHas('enseignant', function ($sub) use ($s) {
                        $sub->where('nom', 'like', "%{$s}%")
                            ->orWhere('prenom', 'like', "%{$s}%");
                    });
            });
        }

        $alertes = $query
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $statsBase = AlertePointage::query()
            ->where('etablissement_id', $etabId)
            ->whereDate('date', $date);

        return view('admin.rh.alertes.index', [
            'alertes' => $alertes,
            'date' => $date,
            'enseignants' => Enseignant::where('etablissement_id', $etabId)->where('actif', true)->orderBy('nom')->orderBy('prenom')->get(),
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'non_lues' => (clone $statsBase)->where('lue', false)->count(),
                'non_traitees' => (clone $statsBase)->where('traitee', false)->count(),
                'critiques' => (clone $statsBase)->where('gravite', AlertePointage::GRAVITE_CRITIQUE)->count(),
            ],
        ]);
    }

    public function show(Request $request, AlertePointage $alerte)
    {
        abort_unless($alerte->etablissement_id === $request->user()->etablissement_id, 404);

        if (!$alerte->lue) {
            $alerte->update(['lue' => true]);
        }

        $alerte->load(['enseignant', 'pointage', 'traiteePar']);

        return view('admin.rh.alertes.show', compact('alerte'));
    }

    public function marquerLue(Request $request, AlertePointage $alerte)
    {
        abort_unless($alerte->etablissement_id === $request->user()->etablissement_id, 404);

        $alerte->update(['lue' => true]);

        return back()->with('success', 'Alerte marquée comme lue.');
    }

    public function traiter(AlertePointageTraitementRequest $request, AlertePointage $alerte)
    {
        abort_unless($alerte->etablissement_id === $request->user()->etablissement_id, 404);

        $alerte->update([
            'lue' => true,
            'traitee' => true,
            'traitee_par' => $request->user()->id,
            'commentaire_traitement' => $request->validated()['commentaire_traitement'] ?? null,
        ]);

        return back()->with('success', 'Alerte traitée avec succès.');
    }
}