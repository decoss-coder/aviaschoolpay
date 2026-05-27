<?php

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('avia:seed-edt-demo {--etablissement-id=} {--annee-id=}', function () {
    $etabId = $this->option('etablissement-id') ? (int) $this->option('etablissement-id') : null;
    $anneeId = $this->option('annee-id') ? (int) $this->option('annee-id') : null;

    $etab = $etabId
        ? Etablissement::find($etabId)
        : Etablissement::query()->orderBy('id')->first();

    if (! $etab) {
        $this->error('Aucun établissement trouvé.');
        return self::FAILURE;
    }

    $annee = $anneeId
        ? AnneeScolaire::where('etablissement_id', $etab->id)->find($anneeId)
        : AnneeScolaire::where('etablissement_id', $etab->id)->where('en_cours', true)->orderByDesc('id')->first();

    if (! $annee) {
        $annee = AnneeScolaire::where('etablissement_id', $etab->id)->orderByDesc('id')->first();
    }

    if (! $annee) {
        $this->error('Aucune année scolaire trouvée pour cet établissement.');
        return self::FAILURE;
    }

    $this->info('Insertion données EDT pour : '.$etab->nom.' — '.$annee->libelle);

    $result = DB::transaction(function () use ($etab, $annee) {
        $levels = [
            ['code' => '6E',  'libelle' => 'Sixième',   'cycle' => 'premier_cycle', 'ordre' => 10],
            ['code' => '5E',  'libelle' => 'Cinquième', 'cycle' => 'premier_cycle', 'ordre' => 20],
            ['code' => '4E',  'libelle' => 'Quatrième', 'cycle' => 'premier_cycle', 'ordre' => 30],
            ['code' => '3E',  'libelle' => 'Troisième', 'cycle' => 'premier_cycle', 'ordre' => 40],
            ['code' => '2NDE','libelle' => 'Seconde',   'cycle' => 'second_cycle',  'ordre' => 50],
            ['code' => '1ERE','libelle' => 'Première',  'cycle' => 'second_cycle',  'ordre' => 60],
            ['code' => 'TLE', 'libelle' => 'Terminale', 'cycle' => 'second_cycle',  'ordre' => 70],
        ];

        $niveauByCode = [];
        foreach ($levels as $level) {
            $niveau = Niveau::updateOrCreate(
                ['etablissement_id' => $etab->id, 'code' => $level['code']],
                $level + [
                    'etablissement_id' => $etab->id,
                    'frais_scolarite_defaut' => 0,
                    'frais_inscription_defaut' => 0,
                    'frais_reinscription_defaut' => 0,
                    'actif' => true,
                ]
            );
            $niveauByCode[$level['code']] = $niveau;
        }

        $matieresData = [
            ['code' => 'FR', 'nom' => 'Français', 'coef' => 3, 'groupe' => 'lettres'],
            ['code' => 'MATH', 'nom' => 'Mathématiques', 'coef' => 3, 'groupe' => 'sciences'],
            ['code' => 'SVT', 'nom' => 'Sciences de la Vie et de la Terre', 'coef' => 2, 'groupe' => 'sciences'],
            ['code' => 'PC', 'nom' => 'Physique-Chimie', 'coef' => 2, 'groupe' => 'sciences'],
            ['code' => 'HG', 'nom' => 'Histoire-Géographie', 'coef' => 2, 'groupe' => 'lettres'],
            ['code' => 'ANG', 'nom' => 'Anglais', 'coef' => 2, 'groupe' => 'lettres'],
            ['code' => 'ESP', 'nom' => 'Espagnol', 'coef' => 1, 'groupe' => 'lettres'],
            ['code' => 'EPS', 'nom' => 'Éducation Physique et Sportive', 'coef' => 1, 'groupe' => 'autres'],
            ['code' => 'EDHC', 'nom' => 'EDHC', 'coef' => 1, 'groupe' => 'autres'],
            ['code' => 'PHILO', 'nom' => 'Philosophie', 'coef' => 2, 'groupe' => 'lettres'],
        ];

        $matByCode = [];
        foreach ($matieresData as $m) {
            $matiere = Matiere::updateOrCreate(
                ['etablissement_id' => $etab->id, 'code' => $m['code']],
                [
                    'etablissement_id' => $etab->id,
                    'parent_matiere_id' => null,
                    'nom' => $m['nom'],
                    'code' => $m['code'],
                    'coefficient_defaut' => $m['coef'],
                    'poids_dans_parent' => null,
                    'ordre' => count($matByCode) + 1,
                    'groupe' => $m['groupe'],
                    'active' => true,
                ]
            );
            $matByCode[$m['code']] = $matiere;
        }

        $teacherRows = [
            ['nom' => 'Kouadio', 'prenom' => 'Jean', 'sexe' => 'M', 'statut' => 'titulaire', 'disciplines' => ['FR']],
            ['nom' => 'Traoré', 'prenom' => 'Mariam', 'sexe' => 'F', 'statut' => 'titulaire', 'disciplines' => ['MATH']],
            ['nom' => 'Koné', 'prenom' => 'Ibrahim', 'sexe' => 'M', 'statut' => 'titulaire', 'disciplines' => ['SVT', 'PC']],
            ['nom' => 'Bamba', 'prenom' => 'Awa', 'sexe' => 'F', 'statut' => 'titulaire', 'disciplines' => ['HG', 'EDHC']],
            ['nom' => 'Yao', 'prenom' => 'Serge', 'sexe' => 'M', 'statut' => 'titulaire', 'disciplines' => ['ANG', 'ESP']],
            ['nom' => 'Coulibaly', 'prenom' => 'Nadia', 'sexe' => 'F', 'statut' => 'contractuel', 'disciplines' => ['EPS']],
            ['nom' => 'Nguessan', 'prenom' => 'Patrick', 'sexe' => 'M', 'statut' => 'vacataire', 'disciplines' => ['PHILO']],
            ['nom' => 'Ouattara', 'prenom' => 'Fatou', 'sexe' => 'F', 'statut' => 'vacataire', 'disciplines' => ['FR']],
            ['nom' => 'Koffi', 'prenom' => 'Armand', 'sexe' => 'M', 'statut' => 'vacataire', 'disciplines' => ['MATH']],
            ['nom' => 'Diabaté', 'prenom' => 'Aminata', 'sexe' => 'F', 'statut' => 'vacataire', 'disciplines' => ['SVT']],
        ];

        $teachers = [];
        foreach ($teacherRows as $i => $t) {
            $email = 'enseignant'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT).'@aviaschoolpay.local';
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'etablissement_id' => $etab->id,
                    'active_etablissement_id' => $etab->id,
                    'nom' => $t['nom'],
                    'prenom' => $t['prenom'],
                    'telephone' => '07000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'role' => 'enseignant',
                    'sexe' => $t['sexe'],
                    'actif' => true,
                    'premiere_connexion' => true,
                ]
            );

            $enseignant = Enseignant::updateOrCreate(
                ['etablissement_id' => $etab->id, 'matricule_mena' => 'MENA-DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $user->id,
                    'etablissement_id' => $etab->id,
                    'matricule_mena' => 'MENA-DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'nom' => $t['nom'],
                    'prenom' => $t['prenom'],
                    'sexe' => $t['sexe'],
                    'telephone' => '07000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'email' => $email,
                    'specialite' => implode(' / ', $t['disciplines']),
                    'statut' => $t['statut'],
                    'type_remuneration' => $t['statut'] === 'vacataire' ? 'horaire' : 'mensuel',
                    'taux_horaire' => $t['statut'] === 'vacataire' ? 2500 : 0,
                    'salaire_base' => $t['statut'] === 'vacataire' ? 0 : 150000,
                    'heures_contractuelles_mois' => 72,
                    'actif' => true,
                ]
            );

            foreach ($t['disciplines'] as $code) {
                $teachers[$code][] = $enseignant;
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            Salle::updateOrCreate(
                ['etablissement_id' => $etab->id, 'nom' => 'Salle '.$i],
                [
                    'etablissement_id' => $etab->id,
                    'nom' => 'Salle '.$i,
                    'batiment' => 'Bloc A',
                    'capacite' => 45,
                    'type' => 'classe',
                    'active' => true,
                ]
            );
        }

        $classRows = [
            ['6E', '6ème 1'], ['6E', '6ème 2'],
            ['5E', '5ème 1'], ['5E', '5ème 2'],
            ['4E', '4ème 1'], ['4E', '4ème 2'],
            ['3E', '3ème 1'], ['3E', '3ème 2'],
            ['2NDE', '2nde C 1'], ['2NDE', '2nde C 2'], ['2NDE', '2nde A1'], ['2NDE', '2nde A2'],
            ['1ERE', '1ère D1'], ['1ERE', '1ère D2'], ['1ERE', '1ère C1'], ['1ERE', '1ère C2'], ['1ERE', '1ère A1'], ['1ERE', '1ère A2'],
            ['TLE', 'Tle D1'], ['TLE', 'Tle D2'], ['TLE', 'Tle C1'], ['TLE', 'Tle C2'], ['TLE', 'Tle A1'], ['TLE', 'Tle A2'],
        ];

        $classes = [];
        foreach ($classRows as [$niveauCode, $nom]) {
            $classes[] = Classe::updateOrCreate(
                ['etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id, 'nom' => $nom],
                [
                    'etablissement_id' => $etab->id,
                    'annee_scolaire_id' => $annee->id,
                    'niveau_id' => $niveauByCode[$niveauCode]?->id,
                    'nom' => $nom,
                    'capacite' => 50,
                    'effectif' => 0,
                    'scolarite_annuelle' => 0,
                    'frais_inscription' => 0,
                    'frais_reinscription' => 0,
                    'active' => true,
                ]
            );
        }

        $matierePlan = ['FR' => 4, 'MATH' => 4, 'SVT' => 2, 'PC' => 2, 'HG' => 2, 'ANG' => 2, 'ESP' => 2, 'EPS' => 2, 'EDHC' => 1];
        $secondaryExtras = ['PHILO' => 2];
        $affectationCount = 0;

        foreach ($classes as $classe) {
            $plan = $matierePlan;
            if (! str_starts_with($classe->nom, '6') && ! str_starts_with($classe->nom, '5') && ! str_starts_with($classe->nom, '4') && ! str_starts_with($classe->nom, '3')) {
                $plan = $plan + $secondaryExtras;
            }

            foreach ($plan as $code => $volume) {
                $pool = $teachers[$code] ?? [];
                if (empty($pool) || empty($matByCode[$code])) {
                    continue;
                }

                $teacher = $pool[$affectationCount % count($pool)];
                Affectation::updateOrCreate(
                    [
                        'enseignant_id' => $teacher->id,
                        'classe_id' => $classe->id,
                        'matiere_id' => $matByCode[$code]->id,
                        'annee_scolaire_id' => $annee->id,
                    ],
                    [
                        'enseignant_id' => $teacher->id,
                        'classe_id' => $classe->id,
                        'matiere_id' => $matByCode[$code]->id,
                        'annee_scolaire_id' => $annee->id,
                        'volume_horaire_hebdo' => $volume,
                        'est_professeur_principal' => $code === 'FR',
                        'active' => true,
                    ]
                );
                $affectationCount++;
            }
        }

        return [
            'teachers' => count($teacherRows),
            'vacataires' => collect($teacherRows)->where('statut', 'vacataire')->count(),
            'bivalents' => collect($teacherRows)->filter(fn ($t) => count($t['disciplines']) >= 2)->count(),
            'salles' => 10,
            'classes' => count($classRows),
            'affectations' => $affectationCount,
        ];
    });

    $this->info('Terminé.');
    $this->line('Enseignants : '.$result['teachers']);
    $this->line('Vacataires : '.$result['vacataires']);
    $this->line('Bivalents : '.$result['bivalents']);
    $this->line('Salles : '.$result['salles']);
    $this->line('Classes : '.$result['classes']);
    $this->line('Affectations : '.$result['affectations']);

    return self::SUCCESS;
})->purpose('Seed EDT demo data: enseignants, salles, classes et affectations');
