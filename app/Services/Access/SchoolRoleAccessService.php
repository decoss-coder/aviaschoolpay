<?php

namespace App\Services\Access;

use App\Models\SchoolRoleAccessBlock;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SchoolRoleAccessService
{
    /**
     * Catalogue des menus/routes que le fondateur peut bloquer par rôle.
     * La clé menu_key sert à masquer le menu ET à bloquer les routes correspondantes.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogue(): array
    {
        return [
            'dashboard' => [
                'label' => 'Tableau de bord',
                'section' => 'Principal',
                'routes' => ['dashboard'],
                'paths' => ['dashboard'],
            ],
            'eleves' => [
                'label' => 'Élèves',
                'section' => 'École',
                'routes' => ['eleves.*', 'eleves.import.*'],
                'paths' => ['eleves', 'eleves/*'],
            ],
            'enseignants' => [
                'label' => 'Enseignants',
                'section' => 'École',
                'routes' => ['enseignants.*'],
                'paths' => ['enseignants', 'enseignants/*'],
            ],
            'classes' => [
                'label' => 'Classes',
                'section' => 'École',
                'routes' => ['classes.*'],
                'paths' => ['classes', 'classes/*'],
            ],
            'affectations' => [
                'label' => 'Affectations',
                'section' => 'École',
                'routes' => ['admin.rh.affectations.*'],
                'paths' => ['admin/rh/affectations', 'admin/rh/affectations/*'],
            ],
            'pointage' => [
                'label' => 'Pointage enseignants',
                'section' => 'Vie scolaire',
                'routes' => ['pointage.*', 'pointages.*', 'admin.rh.pointages.*', 'alertes-pointage.*', 'admin.rh.alertes.*', 'admin.rh.qr-codes.*'],
                'paths' => ['pointage', 'pointage/*', 'pointages/*', 'admin/rh/pointages*', 'alertes-pointage*', 'admin/rh/alertes*', 'admin/rh/qr-codes*'],
            ],
            'presences_eleves' => [
                'label' => 'Présences élèves',
                'section' => 'Vie scolaire',
                'routes' => ['admin.rh.presences.*'],
                'paths' => ['admin/rh/presences*'],
            ],
            'notes' => [
                'label' => 'Notes & évaluations',
                'section' => 'Pédagogie',
                'routes' => ['notes.*', 'admin.rh.evaluation-system.*', 'admin.rh.sous-disciplines.*', 'admin.rh.moyennes-grille.*', 'admin.rh.bulletins.*'],
                'paths' => ['notes*', 'admin/rh/evaluation-system*', 'admin/rh/sous-disciplines*', 'admin/rh/moyennes-grille*', 'admin/rh/bulletins*'],
            ],
            'emploi_du_temps' => [
                'label' => 'Emploi du temps',
                'section' => 'Pédagogie',
                'routes' => ['emploi-du-temps.*'],
                'paths' => ['emploi-du-temps', 'emploi-du-temps/*'],
            ],
            'finances' => [
                'label' => 'Finances / recouvrement',
                'section' => 'Finances',
                'routes' => ['finances.*'],
                'paths' => ['finances', 'finances/*'],
            ],
            'comptabilite' => [
                'label' => 'Comptabilité / dépenses / trésorerie',
                'section' => 'Finances',
                'routes' => ['comptabilite.*', 'depenses.*', 'tresorerie.*', 'budgets.*'],
                'paths' => ['comptabilite*', 'depenses*', 'tresorerie*', 'budgets*'],
            ],
            'documents' => [
                'label' => 'Documents / rapports / fiches de paie',
                'section' => 'Documents',
                'routes' => ['documents.*', 'rapports.*', 'fiches-paie.*', 'fournitures.*'],
                'paths' => ['documents*', 'rapports*', 'fiches-paie*', 'fournitures*'],
            ],
            'communication' => [
                'label' => 'Communication / SMS / notifications',
                'section' => 'Communication',
                'routes' => ['communication.*', 'sms.*', 'notifications.*'],
                'paths' => ['communication*', 'sms*', 'notifications*'],
            ],
            'pilotage' => [
                'label' => 'Pilotage / IA / simulations',
                'section' => 'Pilotage',
                'routes' => ['rentabilite.*', 'simulations.*', 'cockpit.*', 'ia.*'],
                'paths' => ['rentabilite*', 'simulations*', 'cockpit*', 'ia*'],
            ],
            'administration' => [
                'label' => 'Années scolaires / administration école',
                'section' => 'Administration',
                'routes' => ['admin.annees.*'],
                'paths' => ['admin/annees*'],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function managedRoles(): array
    {
        return [
            'directeur' => 'Directeur',
            'directeur_adjoint' => 'Directeur adjoint',
            'educateur' => 'Éducateur',
            'enseignant' => 'Enseignant',
            'eleve' => 'Élève',
            'parent' => 'Parent',
            'gestionnaire' => 'Gestionnaire',
            'secretaire' => 'Secrétaire',
            'comptable' => 'Comptable',
            'censeur' => 'Censeur',
        ];
    }

    /** @return array<int, string> */
    public static function blockedKeysFor(User $user): array
    {
        if ($user->isSuperAdmin() || $user->isFondateur()) {
            return [];
        }

        $etabId = $user->ecoleActiveId();
        if (! $etabId || ! $user->role) {
            return [];
        }

        $cacheKey = "school_access_blocks:{$etabId}:{$user->role}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($etabId, $user) {
            return SchoolRoleAccessBlock::query()
                ->where('etablissement_id', $etabId)
                ->where('role', $user->role)
                ->pluck('menu_key')
                ->all();
        });
    }

    public static function isBlockedFor(User $user, string $menuKey): bool
    {
        return in_array($menuKey, self::blockedKeysFor($user), true);
    }

    public static function routeIsBlockedFor(User $user, ?string $routeName, string $path): bool
    {
        if ($user->isSuperAdmin() || $user->isFondateur()) {
            return false;
        }

        $blockedKeys = self::blockedKeysFor($user);
        if (empty($blockedKeys)) {
            return false;
        }

        $catalogue = self::catalogue();
        foreach ($blockedKeys as $key) {
            $entry = $catalogue[$key] ?? null;
            if (! $entry) {
                continue;
            }

            foreach (($entry['routes'] ?? []) as $pattern) {
                if ($routeName && Str::is($pattern, $routeName)) {
                    return true;
                }
            }

            foreach (($entry['paths'] ?? []) as $pattern) {
                if (Str::is($pattern, trim($path, '/'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array<int, string> $menuKeys */
    public static function replaceBlocks(int $etablissementId, string $role, array $menuKeys, ?int $actorId = null): void
    {
        $validKeys = array_keys(self::catalogue());
        $menuKeys = collect($menuKeys)->filter(fn ($key) => in_array($key, $validKeys, true))->unique()->values();

        SchoolRoleAccessBlock::query()
            ->where('etablissement_id', $etablissementId)
            ->where('role', $role)
            ->delete();

        foreach ($menuKeys as $menuKey) {
            SchoolRoleAccessBlock::create([
                'etablissement_id' => $etablissementId,
                'role' => $role,
                'menu_key' => $menuKey,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        Cache::forget("school_access_blocks:{$etablissementId}:{$role}");
    }
}
