<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enseignant;
use App\Models\Pointage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PointageAdminController extends Controller
{
    public function index(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $date = $request->input('date', today()->toDateString());

        $query = Pointage::query()
            ->where('etablissement_id', $etabId)
            ->with(['enseignant', 'salle', 'alertes'])
            ->whereDate('date', $date);

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->integer('enseignant_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('type_scan')) {
            $query->where('type_scan', $request->input('type_scan'));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->whereHas('enseignant', function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('telephone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('cahier')) {
            match ($request->cahier) {
                'valide' => $query->where('cahier_texte_validated', true),
                'non_valide' => $query->whereNotNull('cahier_texte_path')->where('cahier_texte_validated', false),
                'manquant' => $query->whereNull('cahier_texte_path'),
                default => null,
            };
        }

        $pointages = $query
            ->orderByDesc('heure_scan')
            ->paginate(25)
            ->withQueryString();

        $statsBase = Pointage::query()
            ->where('etablissement_id', $etabId)
            ->whereDate('date', $date);

        return view('admin.rh.pointages.index', [
            'pointages' => $pointages,
            'date' => $date,
            'enseignants' => Enseignant::where('etablissement_id', $etabId)->where('actif', true)->orderBy('nom')->orderBy('prenom')->get(),
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'present' => (clone $statsBase)->where('statut', Pointage::STATUT_PRESENT)->count(),
                'retard' => (clone $statsBase)->where('statut', Pointage::STATUT_RETARD)->count(),
                'absent' => (clone $statsBase)->where('statut', Pointage::STATUT_ABSENT)->count(),
                'hors_zone' => (clone $statsBase)->where('statut', Pointage::STATUT_HORS_ZONE)->count(),
            ],
        ]);
    }

    public function show(Request $request, Pointage $pointage)
    {
        abort_unless($pointage->etablissement_id === $request->user()->etablissement_id, 404);

        $pointage->load(['enseignant', 'salle', 'alertes.traiteePar']);

        return view('admin.rh.pointages.show', compact('pointage'));
    }

    public function selfie(Request $request, Pointage $pointage)
    {
        abort_unless($pointage->etablissement_id === $request->user()->etablissement_id, 404);
        abort_if(blank($pointage->selfie_path), 404);
        abort_unless(Storage::disk('public')->exists($pointage->selfie_path), 404);

        return response()->file(Storage::disk('public')->path($pointage->selfie_path));
    }

    public function cahierTexte(Request $request, Pointage $pointage)
    {
        abort_unless($pointage->etablissement_id === $request->user()->etablissement_id, 404);
        abort_if(blank($pointage->cahier_texte_path), 404);
        abort_unless(Storage::disk('public')->exists($pointage->cahier_texte_path), 404);

        return response()->file(Storage::disk('public')->path($pointage->cahier_texte_path));
    }
}