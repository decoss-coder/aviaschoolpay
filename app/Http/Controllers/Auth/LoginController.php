<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Finance\ParentScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Traiter la connexion
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginValue = trim($credentials['login']);

        // 1. Email ?
        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            $authData = ['email' => $loginValue, 'password' => $credentials['password']];
        }
        // 2. Matricule élève (DESPS ou interne) ?
        elseif ($userId = $this->resolveMatriculeToUserId($loginValue)) {
            $authData = ['id' => $userId, 'password' => $credentials['password']];
        }
        // 3. Téléphone par défaut
        else {
            $authData = ['telephone' => $loginValue, 'password' => $credentials['password']];
        }

        if (Auth::attempt($authData, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->premiere_connexion) {
                return redirect()->route('password.premiere');
            }

            $user->update(['derniere_connexion' => now()]);

            if ($user->role === 'enseignant') {
                // Si plusieurs écoles → forcer le choix
                $nbEcoles = $user->enseignants()->where('actif', true)->count();
                if ($nbEcoles > 1) {
                    return redirect()->route('ecole.switcher.index');
                }
                return redirect()->intended('/mon-espace');
            }

            if ($user->role === 'eleve') {
                return redirect()->intended('/mon-espace-eleve');
            }

            if ($user->role === 'parent') {
                ParentScopeService::lierComptesOrphelins($user);

                return redirect()->intended('/mon-espace-parent');
            }

            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('admin.platform.dashboard'));
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'login' => 'Identifiants incorrects. Vérifiez votre email/téléphone et mot de passe.',
        ])->onlyInput('login');
    }

    /**
     * Si le login ressemble à un matricule élève DESPS ou interne, retourne le user_id lié.
     */
    private function resolveMatriculeToUserId(string $login): ?int
    {
        $login = strtoupper($login);

        $eleve = \App\Models\Eleve::where('matricule_desps', $login)
            ->orWhere('matricule_interne', $login)
            ->whereNotNull('user_id')
            ->first();

        return $eleve?->user_id;
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('active_etablissement_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}