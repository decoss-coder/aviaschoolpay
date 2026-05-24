<?php
use App\Support\ApiEnvelope;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/eleve-statut-required.php',
        ],
        api: __DIR__.'/../routes/api_v1.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'ecole.active' => \App\Http\Middleware\EnsureActiveEtablissement::class,
            'api.teacher-school' => \App\Http\Middleware\EnsureApiTeacherActiveSchool::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'annee.courante' => \App\Http\Middleware\SetAnneeScolaireCourante::class,
            'etab.access' => \App\Http\Middleware\EnsureEtablissementAccess::class,
            'annee.readonly' => \App\Http\Middleware\EnforceAnneeReadOnly::class,
            'school.access' => \App\Http\Middleware\BlockedSchoolRoleAccess::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetAnneeScolaireCourante::class,
            \App\Http\Middleware\EnsureEtablissementAccess::class,
            \App\Http\Middleware\EnforceAnneeReadOnly::class,
            \App\Http\Middleware\BlockedSchoolRoleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/v1*')) {
                return ApiEnvelope::fail('Erreur de validation.', $e->errors(), 422);
            }
            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/v1*')) {
                return ApiEnvelope::fail('Non authentifié.', [], 401);
            }
            return null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/v1*')) {
                return ApiEnvelope::fail('Ressource introuvable.', [], 404);
            }
            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/v1*') && ! $e instanceof ValidationException && ! $e instanceof NotFoundHttpException) {
                $msg = $e->getMessage() ?: 'Erreur HTTP';
                return ApiEnvelope::fail($msg, [], $e->getStatusCode());
            }
            return null;
        });
    })->create();
