<?php

namespace App\Policies;

use App\Models\Eleve;
use App\Models\User;

class ElevePolicy
{
    public function viewAsStudent(User $user, Eleve $eleve): bool
    {
        return $user->isEleve()
            && $user->eleve
            && (int) $user->eleve->id === (int) $eleve->id;
    }

    public function viewAsParent(User $user, Eleve $eleve): bool
    {
        if (!$user->isParent() || !$user->parentTuteur) {
            return false;
        }

        return $user->parentTuteur->eleves()->where('eleve_id', $eleve->id)->exists();
    }

    /**
     * Personnel de l'établissement (hors élève/parent).
     */
    public function viewAsStaff(User $user, Eleve $eleve): bool
    {
        if ($user->isEleve() || $user->isParent()) {
            return false;
        }

        return (int) $user->etablissement_id === (int) $eleve->etablissement_id;
    }

    public function view(User $user, Eleve $eleve): bool
    {
        return $this->viewAsStudent($user, $eleve)
            || $this->viewAsParent($user, $eleve)
            || $this->viewAsStaff($user, $eleve);
    }
}
