<?php

namespace Tests\Feature\Api\V1;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\GroupeScolaire;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Trimestre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileApiStep2TeacherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, token: string, classe: Classe, eleve: Eleve, trimestre: Trimestre, matiere: Matiere, typeEvalId: int}
     */
    private function seedTeacherWithClasse(): array
    {
        $groupe = GroupeScolaire::create(['nom' => 'Groupe T', 'actif' => true]);
        $etab = Etablissement::create([
            'groupe_scolaire_id' => $groupe->id,
            'nom' => 'École T',
            'code_desps' => '639099',
            'type' => 'secondaire',
            'statut_juridique' => 'prive_laic',
            'adresse' => 'Adr',
            'ville' => 'Abidjan',
            'telephone' => '+2250100000001',
            'actif' => true,
            'gps_latitude' => 5.32,
            'gps_longitude' => -4.02,
            'gps_rayon_metres' => 500,
        ]);

        $annee = AnneeScolaire::create([
            'etablissement_id' => $etab->id,
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-15',
            'en_cours' => true,
            'cloturee' => false,
        ]);

        $trimestre = Trimestre::create([
            'annee_scolaire_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'T1',
            'date_debut' => '2025-09-01',
            'date_fin' => '2025-12-31',
            'en_cours' => true,
        ]);

        $niveau = Niveau::create([
            'etablissement_id' => $etab->id,
            'code' => '6eme',
            'libelle' => 'Sixième',
            'cycle' => 'premier_cycle',
            'ordre' => 1,
            'frais_scolarite_defaut' => 0,
            'actif' => true,
        ]);

        $classe = Classe::create([
            'etablissement_id' => $etab->id,
            'annee_scolaire_id' => $annee->id,
            'niveau_id' => $niveau->id,
            'serie_id' => null,
            'nom' => '6e A',
            'capacite' => 40,
            'effectif' => 0,
            'active' => true,
        ]);

        $matiere = Matiere::create([
            'etablissement_id' => $etab->id,
            'nom' => 'Mathématiques',
            'code' => 'MATH',
            'coefficient_defaut' => 3,
            'active' => true,
        ]);

        $user = User::create([
            'etablissement_id' => $etab->id,
            'active_etablissement_id' => $etab->id,
            'nom' => 'PROF',
            'prenom' => 'Test',
            'email' => 'prof.step2@test.local',
            'telephone' => '+2250100000099',
            'password' => Hash::make('secret123'),
            'role' => 'enseignant',
            'actif' => true,
            'premiere_connexion' => false,
        ]);

        $ens = Enseignant::create([
            'user_id' => $user->id,
            'etablissement_id' => $etab->id,
            'nom' => 'PROF',
            'prenom' => 'Test',
            'sexe' => 'M',
            'telephone' => '+2250100000099',
            'statut' => 'titulaire',
            'actif' => true,
        ]);

        Affectation::create([
            'enseignant_id' => $ens->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'annee_scolaire_id' => $annee->id,
            'volume_horaire_hebdo' => 4,
            'est_professeur_principal' => false,
            'active' => true,
        ]);

        $eleve = Eleve::create([
            'etablissement_id' => $etab->id,
            'classe_id' => $classe->id,
            'nom' => 'ELEVE',
            'prenom' => 'Un',
            'sexe' => 'M',
            'matricule_interne' => 'E001',
            'statut_eleve' => Eleve::STATUT_ELEVE_AFFECTE,
            'actif' => true,
        ]);

        $typeEvalId = (int) DB::table('types_evaluation')->insertGetId([
            'etablissement_id' => $etab->id,
            'nom' => 'Devoir',
            'code' => 'devoir',
            'poids_pourcentage' => 100,
            'actif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'secret123',
        ])->json('data.token');

        return compact('user', 'token', 'classe', 'eleve', 'trimestre', 'matiere', 'typeEvalId');
    }

    public function test_teacher_routes_require_auth(): void
    {
        $this->getJson('/api/v1/teacher/dashboard')->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_non_teacher_cannot_access_teacher_api(): void
    {
        $groupe = GroupeScolaire::create(['nom' => 'G', 'actif' => true]);
        $etab = Etablissement::create([
            'groupe_scolaire_id' => $groupe->id,
            'nom' => 'E',
            'code_desps' => '639099',
            'type' => 'secondaire',
            'statut_juridique' => 'prive_laic',
            'adresse' => 'A',
            'ville' => 'X',
            'telephone' => '+2250100000001',
            'actif' => true,
        ]);
        $parent = User::create([
            'etablissement_id' => $etab->id,
            'nom' => 'P',
            'prenom' => 'A',
            'email' => 'parent@test.local',
            'telephone' => '+2250100000002',
            'password' => Hash::make('secret123'),
            'role' => 'parent',
            'actif' => true,
            'premiere_connexion' => false,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => $parent->email,
            'password' => 'secret123',
        ])->json('data.token');

        $this->withToken($token)->getJson('/api/v1/teacher/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_teacher_dashboard_and_classes_envelope(): void
    {
        $s = $this->seedTeacherWithClasse();

        $this->withToken($s['token'])->getJson('/api/v1/teacher/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'enseignant',
                    'etablissement',
                    'stats',
                    'pointage',
                ],
            ]);

        $this->withToken($s['token'])->getJson('/api/v1/teacher/classes')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.classes.0.id', $s['classe']->id);
    }

    public function test_teacher_cannot_access_other_classe_students(): void
    {
        $s = $this->seedTeacherWithClasse();

        $groupe = GroupeScolaire::create(['nom' => 'G2', 'actif' => true]);
        $etab2 = Etablissement::create([
            'groupe_scolaire_id' => $groupe->id,
            'nom' => 'E2',
            'code_desps' => '639100',
            'type' => 'secondaire',
            'statut_juridique' => 'prive_laic',
            'adresse' => 'A',
            'ville' => 'X',
            'telephone' => '+2250100000003',
            'actif' => true,
        ]);
        $annee2 = AnneeScolaire::create([
            'etablissement_id' => $etab2->id,
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-15',
            'en_cours' => true,
            'cloturee' => false,
        ]);
        $niveau2 = Niveau::create([
            'etablissement_id' => $etab2->id,
            'code' => '5eme',
            'libelle' => 'Cinquième',
            'cycle' => 'premier_cycle',
            'ordre' => 2,
            'frais_scolarite_defaut' => 0,
            'actif' => true,
        ]);
        $autreClasse = Classe::create([
            'etablissement_id' => $etab2->id,
            'annee_scolaire_id' => $annee2->id,
            'niveau_id' => $niveau2->id,
            'serie_id' => null,
            'nom' => '5e B',
            'capacite' => 40,
            'effectif' => 0,
            'active' => true,
        ]);

        $this->withToken($s['token'])->getJson('/api/v1/teacher/classes/'.$autreClasse->id.'/students')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_teacher_presences_bulk_and_get(): void
    {
        $s = $this->seedTeacherWithClasse();
        $date = '2026-05-14';

        $this->withToken($s['token'])->postJson(
            '/api/v1/teacher/classes/'.$s['classe']->id.'/presences',
            [
                'date' => $date,
                'presences' => [
                    ['eleve_id' => $s['eleve']->id, 'statut' => 'present', 'observation' => null],
                ],
            ]
        )->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.resume.total', 1)
            ->assertJsonPath('data.resume.presents', 1);

        $this->withToken($s['token'])->getJson(
            '/api/v1/teacher/classes/'.$s['classe']->id.'/presences?date='.$date
        )->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_teacher_evaluation_and_notes_flow(): void
    {
        $s = $this->seedTeacherWithClasse();

        $r = $this->withToken($s['token'])->postJson('/api/v1/teacher/evaluations', [
            'classe_id' => $s['classe']->id,
            'matiere_id' => $s['matiere']->id,
            'titre' => 'Interro 1',
            'type' => 'devoir',
            'date_evaluation' => '2026-05-14',
            'coefficient' => 1,
            'bareme' => 20,
            'trimestre_id' => $s['trimestre']->id,
        ]);

        $r->assertCreated()->assertJsonPath('success', true);
        $evalId = $r->json('data.id');

        $this->withToken($s['token'])->postJson(
            "/api/v1/teacher/evaluations/{$evalId}/notes",
            [
                'notes' => [
                    ['eleve_id' => $s['eleve']->id, 'note' => 14.5, 'observation' => 'Bien'],
                ],
            ]
        )->assertOk()->assertJsonPath('success', true);
    }
}
