<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\AnneeScolaireRestaurationDemande;
use App\Models\PlatformSetting;
use App\Services\Finance\WavePaymentLinkService;
use App\Services\Scolarite\AnneeScolaireArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PlatformAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $demandes = AnneeScolaireRestaurationDemande::query()
            ->with(['etablissement:id,nom', 'anneeScolaire', 'demandeur'])
            ->latest()
            ->paginate(20);

        $archives = AnneeScolaire::query()
            ->where('archivee', true)
            ->with('etablissement:id,nom')
            ->latest('archived_at')
            ->limit(30)
            ->get();

        return view('admin.platform.index', [
            'demandes' => $demandes,
            'archives' => $archives,
            'waveRestauration' => PlatformSetting::get(AnneeScolaireArchiveService::PLATFORM_WAVE_RESTAURATION_KEY),
            'waveLibelle' => PlatformSetting::get(AnneeScolaireArchiveService::PLATFORM_WAVE_LIBELLE_KEY, 'Avia Technologie'),
        ]);
    }

    public function updateWave(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'wave_lien_base'         => ['required', 'string', 'max:500'],
            'wave_libelle'           => ['nullable', 'string', 'max:120'],
            'wave_lien_recharge_sms' => ['nullable', 'string', 'max:500'],
            'sms_prix_unitaire_fcfa' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $lien = WavePaymentLinkService::normaliserLienBase($data['wave_lien_base']);

        PlatformSetting::set(
            AnneeScolaireArchiveService::PLATFORM_WAVE_RESTAURATION_KEY,
            $lien,
            'Lien Wave restauration archive (500 FCFA)'
        );

        PlatformSetting::set(
            AnneeScolaireArchiveService::PLATFORM_WAVE_LIBELLE_KEY,
            $data['wave_libelle'] ?? 'Avia Technologie',
            'Libellé paiement restauration'
        );

        // ─── Lien Wave dédié aux recharges SMS ───
        if (! empty($data['wave_lien_recharge_sms'])) {
            $lienSms = WavePaymentLinkService::normaliserLienBase($data['wave_lien_recharge_sms']);
            PlatformSetting::set(
                \App\Services\Sms\SmsCreditService::PLATFORM_WAVE_RECHARGE_KEY,
                $lienSms,
                'Lien Wave Avia pour recharger les SMS'
            );
        }

        // ─── Prix unitaire SMS ───
        if (! empty($data['sms_prix_unitaire_fcfa'])) {
            PlatformSetting::set('sms_prix_unitaire_fcfa', $data['sms_prix_unitaire_fcfa'], 'Prix unitaire SMS en FCFA');
        }

        return back()->with('success', 'Paramètres Wave et SMS enregistrés.');
    }

    public function livrerCle(Request $request, AnneeScolaireRestaurationDemande $demande)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $request->validate(['confirme_paiement' => ['required', 'accepted']]);

        $annee = $demande->anneeScolaire;
        $cle = null;
        if ($annee?->restoration_key_vault) {
            try {
                $cle = Crypt::decryptString($annee->restoration_key_vault);
            } catch (\Throwable) {
                //
            }
        }

        $demande->update([
            'statut' => 'cle_livree',
            'paye_at' => $demande->paye_at ?? now(),
            'cle_livree_at' => now(),
        ]);

        return back()->with([
            'success' => 'Clé marquée comme livrée à l\'établissement.',
            'cle_livree' => $cle,
            'demande_ref' => $demande->reference,
        ]);
    }

    public function voirCleArchive(Request $request, AnneeScolaire $annee)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless($annee->archivee, 404);

        $cle = null;
        if ($annee->restoration_key_vault) {
            $cle = Crypt::decryptString($annee->restoration_key_vault);
        }

        return back()->with([
            'success' => "Clé pour {$annee->libelle} ({$annee->etablissement?->nom})",
            'cle_livree' => $cle,
        ]);
    }
}
