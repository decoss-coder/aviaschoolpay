<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsRecharge;
use App\Services\Sms\SmsCreditService;
use Illuminate\Http\Request;

class SmsAdminController extends Controller
{
    public function __construct(private SmsCreditService $service) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $statut = $request->get('statut');
        $query = SmsRecharge::with(['etablissement:id,nom', 'demandeur:id,nom,prenom', 'crediteParUser:id,nom,prenom']);
        if ($statut) $query->where('statut', $statut);

        $recharges = $query->latest()->paginate(30)->withQueryString();

        $stats = [
            'en_attente'     => SmsRecharge::where('statut', 'en_attente_paiement')->count(),
            'paye'           => SmsRecharge::where('statut', 'paye')->count(),
            'credite'        => SmsRecharge::where('statut', 'credite')->count(),
            'revenus_total'  => (int) SmsRecharge::where('statut', 'credite')->sum('montant_fcfa'),
            'revenus_mois'   => (int) SmsRecharge::where('statut', 'credite')
                                ->where('credite_at', '>=', now()->startOfMonth())->sum('montant_fcfa'),
            'sms_credites'   => (int) SmsRecharge::where('statut', 'credite')->sum('nb_sms'),
        ];

        return view('admin.sms.index', compact('recharges', 'stats', 'statut'));
    }

    public function marquerPaye(Request $request, $id)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $recharge = SmsRecharge::findOrFail($id);
        $this->service->marquerPaye($recharge);
        return back()->with('success', "Recharge {$recharge->reference} marquée comme PAYÉE — prête à créditer.");
    }

    public function crediter(Request $request, $id)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $request->validate(['note' => 'nullable|string|max:500']);

        $recharge = SmsRecharge::findOrFail($id);
        $this->service->crediter($recharge, $request->user(), $request->note);
        return back()->with('success',
            "✓ {$recharge->nb_sms} SMS crédités à « {$recharge->etablissement?->nom} » ({$recharge->reference}).");
    }

    public function annuler(Request $request, $id)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $recharge = SmsRecharge::findOrFail($id);
        $this->service->annulerRecharge($recharge, $request->note ?? 'Annulé par super-admin');
        return back()->with('success', 'Recharge annulée.');
    }
}
