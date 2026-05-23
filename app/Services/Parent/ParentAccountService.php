<?php

namespace App\Services\Parent;

use App\Models\ParentTuteur;
use App\Models\User;
use App\Services\Eleve\EleveInscriptionService;
use App\Services\Finance\ParentScopeService;
use Illuminate\Support\Facades\Hash;

class ParentAccountService
{
    public const DEFAULT_PASSWORD = '0000';

    /**
     * Crée ou met à jour le compte utilisateur lié au parent/tuteur.
     */
    public static function ensureUser(ParentTuteur $parent): User
    {
        $parent->refresh();
        $phone = EleveInscriptionService::normalize($parent->telephone);

        if (strlen($phone) < 8) {
            throw new \InvalidArgumentException('Le téléphone du parent doit contenir au moins 8 chiffres pour créer un compte.');
        }

        $prenom = trim((string) $parent->prenom) ?: 'Parent';
        $nom = trim((string) $parent->nom) ?: 'Tuteur';

        if ($parent->user_id && $parent->user) {
            $user = $parent->user;
            $user->update([
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => self::uniqueTelephone($phone, $user->id),
                'email' => $user->email ?: self::buildEmail($parent),
                'sexe' => self::sexeFromLien($parent->lien_parente),
                'etablissement_id' => $parent->etablissement_id,
                'actif' => true,
            ]);

            ParentScopeService::lierComptesOrphelins($user);

            return $user;
        }

        $user = User::create([
            'etablissement_id' => $parent->etablissement_id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => self::buildEmail($parent),
            'telephone' => self::uniqueTelephone($phone),
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'role' => 'parent',
            'sexe' => self::sexeFromLien($parent->lien_parente),
            'actif' => true,
            'premiere_connexion' => true,
            'derniere_connexion' => null,
        ]);

        $parent->update(['user_id' => $user->id]);

        ParentScopeService::lierComptesOrphelins($user);

        return $user;
    }

    public static function sexeFromLien(?string $lien): string
    {
        return in_array($lien, ['mere', 'tutrice', 'soeur', 'tante'], true) ? 'F' : 'M';
    }

    private static function buildEmail(ParentTuteur $parent): string
    {
        if ($parent->email && ! User::where('email', $parent->email)->exists()) {
            return $parent->email;
        }

        $base = 'parent.'.$parent->etablissement_id.'.'.($parent->id ?: 'new');

        return $base.'@parent.aviaschoolpay.local';
    }

    private static function uniqueTelephone(string $phone, ?int $exceptUserId = null): string
    {
        $query = User::where('telephone', $phone);

        if ($exceptUserId) {
            $query->where('id', '!=', $exceptUserId);
        }

        if (! $query->exists()) {
            return $phone;
        }

        $suffix = 1;
        do {
            $candidate = substr($phone, 0, 15).$suffix;
            $suffix++;
        } while (User::where('telephone', $candidate)
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->exists());

        return $candidate;
    }
}
