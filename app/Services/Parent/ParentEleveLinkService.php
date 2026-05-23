<?php

namespace App\Services\Parent;

use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\ParentTuteur;
use App\Services\Eleve\EleveInscriptionService;

/**
 * Lie un élève à son parent/tuteur et au compte utilisateur mobile.
 */
class ParentEleveLinkService
{
    /**
     * @param  array<string, mixed>  $parentData  Champs ParentTuteur (nom, prenom, telephone, …)
     */
    public static function synchroniser(Eleve $eleve, Etablissement $etab, array $parentData): ?ParentTuteur
    {
        $tel = EleveInscriptionService::normalize($parentData['telephone'] ?? '');
        if (strlen($tel) < 8) {
            return null;
        }

        $parentData['telephone'] = $tel;
        $parentData['etablissement_id'] = $etab->id;
        $parentData['actif'] = true;

        $variants = EleveInscriptionService::phoneVariants($tel);

        $parent = ParentTuteur::query()
            ->where('etablissement_id', $etab->id)
            ->where(function ($q) use ($variants) {
                $q->whereIn('telephone', $variants)->orWhereIn('telephone_2', $variants);
            })
            ->first();

        if ($parent) {
            $parent->update($parentData);
        } else {
            $parent = ParentTuteur::create($parentData);
        }

        $eleve->parents()->syncWithoutDetaching([
            $parent->id => [
                'est_contact_principal' => true,
                'autorise_recuperation' => true,
            ],
        ]);

        try {
            ParentAccountService::ensureUser($parent);
        } catch (\InvalidArgumentException) {
            // Téléphone invalide — liaison élève/parent conservée sans compte user
        }

        return $parent->fresh();
    }
}
