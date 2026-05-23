<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Eleve\EleveInscriptionService;
use App\Support\DateNaissanceFr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Inscription / activation de compte élève via matricule DESPS.
 *
 * Workflow :
 *   1. Élève entre son matricule_desps
 *   2. On vérifie qu'il existe dans la base et qu'aucun User n'y est encore lié
 *   3. Il choisit un mot de passe + confirme date de naissance (sécurité simple)
 *   4. Un User role=eleve est créé et lié à l'Eleve, puis connecté
 */
class EleveAuthController extends Controller
{
    /**
     * Étape 1 : Formulaire — saisie matricule pour vérifier l'éligibilité.
     */
    public function showCheckForm()
    {
        return view('auth.inscription-eleve.check');
    }

    /**
     * Étape 1 (POST) : Vérifie le matricule et redirige vers la création du mot de passe.
     */
    public function check(Request $request)
    {
        $data = $request->validate([
            'matricule' => 'required|string|max:30',
        ]);

        $matricule = strtoupper(trim($data['matricule']));

        $eleve = Eleve::where(function ($q) use ($matricule) {
            $q->where('matricule_desps', $matricule)
              ->orWhere('matricule_interne', $matricule);
        })->where('actif', true)->first();

        if (!$eleve) {
            return back()->withErrors([
                'matricule' => 'Aucun élève trouvé avec ce matricule. Contactez votre établissement.',
            ])->withInput();
        }

        // user_id renseigné mais lien obsolète (ex. import qui a mis l'admin par erreur)
        // → on autorise l'activation si le user lié n'existe pas ou n'a pas le rôle élève.
        if ($eleve->user_id) {
            $linkedUser = User::find($eleve->user_id);
            $isValidEleveUser = $linkedUser && $linkedUser->role === 'eleve';

            if ($isValidEleveUser) {
                return back()->withErrors([
                    'matricule' => 'Un compte existe déjà pour cet élève. Connectez-vous avec votre matricule et votre mot de passe.',
                ])->withInput();
            }
            // Sinon : on remet user_id à null pour permettre l'activation propre
            $eleve->update(['user_id' => null]);
        }

        // Stocker temporairement l'éligibilité en session
        $token = Str::random(40);
        $request->session()->put("inscription_eleve.{$token}", [
            'eleve_id' => $eleve->id,
            'expires'  => now()->addMinutes(15)->timestamp,
        ]);

        return redirect()->route('inscription.eleve.password', ['token' => $token]);
    }

    /**
     * Étape 2 : Formulaire mot de passe + date de naissance pour confirmer identité.
     */
    public function showPasswordForm(Request $request, string $token)
    {
        $data = $request->session()->get("inscription_eleve.{$token}");
        abort_if(!$data || $data['expires'] < now()->timestamp, 410, 'Le lien a expiré, recommencez l\'inscription.');

        $eleve = Eleve::findOrFail($data['eleve_id']);
        abort_if($eleve->user_id, 410);

        $dateNaissanceRequise = (bool) $eleve->date_naissance;

        return view('auth.inscription-eleve.password', compact('eleve', 'token', 'dateNaissanceRequise'));
    }

    /**
     * Étape 2 (POST) : Crée le compte User et connecte l'élève.
     */
    public function createAccount(Request $request, string $token)
    {
        $session = $request->session()->get("inscription_eleve.{$token}");
        abort_if(!$session || $session['expires'] < now()->timestamp, 410, 'Lien expiré.');

        $eleve = Eleve::findOrFail($session['eleve_id']);
        abort_if($eleve->user_id, 410);

        $data = $request->validate([
            'date_naissance'   => ['nullable', 'date'],
            'telephone_parent' => ['required', 'string', 'min:8', 'max:20'],
            'password'         => ['required', 'confirmed', Password::min(6)],
        ]);

        $dateSoumise = DateNaissanceFr::fromRequest($request);
        $checkDate = DateNaissanceFr::validateForEleve($eleve->date_naissance, $dateSoumise);
        if (! $checkDate['ok']) {
            return back()->withErrors([
                'date_naissance' => $checkDate['message'],
            ])->withInput();
        }

        $checkTel = EleveInscriptionService::validateParentPhone($eleve, $data['telephone_parent']);
        if (! $checkTel['ok']) {
            return back()->withErrors([
                'telephone_parent' => $checkTel['message'],
            ])->withInput();
        }

        EleveInscriptionService::persistParentPhone($eleve, $checkTel['normalized']);

        // Email synthétique si absent (utilisé pour la colonne email obligatoire/uuid)
        $matricule = $eleve->matricule_desps ?: $eleve->matricule_interne;
        $email = $eleve->email ?: strtolower($matricule) . '@eleve.aviaschoolpay.local';

        // Vérifier qu'aucun user ne squatte déjà cet email
        if (User::where('email', $email)->exists()) {
            $email = strtolower($matricule) . '.' . Str::random(6) . '@eleve.aviaschoolpay.local';
        }

        $user = User::create([
            'etablissement_id'   => $eleve->etablissement_id,
            'nom'                => $eleve->nom,
            'prenom'             => $eleve->prenom,
            'email'              => $email,
            'telephone'          => EleveInscriptionService::uniqueUserTelephone($eleve),
            'password'           => Hash::make($data['password']),
            'role'               => 'eleve',
            'sexe'               => $eleve->sexe,
            'actif'              => true,
            'premiere_connexion' => false,
            'derniere_connexion' => now(),
        ]);

        $eleve->update(['user_id' => $user->id]);

        $request->session()->forget("inscription_eleve.{$token}");

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('mon-espace-eleve.dashboard')
            ->with('success', "Compte créé avec succès. Bienvenue {$eleve->prenom} !");
    }
}
