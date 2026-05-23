<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Eleve\EleveInscriptionService;
use App\Support\ApiEnvelope;
use App\Support\ApiUserProfile;
use App\Support\DateNaissanceFr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * API mobile : création de compte élève (première connexion).
 *
 * Flow :
 *  1. POST /api/v1/auth/eleve/verifier-matricule  → vérifie le matricule
 *  2. POST /api/v1/auth/eleve/creer-compte        → crée le compte
 */
class StudentInscriptionApiController extends Controller
{
    /**
     * Étape 1 — Vérifier que le matricule existe et n'a pas encore de compte.
     */
    public function verifierMatricule(Request $request)
    {
        $request->validate([
            'matricule' => ['required', 'string', 'max:100'],
        ]);

        $matricule = trim($request->matricule);

        $eleve = Eleve::where('matricule_desps', $matricule)
            ->orWhere('matricule_interne', $matricule)
            ->first();

        if (! $eleve) {
            return ApiEnvelope::fail('Aucun élève trouvé avec ce matricule.', 404);
        }

        if ($eleve->user_id !== null) {
            return ApiEnvelope::fail('Un compte existe déjà pour ce matricule. Utilisez l\'écran de connexion.', 409);
        }

        return ApiEnvelope::success([
            'eleve_id'    => $eleve->id,
            'nom'         => $eleve->nom,
            'prenom'      => $eleve->prenom,
            'matricule'   => $matricule,
            'classe'      => $eleve->classe?->nom,
            'etablissement' => $eleve->etablissement?->nom,
            'date_naissance_requise' => (bool) $eleve->date_naissance,
            'telephone_parent_enregistre' => EleveInscriptionService::referenceParentPhones($eleve) !== [],
        ], 'Matricule vérifié. Créez votre mot de passe.');
    }

    /**
     * Étape 2 — Créer le compte avec date de naissance + mot de passe.
     */
    public function creerCompte(Request $request)
    {
        $request->validate([
            'eleve_id'              => ['required', 'integer', 'exists:eleves,id'],
            'date_naissance'        => ['nullable', 'date_format:Y-m-d'],
            'telephone_parent'      => ['required', 'string', 'min:8', 'max:20'],
            'password'              => ['required', 'confirmed', Password::min(6)],
            'device_name'           => ['nullable', 'string', 'max:100'],
        ]);

        $eleve = Eleve::findOrFail($request->eleve_id);

        if ($eleve->user_id !== null) {
            return ApiEnvelope::fail('Un compte existe déjà pour cet élève.', 409);
        }

        $dateSoumise = $request->filled('date_naissance')
            ? $request->date_naissance
            : DateNaissanceFr::parseText((string) $request->input('date_naissance_fr', ''));

        $checkDate = DateNaissanceFr::validateForEleve($eleve->date_naissance, $dateSoumise);
        if (! $checkDate['ok']) {
            return ApiEnvelope::fail($checkDate['message'] ?? 'Date de naissance invalide.', 422);
        }

        $checkTel = EleveInscriptionService::validateParentPhone($eleve, $request->telephone_parent);
        if (! $checkTel['ok']) {
            return ApiEnvelope::fail($checkTel['message'] ?? 'Numéro parent invalide.', 422);
        }

        EleveInscriptionService::persistParentPhone($eleve, $checkTel['normalized']);

        // Construire l'email de l'élève
        $email = $eleve->email
            ?: (str()->slug($eleve->matricule_desps ?? $eleve->matricule_interne ?? 'eleve') . '@eleve.aviaschoolpay.local');

        // S'assurer que l'email est unique
        if (User::where('email', $email)->exists()) {
            $email = str()->slug($eleve->matricule_interne ?? $eleve->matricule_desps ?? $eleve->id) . '_' . $eleve->id . '@eleve.aviaschoolpay.local';
        }

        $user = User::create([
            'etablissement_id'    => $eleve->etablissement_id,
            'nom'                 => $eleve->nom,
            'prenom'              => $eleve->prenom,
            'email'               => $email,
            'telephone'           => EleveInscriptionService::uniqueUserTelephone($eleve),
            'password'            => Hash::make($request->password),
            'role'                => 'eleve',
            'sexe'                => $eleve->sexe,
            'actif'               => true,
            'premiere_connexion'  => false,
            'derniere_connexion'  => now(),
        ]);

        // Lier l'élève au user
        $eleve->update(['user_id' => $user->id]);

        // Créer le token Sanctum
        $deviceName = $request->device_name ?? 'AviaApp-Mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return ApiEnvelope::success(
            ApiUserProfile::loginResponse($user->fresh(), $token),
            'Compte créé avec succès. Bienvenue !',
            201
        );
    }
}
