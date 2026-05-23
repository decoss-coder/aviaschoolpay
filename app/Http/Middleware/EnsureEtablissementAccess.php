<?php

namespace App\Http\Middleware;

use App\Support\ApiEnvelope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque l'accès si l'établissement du compte est désactivé (sauf super admin).
 */
class EnsureEtablissementAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;
        if (! $etabId) {
            return $next($request);
        }

        $etab = \App\Models\Etablissement::find($etabId);

        if ($etab && ! $etab->actif) {
            if ($request->is('api/v1*') || $request->expectsJson()) {
                return ApiEnvelope::fail(
                    'L\'accès à votre établissement a été suspendu par Avia Technologie. Contactez le support.',
                    [],
                    403
                );
            }

            if (! $request->routeIs('acces.suspendu')) {
                return redirect()->route('acces.suspendu');
            }
        }

        if ($user && ! $user->actif) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors([
                'login' => 'Votre compte a été désactivé.',
            ]);
        }

        return $next($request);
    }
}
