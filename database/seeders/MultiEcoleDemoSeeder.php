<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Trimestre;
use App\Models\TypeEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Démo multi-école : crée un 2ème établissement et rattache SEKA Etienne
 * (prof bivalent ANGL+EPS) à cette 2ème école.
 *
 * Permet de tester :
 *  - Login → écran de choix d'école
 *  - Switcher d'école depuis la sidebar
 *  - Affectations / élèves / évaluations / feuille de note scopées à l'école active
 *
 * Usage : php artisan db:seed --class=MultiEcoleDemoSeeder
 */
class MultiEcoleDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. 2ème Établissement ────────────────────────────────────────
        $etab2 = Etablissement::firstOrCreate(
            ['code_desps' => 'TEST002'],
            [
                'nom'              => 'Collège Privé Les Élites',
                'sigle'            => 'CPLE',
                'type'             => 'secondaire',
                'statut_juridique' => 'prive_laic',
                'adresse'          => 'Avenue des Écoles, Bouaké',
                'ville'            => 'Bouaké',
                'commune'          => 'Bouaké',
                'telephone'        => '+225 0700000099',
                'email'            => 'admin@elites.ci',
                'actif'            => true,
            ]
        );

        // ── 2. Année scolaire active sur cette école ─────────────────────
        $annee2 = AnneeScolaire::firstOrCreate(
            ['etablissement_id' => $etab2->id, 'libelle' => '2025-2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-06-30', 'en_cours' => true, 'cloturee' => false]
        );

        // ── 3. Trimestres ────────────────────────────────────────────────
        foreach ([
            ['numero' => 1, 'libelle' => 'Trimestre 1', 'date_debut' => '2025-09-01', 'date_fin' => '2025-12-15', 'en_cours' => true],
            ['numero' => 2, 'libelle' => 'Trimestre 2', 'date_debut' => '2026-01-05', 'date_fin' => '2026-03-31', 'en_cours' => false],
            ['numero' => 3, 'libelle' => 'Trimestre 3', 'date_debut' => '2026-04-01', 'date_fin' => '2026-06-30', 'en_cours' => false],
        ] as $t) {
            Trimestre::firstOrCreate(
                ['annee_scolaire_id' => $annee2->id, 'numero' => $t['numero']],
                $t
            );
        }

        // ── 4. Types d'évaluation ────────────────────────────────────────
        foreach ([
            ['code' => 'DEVOIR',  'nom' => 'Devoir surveillé', 'poids' => 30],
            ['code' => 'INTERRO', 'nom' => 'Interrogation',     'poids' => 20],
            ['code' => 'COMPO',   'nom' => 'Composition',       'poids' => 50],
        ] as $t) {
            TypeEvaluation::firstOrCreate(
                ['etablissement_id' => $etab2->id, 'code' => $t['code']],
                ['nom' => $t['nom'], 'poids_pourcentage' => $t['poids'], 'actif' => true]
            );
        }

        // ── 5. Matières (réutilise codes standards) ──────────────────────
        $matieres = [];
        foreach ([
            ['code' => 'ANGL', 'nom' => 'Anglais',           'coef' => 2],
            ['code' => 'EPS',  'nom' => 'Éducation physique', 'coef' => 1],
            ['code' => 'MATH', 'nom' => 'Mathématiques',      'coef' => 4],
        ] as $m) {
            $matieres[$m['code']] = Matiere::firstOrCreate(
                ['etablissement_id' => $etab2->id, 'code' => $m['code']],
                ['nom' => $m['nom'], 'coefficient_defaut' => $m['coef'], 'active' => true]
            );
        }

        // ── 6. Niveaux + Classes minimales ───────────────────────────────
        $niveau = Niveau::firstOrCreate(
            ['etablissement_id' => $etab2->id, 'code' => '6e'],
            ['libelle' => '6ème', 'ordre' => 1, 'cycle' => 'premier_cycle']
        );

        $classe = Classe::firstOrCreate(
            ['etablissement_id' => $etab2->id, 'annee_scolaire_id' => $annee2->id, 'nom' => '6e A'],
            ['niveau_id' => $niveau->id, 'capacite' => 40, 'effectif' => 0, 'active' => true]
        );

        // ── 7. Rattache SEKA Etienne à cette 2ème école ──────────────────
        $userSeka = User::where('email', 'seka.etienne@test.ci')->first();

        if (!$userSeka) {
            $this->command->warn('Utilisateur seka.etienne@test.ci introuvable — lance d\'abord DatabaseSeeder.');
            return;
        }

        $ensSeka2 = Enseignant::firstOrCreate(
            ['user_id' => $userSeka->id, 'etablissement_id' => $etab2->id],
            [
                'matricule_mena' => null,
                'nom'            => 'SEKA',
                'prenom'         => 'Etienne',
                'sexe'           => 'M',
                'telephone'      => '0700000033',
                'statut'         => 'vacataire',
                'specialite'     => 'Anglais/EPS',
                'actif'          => true,
            ]
        );

        // ── 8. Affectation SEKA → 6e A pour ANGL ─────────────────────────
        Affectation::firstOrCreate(
            [
                'enseignant_id'     => $ensSeka2->id,
                'classe_id'         => $classe->id,
                'matiere_id'        => $matieres['ANGL']->id,
                'annee_scolaire_id' => $annee2->id,
            ],
            ['volume_horaire_hebdo' => 3, 'active' => true]
        );

        $this->command->info("✅ 2ème école créée : {$etab2->nom}");
        $this->command->info("✅ SEKA Etienne est désormais affecté à 2 écoles.");
        $this->command->info("→ Connexion : seka.etienne@test.ci / password");
        $this->command->info("→ Après login : écran de choix d'école apparaîtra.");
    }
}
