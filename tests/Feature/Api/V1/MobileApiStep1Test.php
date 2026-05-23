<?php

namespace Tests\Feature\Api\V1;

use App\Models\GroupeScolaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileApiStep1Test extends TestCase
{
    use RefreshDatabase;

    private function seedEtablissementAndUser(string $role = 'directeur'): User
    {
        $groupe = GroupeScolaire::create([
            'nom' => 'Groupe Test',
            'actif' => true,
        ]);

        $etab = \App\Models\Etablissement::create([
            'groupe_scolaire_id' => $groupe->id,
            'nom' => 'École Test',
            'code_desps' => '639099',
            'type' => 'secondaire',
            'statut_juridique' => 'prive_laic',
            'adresse' => 'Adresse test',
            'ville' => 'Abidjan',
            'telephone' => '+2250100000001',
            'actif' => true,
        ]);

        return User::create([
            'etablissement_id' => $etab->id,
            'nom' => 'Admin',
            'prenom' => 'Test',
            'email' => 'admin@test.local',
            'telephone' => '+2250100000002',
            'password' => Hash::make('secret123'),
            'role' => $role,
            'actif' => true,
            'premiere_connexion' => false,
        ]);
    }

    public function test_login_returns_envelope_and_token(): void
    {
        $user = $this->seedEtablissementAndUser('directeur');

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user',
                    'role',
                    'profiles',
                    'ecoles_enseignant',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame('Bearer', $response->json('data.token_type'));
        $this->assertSame('directeur', $response->json('data.role'));
    }

    public function test_login_invalid_returns_envelope(): void
    {
        $this->seedEtablissementAndUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'wrong@wrong.local',
            'password' => 'bad',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_profile(): void
    {
        $user = $this->seedEtablissementAndUser('directeur');

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'secret123',
        ])->json('data.token');

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'directeur');
    }

    public function test_web_home_still_responds(): void
    {
        $r = $this->get('/');
        $this->assertContains($r->status(), [200, 302, 301]);
    }
}
