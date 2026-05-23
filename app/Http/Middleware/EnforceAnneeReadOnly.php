<?php

namespace App\Http\Middleware;

use App\Models\AnneeScolaire;
use App\Services\Scolarite\AnneeScolaireContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Active le mode LECTURE SEULE sur toute l'application si l'année scolaire
 * actuellement consultée est clôturée ou archivée.
 *
 * - Web : bloque toute requête mutante (POST/PUT/PATCH/DELETE) sauf whitelist
 *   et expose la variable de vue `$lectureSeule` (booléen).
 * - API mobile : retourne 423 LOCKED avec message clair.
 */
class EnforceAnneeReadOnly
{
    /**
     * Routes (par nom) toujours autorisées même en mode lecture seule.
     * → Login, déconnexion, navigation entre établissements, super_admin.
     */
    private const ROUTES_AUTORISEES = [
        'logout',
        'login',
        'password.update',
        'password.premiere.update',
        'admin.annees.activer',
        'admin.annees.consulter',
        'admin.annees.demander-restauration',
        'admin.annees.restaurer',
        'admin.annees.restaurer-fichier',
        'admin.annees.resynchroniser',
        'admin.annees.reimporter-edt',
        'admin.annees.store',          // créer nouvelle année (sortie du blocage)
        'admin.annees.cloturer',
        'admin.annees.index',
        'admin.platform.dashboard',
        'admin.platform.parametres',
        'admin.quitter-espace',
        'sigfne.parametrer',
    ];

    /** Routes GET de formulaires bloquées en lecture seule. */
    private const GET_FORM_SUFFIXES = ['.create', '.edit'];

    public function handle(Request $request, Closure $next): Response
    {
        $annee = AnneeScolaireContext::courante();
        $lectureSeule = $annee && $annee->estLectureSeule();

        // Partage la variable avec toutes les vues
        View::share('lectureSeule', $lectureSeule);
        View::share('anneeLectureSeule', $lectureSeule ? $annee : null);

        if (! $lectureSeule) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();

        // ─── Bloquer l'accès aux écrans de création / édition (GET) ───
        if ($request->isMethod('GET') && $this->estRouteFormulaireMutation($routeName)) {
            return $this->refuser($request, $annee);
        }

        // ─── Mode lecture seule actif ───
        $mutante = in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        if (! $mutante) {
            return $next($request);
        }

        // Whitelist : certaines actions restent autorisées
        if ($routeName && in_array($routeName, self::ROUTES_AUTORISEES, true)) {
            return $next($request);
        }

        return $this->refuser($request, $annee);
    }

    private function estRouteFormulaireMutation(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        if (in_array($routeName, self::ROUTES_AUTORISEES, true)) {
            return false;
        }

        foreach (self::GET_FORM_SUFFIXES as $suffix) {
            if (str_ends_with($routeName, $suffix)) {
                return true;
            }
        }

        return str_contains($routeName, '.import')
            || str_contains($routeName, 'import.');
    }

    private function refuser(Request $request, AnneeScolaire $annee): Response
    {
        $message = $annee->estArchiveConsultation()
            ? "Année « {$annee->libelle} » restaurée : mode consultation uniquement (aucun ajout, modification ni suppression). Activez une nouvelle année pour la saisie courante."
            : "Cette année scolaire ({$annee->libelle}) est clôturée ou archivée. Aucune modification n'est possible — créez ou activez une nouvelle année pour reprendre l'activité.";

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error'   => 'annee_cloturee_lecture_seule',
                'message' => $message,
                'annee'   => [
                    'id'       => $annee->id,
                    'libelle'  => $annee->libelle,
                    'cloturee' => (bool) $annee->cloturee,
                    'archivee' => (bool) $annee->archivee,
                ],
            ], 423);
        }

        if ($request->isMethod('GET')) {
            return redirect()
                ->route('dashboard')
                ->with('error', $message);
        }

        return back()->with('error', $message);
    }
}
