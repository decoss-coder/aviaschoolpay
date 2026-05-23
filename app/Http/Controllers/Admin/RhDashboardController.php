<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertePointage;
use App\Models\CongePermission;
use App\Models\Enseignant;
use App\Models\PaieEnseignant;
use App\Models\Pointage;
use Illuminate\Http\Request;

class RhDashboardController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $today = today();
        $moisCourant = now()->format('Y-m');

        $enseignantsBase = Enseignant::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true);

        $totalEnseignants = (clone $enseignantsBase)->count();

        $presents = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->whereDate('date', $today)
            ->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)
            ->whereIn('statut', [Pointage::STATUT_PRESENT, Pointage::STATUT_RETARD])
            ->distinct('enseignant_id')
            ->count('enseignant_id');

        $retards = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->whereDate('date', $today)
            ->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)
            ->where('statut', Pointage::STATUT_RETARD)
            ->distinct('enseignant_id')
            ->count('enseignant_id');

        $absents = max($totalEnseignants - $presents, 0);

        $alertesNonTraitees = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->where('traitee', false)
            ->count();

        $congesEnAttente = CongePermission::query()
            ->where('etablissement_id', $etab->id)
            ->where('statut', CongePermission::STATUT_EN_ATTENTE)
            ->count();

        $masseSalarialeMois = (float) PaieEnseignant::query()
            ->where('etablissement_id', $etab->id)
            ->where('mois', $moisCourant)
            ->sum('net_a_payer');

        $scorePonctualiteMoyen = round(
            (float) $enseignantsBase->avg('score_ponctualite'),
            2
        );

        $enseignantsEnRetard = Pointage::query()
            ->where('etablissement_id', $etab->id)
            ->whereDate('date', $today)
            ->where('type_scan', Pointage::TYPE_SCAN_ARRIVEE)
            ->where('statut', Pointage::STATUT_RETARD)
            ->with('enseignant')
            ->orderByDesc('heure_scan')
            ->limit(8)
            ->get();

        $dernieresAlertes = AlertePointage::query()
            ->where('etablissement_id', $etab->id)
            ->with(['enseignant', 'pointage'])
            ->latest('date')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('admin.rh.dashboard', compact(
            'totalEnseignants',
            'presents',
            'retards',
            'absents',
            'alertesNonTraitees',
            'congesEnAttente',
            'masseSalarialeMois',
            'scorePonctualiteMoyen',
            'enseignantsEnRetard',
            'dernieresAlertes'
        ));
    }
}