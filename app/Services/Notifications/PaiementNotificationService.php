<?php

namespace App\Services\Notifications;

use App\Mail\PaiementDirectionMail;
use App\Models\Notification;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaiementNotificationService
{
    /**
     * Paiement Wave / mobile initié — alerte direction pour validation.
     */
    public static function notifierInitie(Paiement $paiement): void
    {
        if ($paiement->statut !== 'en_attente') {
            return;
        }

        $paiement->loadMissing(['eleve', 'etablissement:id,nom,sigle']);

        self::creerPourDirection($paiement, [
            'variant' => 'initie',
            'titre' => 'Paiement en attente',
            'message' => self::messageInitie($paiement),
            'email_subject' => '[AviaSchoolPay] Paiement Wave en attente de validation',
            'envoyer_email' => true,
        ]);
    }

    /**
     * Paiement confirmé — notification + e-mail direction (+ parent si compte lié).
     */
    public static function notifierConfirme(Paiement $paiement): void
    {
        if ($paiement->statut !== 'confirme') {
            return;
        }

        $paiement->loadMissing(['eleve', 'etablissement:id,nom,sigle', 'inscription.classe:id,nom']);

        self::creerPourDirection($paiement, [
            'variant' => 'confirme',
            'titre' => 'Paiement confirmé',
            'message' => self::messageConfirme($paiement),
            'email_subject' => '[AviaSchoolPay] Paiement confirmé — '.$paiement->numero_recu,
            'envoyer_email' => true,
        ]);

        self::notifierParent($paiement);
    }

    /**
     * @param  array{variant: string, titre: string, message: string, email_subject: string, envoyer_email: bool}  $options
     */
    private static function creerPourDirection(Paiement $paiement, array $options): void
    {
        $etabId = (int) $paiement->etablissement_id;
        $variant = $options['variant'];
        $lien = route('paiements.show', $paiement);

        foreach (self::destinatairesDirection($etabId) as $user) {
            if (self::existeDeja($user->id, $paiement->id, $variant)) {
                continue;
            }

            $notification = Notification::create([
                'user_id' => $user->id,
                'etablissement_id' => $etabId,
                'titre' => $options['titre'],
                'message' => $options['message'],
                'canal' => 'app',
                'type' => 'paiement',
                'lien_action' => $lien,
                'lue' => false,
                'envoyee' => false,
                'metadata' => [
                    'paiement_id' => $paiement->id,
                    'variant' => $variant,
                    'eleve_id' => $paiement->eleve_id,
                    'montant' => $paiement->montant,
                    'mode' => $paiement->mode,
                    'reference' => $paiement->reference,
                ],
            ]);

            if ($options['envoyer_email'] && filled($user->email)) {
                try {
                    Mail::to($user->email)->send(new PaiementDirectionMail(
                        $paiement,
                        $options['email_subject'],
                        $options['message'],
                        $variant
                    ));
                    $notification->update([
                        'envoyee' => true,
                        'envoyee_at' => now(),
                        'canal' => 'app+email',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('E-mail notification paiement non envoyé', [
                        'user_id' => $user->id,
                        'paiement_id' => $paiement->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private static function notifierParent(Paiement $paiement): void
    {
        $eleve = $paiement->eleve;
        if (! $eleve) {
            return;
        }

        $eleve->loadMissing('parents.user');
        foreach ($eleve->parents as $parent) {
            $user = $parent->user;
            if (! $user?->actif) {
                continue;
            }

            if (self::existeDeja($user->id, $paiement->id, 'parent_confirme')) {
                continue;
            }

            Notification::create([
                'user_id' => $user->id,
                'etablissement_id' => $paiement->etablissement_id,
                'titre' => 'Paiement enregistré',
                'message' => sprintf(
                    'Le paiement de %s F pour %s %s a été confirmé par l\'établissement.',
                    number_format($paiement->montant, 0, ',', ' '),
                    $eleve->prenom,
                    $eleve->nom
                ),
                'canal' => 'app',
                'type' => 'paiement',
                'lien_action' => route('paiements.show', $paiement),
                'lue' => false,
                'metadata' => [
                    'paiement_id' => $paiement->id,
                    'variant' => 'parent_confirme',
                ],
            ]);
        }
    }

    /** @return Collection<int, User> */
    private static function destinatairesDirection(int $etablissementId): Collection
    {
        return User::query()
            ->where('actif', true)
            ->whereIn('role', ['directeur', 'directeur_adjoint', 'gestionnaire', 'comptable'])
            ->where(function ($q) use ($etablissementId) {
                $q->where('etablissement_id', $etablissementId)
                    ->orWhere('active_etablissement_id', $etablissementId);
            })
            ->get();
    }

    private static function existeDeja(int $userId, int $paiementId, string $variant): bool
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'paiement')
            ->where('metadata->paiement_id', $paiementId)
            ->where('metadata->variant', $variant)
            ->exists();
    }

    private static function messageInitie(Paiement $paiement): string
    {
        $eleve = $paiement->eleve;

        return sprintf(
            '%s %s — %s F via %s (%s). Validez le paiement après réception Wave.',
            $eleve?->prenom ?? 'Élève',
            $eleve?->nom ?? '',
            number_format($paiement->montant, 0, ',', ' '),
            strtoupper($paiement->mode ?? 'wave'),
            $paiement->reference
        );
    }

    private static function messageConfirme(Paiement $paiement): string
    {
        $eleve = $paiement->eleve;
        $classe = $paiement->inscription?->classe?->nom ?? '—';

        return sprintf(
            '%s %s (%s) — %s F confirmés. Reçu : %s.',
            $eleve?->prenom ?? 'Élève',
            $eleve?->nom ?? '',
            $classe,
            number_format($paiement->montant, 0, ',', ' '),
            $paiement->numero_recu ?? $paiement->reference
        );
    }
}
