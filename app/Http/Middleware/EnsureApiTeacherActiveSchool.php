<?php

namespace App\Http\Middleware;

use App\Support\ApiEnvelope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pour les enseignants multi-écoles : exige active_etablissement_id avant les routes métier.
 */
class EnsureApiTeacherActiveSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->isEnseignant()) {
            return $next($request);
        }

        $fiches = $user->enseignants()->where('actif', true)->get();
        if ($fiches->count() <= 1) {
            return $next($request);
        }

        if (!$user->active_etablissement_id) {
            return ApiEnvelope::fail(
                'Sélectionnez un établissement actif (PUT /api/v1/context/etablissement).',
                [
                    'code' => 'NEEDS_ETABLISSEMENT',
                    'ecoles' => $fiches->map(fn ($e) => [
                        'etablissement_id' => $e->etablissement_id,
                        'enseignant_id' => $e->id,
                    ])->values()->all(),
                ],
                409
            );
        }

        if (!$fiches->contains('etablissement_id', (int) $user->active_etablissement_id)) {
            return ApiEnvelope::fail('Établissement actif invalide.', [], 422);
        }

        return $next($request);
    }
}
