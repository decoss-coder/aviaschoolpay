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
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
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
