<?php

namespace App\Http\Middleware;

use App\Services\Scolarite\AnneeScolaireContext;
use App\Services\Scolarite\AnneeScolaireDonneesService;
use App\Services\Scolarite\AnneeScolaireService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAnneeScolaireCourante
{
    /** @var array<int, true> */
    private static array $syncEffectue = [];

    public function handle(Request $request, Closure $next): Response
    {
        AnneeScolaireContext::clear();

        $user = $request->user();
        if ($user) {
            $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;
            if ($etabId) {
                $annee = AnneeScolaireService::initialiserContexte((int) $etabId);
                if ($annee?->estArchiveConsultation() && ! isset(self::$syncEffectue[$annee->id])) {
                    AnneeScolaireDonneesService::synchroniserDepuisInscriptions($annee);
                    self::$syncEffectue[$annee->id] = true;
                }
            }
        }

        $response = $next($request);

        if ($annee = AnneeScolaireContext::toApiPayload()) {
            $response->headers->set('X-Annee-Scolaire-Id', (string) $annee['id']);
            $response->headers->set('X-Annee-Scolaire-Libelle', $annee['libelle']);
        }

        return $response;
    }
}
