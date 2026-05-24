<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
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
            'login'              => 'required|string',
            'code_etablissement' => 'nullable|string|max:30',
            'password'           => 'required|string',
        ]);

        $loginValue = trim($credentials['login']);
        $schoolCode = trim((string) ($credentials['code_etablissement'] ?? ''));

        // 1. Email ?
        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            $authData = ['email' => $loginValue, 'password' => $credentials['password']];
        }
        // 2. Matricule élève (DESPS ou interne) ?
        elseif ($matriculeResolution = $this->resolveMatriculeToUserId($loginValue, $schoolCode)) {
            if ($matriculeResolution['ambiguous']) {
                return back()->withErrors([
                    'login' => 'Ce matricule existe dans plusieurs écoles. Renseignez le code établissement pour identifier votre école.',
                ])->withInput();
            }

            if ($matriculeResolution['not_found_with_code']) {
                return back()->withErrors([
                    'login' => 'Aucun élève actif trouvé avec ce matricule dans l’établissement indiqué.',
                ])->withInput();
            }

            $authData = ['id' => $matriculeResolution['user_id'], 'password' => $credentials['password']];
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
            'login' => 'Identifiants incorrects. Vérifiez votre email/téléphone/matricule et mot de passe.',
        ])->onlyInput('login');
    }

    /**
     * Résout un matricule élève dans un environnement multi-écoles.
     *
     * Règle :
     * - si le même matricule existe dans plusieurs écoles, le code établissement devient obligatoire ;
     * - le couple fiable est donc : code établissement + matricule ;
     * - un matricule seul reste accepté seulement s'il identifie un seul compte élève actif.
     *
     * @return array{user_id:?int, ambiguous:bool, not_found_with_code:bool}|null
     */
    private function resolveMatriculeToUserId(string $login, ?string $schoolCode = null): ?array
    {
        $login = strtoupper(preg_replace('/\s+/', '', trim($login)));
        $schoolCode = strtoupper(trim((string) $schoolCode));

        $query = Eleve::query()
            ->with('etablissement:id,nom,code_desps,sigle')
            ->where(function ($q) use ($login) {
                $q->where('matricule_desps', $login)
                  ->orWhere('matricule_interne', $login);
            })
            ->where('actif', true)
            ->whereNotNull('user_id');

        if ($schoolCode !== '') {
            $query->whereHas('etablissement', function ($q) use ($schoolCode) {
                $q->whereRaw('UPPER(code_desps) = ?', [$schoolCode])
                  ->orWhereRaw('UPPER(sigle) = ?', [$schoolCode]);
            });
        }

        $matches = $query->limit(3)->get();

        if ($matches->isEmpty()) {
            return $schoolCode !== ''
                ? ['user_id' => null, 'ambiguous' => false, 'not_found_with_code' => true]
                : null;
        }

        if ($matches->count() > 1 && $schoolCode === '') {
            return ['user_id' => null, 'ambiguous' => true, 'not_found_with_code' => false];
        }

        return ['user_id' => (int) $matches->first()->user_id, 'ambiguous' => false, 'not_found_with_code' => false];
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
