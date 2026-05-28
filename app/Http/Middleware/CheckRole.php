<?php

namespace App\Http\Middleware;

use App\Support\ApiEnvelope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        $userRole = strtolower(trim((string) ($user?->role ?? '')));
        $roles = array_values(array_unique(array_map(fn ($role) => strtolower(trim((string) $role)), $roles)));

        $isRhAffectations = $request->is('admin/rh/affectations')
            || $request->is('admin/rh/affectations/*')
            || str_starts_with((string) $request->route()?->getName(), 'admin.rh.affectations.');

        $canAccessRhAffectations = $isRhAffectations && in_array($userRole, [
            'super_admin',
            'fondateur',
            'directeur',
            'directeur_adjoint',
            'gestionnaire',
            'secretaire',
            'comptable',
            'censeur',
        ], true);

        // Le fondateur est l'autorité principale de son établissement :
        // il doit pouvoir entrer dans les espaces de direction, sauf blocage spécifique par school.access.
        $isFounderAllowedAsLeadership = $user
            && $userRole === 'fondateur'
            && array_intersect($roles, ['directeur', 'directeur_adjoint', 'gestionnaire', 'super_admin']);

        if (!$user || (!in_array($userRole, $roles, true) && !$isFounderAllowedAsLeadership && !$canAccessRhAffectations)) {
            if ($request->is('api/v1*') || $request->expectsJson()) {
                return ApiEnvelope::fail(
                    'Accès refusé. Rôle requis : '.implode(' ou ', $roles),
                    [],
                    403
                );
            }

            return redirect()->route('dashboard')
                ->with('error', 'Accès refusé. Rôle insuffisant.');
        }

        return $next($request);
    }
}
