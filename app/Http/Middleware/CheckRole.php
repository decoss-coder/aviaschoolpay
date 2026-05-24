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

        // Le fondateur est l'autorité principale de son établissement :
        // il doit pouvoir entrer dans les espaces de direction, sauf blocage spécifique par school.access.
        $isFounderAllowedAsLeadership = $user
            && $user->role === 'fondateur'
            && array_intersect($roles, ['directeur', 'directeur_adjoint', 'gestionnaire', 'super_admin']);

        if (!$user || (!in_array($user->role, $roles, true) && !$isFounderAllowedAsLeadership)) {
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
