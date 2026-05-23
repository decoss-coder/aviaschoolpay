<?php

namespace App\Services\Sms;

use App\Models\Etablissement;
use App\Models\SmsCredit;
use App\Models\SmsEnvoi;
use App\Models\SmsRecharge;
use App\Models\User;
use App\Services\Finance\WavePaymentLinkService;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;

class SmsCreditService
{
    /** Prix unitaire d'un SMS en FCFA (configurable via PlatformSetting). */
    public const PRIX_UNITAIRE_FCFA = 50;

    public const PLATFORM_WAVE_RECHARGE_KEY = 'wave_lien_recharge_sms';

    public function __construct(private InfobipSmsService $infobip) {}

    // ─────────────────────────────────────────────────────────
    // CREDIT (solde)
    // ─────────────────────────────────────────────────────────

    public function getOrCreateCredit(int $etabId): SmsCredit
    {
        return SmsCredit::firstOrCreate(['etablissement_id' => $etabId]);
    }

    public function solde(int $etabId): int
    {
        return (int) $this->getOrCreateCredit($etabId)->solde;
    }

    public function aSuffisance(int $etabId, int $nbSms): bool
    {
        return $this->solde($etabId) >= $nbSms;
    }

    // ─────────────────────────────────────────────────────────
    // RECHARGE WAVE
    // ─────────────────────────────────────────────────────────

    /**
     * Créer une demande de recharge (lien Wave de paiement Avia).
     */
    public function creerRecharge(Etablissement $etab, User $user, int $nbSms): SmsRecharge
    {
        abort_unless($nbSms > 0 && $nbSms <= 100000, 422, 'Nombre de SMS invalide (1 - 100 000).');

        $prixUnitaire = (int) self::prixUnitaire();
        $montant = $nbSms * $prixUnitaire;

        $waveUrl = self::lienWaveAvia($montant);

        return SmsRecharge::create([
            'etablissement_id'     => $etab->id,
            'demandeur_id'         => $user->id,
            'reference'            => SmsRecharge::genererReference(),
            'nb_sms'               => $nbSms,
            'montant_fcfa'         => $montant,
            'prix_unitaire_fcfa'   => $prixUnitaire,
            'wave_checkout_url'    => $waveUrl,
            'statut'               => 'en_attente_paiement',
        ]);
    }

    /**
     * Marquer une recharge comme payée (par la direction ou par webhook).
     */
    public function marquerPaye(SmsRecharge $recharge): SmsRecharge
    {
        abort_if($recharge->statut !== 'en_attente_paiement', 422, 'Recharge déjà traitée.');
        $recharge->update(['statut' => 'paye', 'paye_at' => now()]);
        return $recharge->fresh();
    }

    /**
     * Crédite effectivement les SMS sur le compte de l'école.
     * Action réservée au super-admin Avia (après vérification du paiement Wave).
     */
    public function crediter(SmsRecharge $recharge, User $admin, ?string $note = null): SmsRecharge
    {
        abort_if($recharge->statut === 'credite', 422, 'Déjà crédité.');
        abort_if($recharge->statut === 'annule', 422, 'Recharge annulée.');

        DB::transaction(function () use ($recharge, $admin, $note) {
            $credit = $this->getOrCreateCredit($recharge->etablissement_id);
            $credit->increment('solde', $recharge->nb_sms);
            $credit->increment('cumul_recharge', $recharge->nb_sms);
            $credit->increment('cumul_paye_fcfa', $recharge->montant_fcfa);

            $recharge->update([
                'statut'      => 'credite',
                'credite_at'  => now(),
                'credite_par' => $admin->id,
                'notes_admin' => $note,
                'paye_at'     => $recharge->paye_at ?? now(),
            ]);
        });

        return $recharge->fresh();
    }

    public function annulerRecharge(SmsRecharge $recharge, ?string $note = null): SmsRecharge
    {
        abort_if(in_array($recharge->statut, ['credite']), 422, 'Recharge déjà créditée — impossible d\'annuler.');
        $recharge->update(['statut' => 'annule', 'notes_admin' => $note]);
        return $recharge->fresh();
    }

    // ─────────────────────────────────────────────────────────
    // ENVOI SMS
    // ─────────────────────────────────────────────────────────

    /**
     * Envoie un SMS et débite le solde si succès.
     *
     * @return array{success: bool, envoi: ?SmsEnvoi, error?: string}
     */
    public function envoyer(
        Etablissement $etab,
        ?User $user,
        string $destinataire,
        ?string $destinataireNom,
        string $contenu,
        string $type = 'manuel',
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): array {
        $nbParties = InfobipSmsService::nbParties($contenu);

        if (! $this->aSuffisance($etab->id, $nbParties)) {
            return [
                'success' => false,
                'envoi'   => null,
                'error'   => "Solde SMS insuffisant. Reste : {$this->solde($etab->id)}, requis : $nbParties",
            ];
        }

        // Créer l'envoi en attente
        $envoi = SmsEnvoi::create([
            'etablissement_id' => $etab->id,
            'envoye_par'       => $user?->id,
            'destinataire'     => $destinataire,
            'destinataire_nom' => $destinataireNom,
            'contenu'          => $contenu,
            'type'             => $type,
            'statut'           => 'en_attente',
            'nb_parties'       => $nbParties,
            'reference_type'   => $referenceType,
            'reference_id'     => $referenceId,
        ]);

        // Appel Infobip
        $r = $this->infobip->send($destinataire, $contenu, $envoi->id);

        if ($r['success']) {
            $envoi->update([
                'statut'             => 'envoye',
                'infobip_message_id' => $r['message_id'] ?? null,
                'infobip_response'   => json_encode($r['response'] ?? null),
                'sent_at'            => now(),
            ]);

            // Débit du solde + cumul envoyé
            $credit = $this->getOrCreateCredit($etab->id);
            DB::transaction(function () use ($credit, $nbParties) {
                $credit->decrement('solde', $nbParties);
                $credit->increment('cumul_envoye', $nbParties);
            });

            return ['success' => true, 'envoi' => $envoi->fresh()];
        }

        $envoi->update([
            'statut'           => 'echec',
            'erreur'           => mb_substr($r['error'] ?? 'Erreur inconnue', 0, 500),
            'infobip_response' => isset($r['response']) ? json_encode($r['response']) : null,
        ]);

        return ['success' => false, 'envoi' => $envoi->fresh(), 'error' => $r['error']];
    }

    /**
     * Envoi en masse — retourne un résumé.
     *
     * @param array<int, array{tel: string, nom?: string}> $destinataires
     * @return array{envoyes: int, echecs: int, total: int, details: array}
     */
    public function envoyerMasse(
        Etablissement $etab,
        ?User $user,
        array $destinataires,
        string $contenu,
        string $type = 'manuel',
    ): array {
        $envoyes = 0; $echecs = 0; $details = [];

        foreach ($destinataires as $d) {
            $r = $this->envoyer(
                $etab, $user,
                $d['tel'] ?? '',
                $d['nom'] ?? null,
                $contenu,
                $type,
                $d['ref_type'] ?? null,
                $d['ref_id'] ?? null,
            );
            if ($r['success']) {
                $envoyes++;
            } else {
                $echecs++;
                $details[] = ['tel' => $d['tel'] ?? '', 'erreur' => $r['error'] ?? '?'];
            }
        }

        return [
            'envoyes' => $envoyes,
            'echecs'  => $echecs,
            'total'   => count($destinataires),
            'details' => $details,
        ];
    }

    public static function prixUnitaire(): int
    {
        return (int) PlatformSetting::get('sms_prix_unitaire_fcfa', self::PRIX_UNITAIRE_FCFA);
    }

    /**
     * Lien Wave Avia pour recharger un montant donné.
     * Fallback : si le lien dédié SMS n'est pas configuré, utilise le lien Wave Avia
     * général (celui des restaurations d'archives) — même compte bénéficiaire Avia.
     */
    public static function lienWaveAvia(int $montant): ?string
    {
        // 1. Priorité : clé dédiée SMS
        $base = PlatformSetting::get(self::PLATFORM_WAVE_RECHARGE_KEY);

        // 2. Fallback : clé Wave Avia générale (config par super-admin pour restauration archives)
        if (! $base) {
            $base = PlatformSetting::get(\App\Services\Scolarite\AnneeScolaireArchiveService::PLATFORM_WAVE_RESTAURATION_KEY);
        }

        if (! $base) return null;

        try {
            $fake = new Etablissement(['wave_actif' => true, 'wave_lien_base' => $base]);
            return WavePaymentLinkService::construireUrl($fake, $montant);
        } catch (\Throwable) {
            return rtrim($base, '/').(str_contains($base, '?') ? '&' : '?').'amount='.$montant;
        }
    }
}
