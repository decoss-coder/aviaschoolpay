<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->premiere_connexion && ! $request->routeIs(
            'password.premiere',
            'password.premiere.update',
            'logout'
        )) {
            return redirect()->route('password.premiere');
        }

        return $next($request);
    }
}
