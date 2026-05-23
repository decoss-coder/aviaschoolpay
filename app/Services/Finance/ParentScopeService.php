<?php

namespace App\Services\Finance;

use App\Models\Eleve;
use App\Models\ParentTuteur;
use App\Models\User;
use App\Services\Eleve\EleveInscriptionService;
use Illuminate\Support\Collection;

class ParentScopeService
{
    /**
     * Tous les profils parent liés au compte (multi-écoles : même user_id ou même téléphone).
     *
     * @return Collection<int, ParentTuteur>
     */
    public static function profilsPourUser(User $user): Collection
    {
        if (! $user->isParent()) {
            return collect();
        }

        $phoneVariants = EleveInscriptionService::phoneVariants($user->telephone ?? '');

        $query = ParentTuteur::query()->where('actif', true);

        $query->where(function ($q) use ($user, $phoneVariants) {
            $q->where('user_id', $user->id);

            if ($phoneVariants !== []) {
                $q->orWhereIn('telephone', $phoneVariants)
                    ->orWhereIn('telephone_2', $phoneVariants);
            }
        });

        return $query->with('etablissement:id,nom,sigle')->get()->unique('id')->values();
    }

    /**
     * Enfants de tous les profils parent (plusieurs écoles possibles).
     *
     * @return Collection<int, Eleve>
     */
    public static function enfantsPourUser(User $user): Collection
    {
        $parentIds = self::profilsPourUser($user)->pluck('id');

        if ($parentIds->isEmpty()) {
            return collect();
        }

        return Eleve::query()
            ->whereHas('parents', fn ($q) => $q->whereIn('parents_tuteurs.id', $parentIds))
            ->with(['classe.niveau', 'etablissement:id,nom,sigle', 'parents'])
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->unique('id')
            ->values();
    }

    public static function userPeutVoirEleve(User $user, Eleve $eleve): bool
    {
        if (! $user->isParent()) {
            return false;
        }

        $parentIds = self::profilsPourUser($user)->pluck('id');

        return $eleve->parents()->whereIn('parents_tuteurs.id', $parentIds)->exists();
    }

    /** Premier profil parent (compatibilité). */
    public static function profilPrincipal(User $user): ?ParentTuteur
    {
        return self::profilsPourUser($user)->first()
            ?? $user->parentTuteur;
    }

    /**
     * Lie un profil parent à l'utilisateur si le téléphone correspond.
     */
    public static function lierComptesOrphelins(User $user): void
    {
        if (! $user->isParent() || ! $user->telephone) {
            return;
        }

        $variants = EleveInscriptionService::phoneVariants($user->telephone);
        if ($variants === []) {
            return;
        }

        ParentTuteur::query()
            ->where(function ($q) use ($variants) {
                $q->whereIn('telephone', $variants)->orWhereIn('telephone_2', $variants);
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->update(['user_id' => $user->id]);
    }
}
