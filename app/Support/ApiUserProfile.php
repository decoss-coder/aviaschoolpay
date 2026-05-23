<?php

namespace App\Support;

use App\Models\User;
use App\Services\Finance\ParentScopeService;
use App\Services\Scolarite\AnneeScolaireContext;

/**
 * Représentation utilisateur pour les réponses API /api/v1 (me, login).
 */
class ApiUserProfile
{
    /**
     * Payload pour POST /api/v1/auth/login (champs attendus par les apps mobiles).
     *
     * @return array<string, mixed>
     */
    public static function loginResponse(User $user, string $plainToken): array
    {
        $user = $user->fresh(['eleve', 'parentTuteur', 'enseignants.etablissement']);

        return [
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $user->only([
                'id', 'nom', 'prenom', 'email', 'telephone',
                'etablissement_id', 'active_etablissement_id',
            ]),
            'role' => $user->role,
            'profiles' => self::profilesBlock($user),
            'ecoles_enseignant' => self::ecolesEnseignant($user),
            'needs_etablissement_selection' => $user->isEnseignant()
                && $user->enseignants()->where('actif', true)->count() > 1
                && !$user->active_etablissement_id,
            'needs_password_change' => (bool) $user->premiere_connexion,
            'annee_scolaire_courante' => AnneeScolaireContext::toApiPayload(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user): array
    {
        $user->loadMissing(['eleve', 'parentTuteur', 'enseignants.etablissement']);

        $nbEcoles = $user->isEnseignant() ? $user->enseignants()->where('actif', true)->count() : 0;

        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'role' => $user->role,
            'langue' => $user->langue,
            'etablissement_id' => $user->etablissement_id,
            'active_etablissement_id' => $user->active_etablissement_id,
            'ecole_active_id' => $user->ecoleActiveId(),
            'profiles' => self::profilesBlock($user),
            'ecoles_enseignant' => self::ecolesEnseignant($user),
            'needs_etablissement_selection' => $user->isEnseignant() && $nbEcoles > 1 && !$user->active_etablissement_id,
            'needs_password_change' => (bool) $user->premiere_connexion,
            'annee_scolaire_courante' => AnneeScolaireContext::toApiPayload(),
        ];
    }

    /**
     * @return array{enseignant: mixed, eleve: mixed, parent: mixed}
     */
    private static function profilesBlock(User $user): array
    {
        $ens = $user->enseignantActif();

        $eleveData = null;
        if ($user->eleve) {
            $eleve = $user->eleve->loadMissing('etablissement:id,nom', 'inscriptionEnCours.classe:id,nom');
            $classe = $eleve->classeEffective();
            $eleveData = array_merge(
                $eleve->only(['id', 'nom', 'prenom', 'etablissement_id', 'classe_id', 'matricule_desps', 'matricule_interne']),
                [
                    'classe'        => $classe?->only(['id', 'nom']),
                    'etablissement' => $eleve->etablissement?->only(['id', 'nom']),
                ]
            );
        }

        $parentProfils = $user->isParent()
            ? ParentScopeService::profilsPourUser($user)->map(fn ($p) => $p->only(['id', 'nom', 'prenom', 'etablissement_id', 'telephone']) + [
                'etablissement' => $p->etablissement?->only(['id', 'nom', 'sigle']),
            ])->values()->all()
            : [];

        return [
            'enseignant' => $ens?->only(['id', 'nom', 'prenom', 'etablissement_id']),
            'eleve'      => $eleveData,
            'parent'     => $user->parentTuteur?->only(['id', 'nom', 'prenom', 'etablissement_id', 'telephone']),
            'parents'    => $parentProfils,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function ecolesEnseignant(User $user): array
    {
        if (!$user->isEnseignant()) {
            return [];
        }

        return $user->enseignants()
            ->where('actif', true)
            ->with('etablissement:id,nom,sigle')
            ->get()
            ->map(fn ($e) => [
                'etablissement_id' => $e->etablissement_id,
                'enseignant_id' => $e->id,
                'etablissement' => $e->etablissement?->only(['id', 'nom', 'sigle']),
            ])
            ->values()
            ->all();
    }
}
