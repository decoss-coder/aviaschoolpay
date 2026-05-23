<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour les enseignants : garantit qu'une école est active en session.
 *
 *  - 0 fiche enseignant   → 403
 *  - 1 fiche enseignant   → définit automatiquement l'école en session
 *  - ≥ 2 fiches           → redirige vers le sélecteur d'école (sauf si déjà choisie)
 */
class EnsureActiveEtablissement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->isEnseignant()) {
            return $next($request);
        }

        $enseignants = $user->enseignants()->where('actif', true)->get();

        if ($enseignants->isEmpty()) {
            abort(403, "Aucune fiche enseignant active pour ce compte.");
        }

        $sessionEtabId = session('active_etablissement_id');
        $valide = $sessionEtabId && $enseignants->contains('etablissement_id', (int) $sessionEtabId);

        if ($valide) {
            return $next($request);
        }

        if ($enseignants->count() === 1) {
            // Auto-sélection : un seul établissement
            session(['active_etablissement_id' => $enseignants->first()->etablissement_id]);
            return $next($request);
        }

        // Plusieurs écoles → forcer le choix (sauf si on est déjà sur la page de choix)
        if ($request->routeIs('ecole.switcher.*')) {
            return $next($request);
        }

        return redirect()->route('ecole.switcher.index');
    }
}
