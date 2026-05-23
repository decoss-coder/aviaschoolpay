<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Services\Eleve\EleveInscriptionService;
use App\Services\Finance\ParentScopeService;
use App\Services\Scolarite\AnneeScolaireService;
use App\Support\ApiEnvelope;
use App\Support\ApiUserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);

        $loginValue = trim($credentials['login']);
        $password = $credentials['password'];

        $user = $this->resolveUser($loginValue);

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Identifiants incorrects.'],
            ]);
        }

        if (!$user->actif) {
            return ApiEnvelope::fail('Compte désactivé.', [], 403);
        }

        if (! $user->premiere_connexion) {
            $user->update(['derniere_connexion' => now()]);
        }

        if ($user->isEnseignant()) {
            $this->ensureTeacherDefaultSchool($user);
        }

        if ($user->isParent()) {
            ParentScopeService::lierComptesOrphelins($user);
        }

        $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;
        if ($etabId) {
            AnneeScolaireService::initialiserContexte((int) $etabId);
        }

        $device = $credentials['device_name'] ?? 'mobile';
        $token = $user->createToken($device, ['role:'.$user->role])->plainTextToken;

        return ApiEnvelope::success(
            ApiUserProfile::loginResponse($user, $token),
            'Authentification réussie.'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiEnvelope::success(new \stdClass, 'Déconnecté.');
    }

    private function resolveUser(string $loginValue): ?User
    {
        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $loginValue)->first();
        }

        $matricule = strtoupper($loginValue);
        $eleve = Eleve::where(function ($q) use ($matricule) {
            $q->where('matricule_desps', $matricule)
                ->orWhere('matricule_interne', $matricule);
        })->whereNotNull('user_id')->first();

        if ($eleve?->user_id) {
            return User::find($eleve->user_id);
        }

        $normalized = EleveInscriptionService::normalize($loginValue);
        if (strlen($normalized) >= 8) {
            $variants = EleveInscriptionService::phoneVariants($loginValue);
            $user = User::query()
                ->whereIn('telephone', $variants)
                ->first();

            if ($user) {
                return $user;
            }

            return User::query()
                ->where('role', 'parent')
                ->get()
                ->first(fn (User $u) => EleveInscriptionService::phonesMatch($u->telephone, $loginValue));
        }

        return User::where('telephone', $loginValue)->first();
    }

    private function ensureTeacherDefaultSchool(User $user): void
    {
        $fiches = $user->enseignants()->where('actif', true)->get();
        if ($fiches->count() === 1) {
            $user->forceFill(['active_etablissement_id' => $fiches->first()->etablissement_id])->save();
        }
    }
}
