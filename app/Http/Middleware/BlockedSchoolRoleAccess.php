<?php

namespace App\Http\Middleware;

use App\Services\Access\SchoolRoleAccessService;
use App\Support\ApiEnvelope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockedSchoolRoleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        // Ne jamais bloquer les pages indispensables au compte et à la sécurité.
        if ($routeName && (
            str_starts_with($routeName, 'password.') ||
            str_starts_with($routeName, 'access-control.') ||
            in_array($routeName, ['logout', 'dashboard', 'acces.suspendu'], true)
        )) {
            return $next($request);
        }

        if (SchoolRoleAccessService::routeIsBlockedFor($user, $routeName, $request->path())) {
            if ($request->is('api/v1*') || $request->expectsJson()) {
                return ApiEnvelope::fail('Accès bloqué par le fondateur de l’établissement.', [], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Accès bloqué par le fondateur de l’établissement.');
        }

        return $next($request);
    }
}
