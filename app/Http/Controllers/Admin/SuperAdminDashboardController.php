<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaireRestaurationDemande;
use App\Services\Platform\PlatformStatsService;
use Illuminate\Http\Request;

class SuperAdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $globales       = PlatformStatsService::globales();
        $etablissements = PlatformStatsService::etablissementsAvecStats();
        $evolution      = PlatformStatsService::evolutionPaiements(14);
        $topCA          = PlatformStatsService::topEtablissementsCA(5);

        $demandesRestauration = AnneeScolaireRestaurationDemande::query()
            ->whereIn('statut', ['en_attente_paiement', 'paye'])
            ->with(['etablissement:id,nom', 'anneeScolaire:id,libelle', 'demandeur:id,nom,prenom'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.platform.dashboard', compact(
            'globales',
            'etablissements',
            'evolution',
            'topCA',
            'demandesRestauration'
        ));
    }
}
