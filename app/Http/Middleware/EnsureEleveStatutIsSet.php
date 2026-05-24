<?php

namespace App\Http\Middleware;

use App\Models\Eleve;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEleveStatutIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        if ($request->routeIs('eleves.statut-required.*')) {
            return $next($request);
        }

        $eleve = $this->resolveEleve($request);

        if (! $eleve) {
            return $next($request);
        }

        if ((int) $eleve->etablissement_id !== (int) $request->user()->etablissement_id) {
            return $next($request);
        }

        if ($this->hasValidStatut($eleve)) {
            return $next($request);
        }

        if ($request->isMethod('post') && $request->routeIs('paiements.store')) {
            return redirect()
                ->route('eleves.statut-required.edit', [
                    'eleve' => $eleve->id,
                    'redirect' => route('paiements.create', ['eleve_id' => $eleve->id]),
                ])
                ->withErrors([
                    'eleve_id' => 'Veuillez d’abord renseigner le statut AFF ou NAFF de cet élève avant d’enregistrer un paiement.',
                ]);
        }

        return redirect()->route('eleves.statut-required.edit', [
            'eleve' => $eleve->id,
            'redirect' => $request->fullUrl(),
        ]);
    }

    private function resolveEleve(Request $request): ?Eleve
    {
        $routeEleve = $request->route('eleve');

        if ($routeEleve instanceof Eleve) {
            return $routeEleve;
        }

        if (is_numeric($routeEleve)) {
            return Eleve::find((int) $routeEleve);
        }

        $eleveId = $request->query('eleve_id') ?: $request->input('eleve_id');

        if (! is_numeric($eleveId)) {
            return null;
        }

        return Eleve::find((int) $eleveId);
    }

    private function hasValidStatut(Eleve $eleve): bool
    {
        return in_array($eleve->statut_eleve, [
            Eleve::STATUT_ELEVE_AFFECTE,
            Eleve::STATUT_ELEVE_NON_AFFECTE,
        ], true);
    }
}
