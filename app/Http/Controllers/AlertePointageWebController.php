<?php

namespace App\Http\Controllers;

use App\Models\AlertePointage;
use App\Models\Enseignant;
use Illuminate\Http\Request;

class AlertePointageWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $date = $request->date ?: today()->toDateString();

        $query = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'enseignant.user',
                'pointage.salle',
                'pointage.qrCode',
                'traiteePar',
            ])
            ->whereDate('date', $date);

        if ($request->filled('search')) {
            $s = trim((string) $request->search);

            $query->where(function ($q) use ($s) {
                $q->where('message', 'like', "%{$s}%")
                    ->orWhere('commentaire_traitement', 'like', "%{$s}%")
                    ->orWhereHas('enseignant', function ($sub) use ($s) {
                        $sub->where('nom', 'like', "%{$s}%")
                            ->orWhere('prenom', 'like', "%{$s}%")
                            ->orWhere('matricule_mena', 'like', "%{$s}%")
                            ->orWhere('telephone', 'like', "%{$s}%")
                            ->orWhere('specialite', 'like', "%{$s}%");
                    });
            });
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', (int) $request->enseignant_id);
        }

        if ($request->filled('type_alerte')) {
            $query->where('type_alerte', $request->type_alerte);
        }

        if ($request->filled('gravite')) {
            $query->where('gravite', $request->gravite);
        }

        if ($request->filled('etat')) {
            if ($request->etat === 'non_lue') {
                $query->where('lue', false);
            } elseif ($request->etat === 'lue') {
                $query->where('lue', true);
            } elseif ($request->etat === 'non_traitee') {
                $query->where('traitee', false);
            } elseif ($request->etat === 'traitee') {
                $query->where('traitee', true);
            }
        }

        $statsBase = clone $query;

        $stats = [
            'total' => (clone $statsBase)->count(),
            'non_lues' => (clone $statsBase)->where('lue', false)->count(),
            'non_traitees' => (clone $statsBase)->where('traitee', false)->count(),
            'traitees' => (clone $statsBase)->where('traitee', true)->count(),
            'info' => (clone $statsBase)->where('gravite', 'info')->count(),
            'warning' => (clone $statsBase)->where('gravite', 'warning')->count(),
            'critiques' => (clone $statsBase)->where('gravite', 'critique')->count(),
        ];

        $alertes = $query
            ->orderByDesc('date')
            ->orderBy('traitee')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $enseignants = Enseignant::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $typesAlerteDisponibles = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->distinct()
            ->orderBy('type_alerte')
            ->pluck('type_alerte')
            ->all();

        return view('alertes-pointage.index', compact(
            'alertes',
            'stats',
            'enseignants',
            'typesAlerteDisponibles',
            'date'
        ));
    }

    public function show(Request $request, int $alerte)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $alerte = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->with([
                'enseignant.user',
                'pointage.salle',
                'pointage.qrCode',
                'traiteePar',
            ])
            ->findOrFail($alerte);

        if (!$alerte->lue) {
            $alerte->update(['lue' => true]);
            $alerte->refresh();
        }

        return view('alertes-pointage.show', compact('alerte'));
    }

    public function marquerLue(Request $request, int $alerte)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $alerte = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->findOrFail($alerte);

        $alerte->update(['lue' => true]);

        return back()->with('success', 'Alerte marquée comme lue.');
    }

    public function traiter(Request $request, int $alerte)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $validated = $request->validate([
            'commentaire_traitement' => ['nullable', 'string', 'max:1000'],
        ]);

        $alerte = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->findOrFail($alerte);

        $alerte->update([
            'lue' => true,
            'traitee' => true,
            'traitee_par' => $request->user()->id,
            'commentaire_traitement' => $validated['commentaire_traitement'] ?? null,
        ]);

        return back()->with('success', 'Alerte traitée avec succès.');
    }
}