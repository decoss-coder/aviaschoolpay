<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Services\Finance\WavePaymentLinkService;
use Illuminate\Http\Request;

class WavePaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $etablissements = Etablissement::query()
            ->orderBy('nom')
            ->get()
            ->map(function (Etablissement $etab) {
                $etab->wave_lien_masque = WavePaymentLinkService::masquerLienBase($etab->wave_lien_base);

                return $etab;
            });

        return view('admin.wave-payments.index', compact('etablissements'));
    }

    public function update(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'wave_actif' => ['nullable', 'boolean'],
            'wave_libelle' => ['nullable', 'string', 'max:120'],
            'wave_lien_base' => ['nullable', 'string', 'max:500'],
        ]);

        $lien = filled($data['wave_lien_base'] ?? null)
            ? WavePaymentLinkService::normaliserLienBase($data['wave_lien_base'])
            : $etablissement->wave_lien_base;

        if ($request->boolean('wave_actif') && ! $lien) {
            return back()->withErrors([
                'wave_lien_base' => 'Un lien Wave valide est requis pour activer le paiement.',
            ])->withInput();
        }

        $etablissement->update([
            'wave_actif' => $request->boolean('wave_actif'),
            'wave_libelle' => $data['wave_libelle'] ?? $etablissement->nom,
            'wave_lien_base' => $lien,
            'wave_configured_at' => $lien ? now() : null,
            'wave_configured_by' => $lien ? $request->user()->id : null,
        ]);

        return back()->with('success', "Configuration Wave enregistrée pour « {$etablissement->nom} ».");
    }

    public function test(Request $request, Etablissement $etablissement)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $montant = (int) $request->validate(['montant' => ['required', 'integer', 'min:100']])['montant'];

        try {
            $url = WavePaymentLinkService::construireUrl($etablissement, $montant);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('wave_test_url', $url);
    }
}
