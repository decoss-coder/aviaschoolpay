<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.change-password', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(4)],
        ]);

        $user = $request->user();
        $user->update([
            'password' => $data['password'],
            'premiere_connexion' => false,
            'derniere_connexion' => now(),
        ]);

        if ($user->isParent()) {
            return redirect()->route('mon-espace-parent.dashboard')
                ->with('success', 'Mot de passe mis à jour. Bienvenue sur votre espace parent.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Mot de passe mis à jour.');
    }

    public function updateApi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(4)],
        ]);

        $user = $request->user();
        $user->update([
            'password' => $data['password'],
            'premiere_connexion' => false,
            'derniere_connexion' => now(),
        ]);

        return ApiEnvelope::success([
            'premiere_connexion' => false,
        ], 'Mot de passe mis à jour.');
    }
}
