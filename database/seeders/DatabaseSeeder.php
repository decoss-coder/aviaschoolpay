<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EdtClassePlageHoraire;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Établissement ─────────────────────────────────────────────
        $etab = Etablissement::firstOrCreate(
            ['code_desps' => 'TEST001'],
            [
                'nom'              => 'Lycée Test AviaSchoolPay',
                'sigle'            => 'LTAP',
                'type'             => 'lycee',
                'statut_juridique' => 'prive_laic',
                'adresse'          => '12 Rue des Tests, Abidjan',
                'ville'            => 'Abidjan',
                'commune'          => 'Cocody',
                'telephone'        => '+225 0700000001',
                'email'            => 'admin@test.ci',
                'actif'            => true,
            ]
        );

        // ── 2. Année scolaire active ──────────────────────────────────────
        $annee = AnneeScolaire::firstOrCreate(
            ['etablissement_id' => $etab->id, 'libelle' => '2025-2026'],
            [
                'date_debut' => '2025-09-01',
                'date_fin'   => '2026-06-30',
                'en_cours'   => true,
                'cloturee'   => false,
            ]
        );

        // ── 3. Super admin ───────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'superadmin@test.ci'],
            [
                'etablissement_id'   => $etab->id,
                'nom'                => 'ADMIN',
                'prenom'             => 'Super',
                'telephone'          => '0700000000',
                'password'           => Hash::make('password'),
                'role'               => 'super_admin',
                'actif'              => true,
                'premiere_connexion' => false,
            ]
        );

        // ── 4. Directeur ─────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'directeur@test.ci'],
            [
                'etablissement_id'   => $etab->id,
                'nom'                => 'KOUASSI',
                'prenom'             => 'Jean',
                'telephone'          => '0700000001',
                'password'           => Hash::make('password'),
                'role'               => 'directeur',
                'actif'              => true,
                'premiere_connexion' => false,
            ]
        );

        // ── 5. Niveaux ───────────────────────────────────────────────────
        $niveauxDef = [
            ['6EME',  '6ème',  'premier_cycle', 1],
            ['5EME',  '5ème',  'premier_cycle', 2],
            ['4EME',  '4ème',  'premier_cycle', 3],
            ['3EME',  '3ème',  'premier_cycle', 4],
            ['2NDE',  '2nde',  'second_cycle',  5],
            ['1ERE',  '1ère',  'second_cycle',  6],
            ['TLE',   'Tle',   'second_cycle',  7],
        ];

        $niveaux = [];
        foreach ($niveauxDef as [$code, $lib, $cycle, $ordre]) {
            $niveaux[$code] = Niveau::firstOrCreate(
                ['code' => $code, 'etablissement_id' => $etab->id],
                ['libelle' => $lib, 'cycle' => $cycle, 'ordre' => $ordre, 'frais_scolarite_defaut' => 0]
            );
        }

        // ── 6. Classes (3 par niveau) ─────────────────────────────────────
        $classesData = [
            '6EME' => ['6ème 1', '6ème 2', '6ème 3'],
            '5EME' => ['5ème 1', '5ème 2', '5ème 3'],
            '4EME' => ['4ème 1', '4ème 2', '4ème 3'],
            '3EME' => ['3ème 1', '3ème 2', '3ème 3'],
            '2NDE' => ['2nde A', '2nde B', '2nde C'],
            '1ERE' => ['1ère A', '1ère C', '1ère D'],
            'TLE'  => ['Tle A', 'Tle C', 'Tle D'],
        ];

        $classes = [];
        foreach ($classesData as $niveauCode => $noms) {
            foreach ($noms as $nom) {
                $classes[$nom] = Classe::firstOrCreate(
                    ['nom' => $nom, 'etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id],
                    ['niveau_id' => $niveaux[$niveauCode]->id, 'capacite' => 45, 'effectif' => 0, 'active' => true]
                );
            }
        }

        // ── 7. Matières ──────────────────────────────────────────────────
        $matieresData = [
            ['SVT',  'Sciences de la Vie et de la Terre', 2],
            ['MATH', 'Mathématiques',                     4],
            ['PC',   'Physique-Chimie',                   3],
            ['FR',   'Français',                          4],
            ['HG',   'Histoire-Géographie',               3],
            ['EPS',  'Éducation Physique et Sportive',    2],
            ['ANGL', 'Anglais',                           3],
        ];

        $matieres = [];
        foreach ($matieresData as [$code, $nom, $coef]) {
            $matieres[$code] = Matiere::firstOrCreate(
                ['code' => $code, 'etablissement_id' => $etab->id],
                ['nom' => $nom, 'coefficient_defaut' => $coef, 'active' => true]
            );
        }

        // ── 8. Créneaux horaires (07h00 → 18h00) ─────────────────────────
        // Cours : ordres 1-3, 5-7, 9-13 — Pauses intercalées : 4 (récré) et 8 (déjeuner)
        $creneauxData = [
            ['C1',         '07:00', '07:55',  1, 'cours'],
            ['C2',         '07:55', '08:50',  2, 'cours'],
            ['C3',         '08:50', '09:45',  3, 'cours'],
            ['RÉCRÉATION', '09:45', '10:00',  4, 'recreation'],
            ['C4',         '10:00', '10:55',  5, 'cours'],
            ['C5',         '10:55', '11:50',  6, 'cours'],
            ['C6',         '11:50', '12:45',  7, 'cours'],
            ['PAUSE',      '12:45', '14:00',  8, 'pause_dejeuner'],
            ['C7',         '14:00', '14:55',  9, 'cours'],
            ['C8',         '14:55', '15:50', 10, 'cours'],
            ['C9',         '15:50', '16:45', 11, 'cours'],
            ['C10',        '16:45', '17:40', 12, 'cours'],
            ['C11',        '17:40', '18:00', 13, 'cours'],
        ];

        foreach ($creneauxData as [$lib, $deb, $fin, $ordre, $type]) {
            $existing = Creneau::where('etablissement_id', $etab->id)->where('libelle', $lib)->first();
            if ($existing) {
                $existing->update(['heure_debut' => $deb, 'heure_fin' => $fin, 'ordre' => $ordre, 'type' => $type]);
            } else {
                Creneau::create([
                    'etablissement_id' => $etab->id,
                    'libelle'          => $lib,
                    'heure_debut'      => $deb,
                    'heure_fin'        => $fin,
                    'type'             => $type,
                    'ordre'            => $ordre,
                ]);
            }
        }

        // ── 9. Enseignants (vacataires + titulaires + contractuels par discipline)
        // Format : [email, nom, prenom, tel, statut, specialite]
        $enseignantsData = [
            // SVT
            ['kouame.pierre@test.ci',   'KOUAME',   'Pierre',    '0700000020', 'titulaire',   'SVT'],
            ['gonzreu.adrien@test.ci',  'GONZREU',  'Adrien',    '0700000002', 'vacataire',   'SVT'],
            // MATH
            ['bamba.felix@test.ci',     'BAMBA',    'Félix',     '0700000021', 'titulaire',   'Mathématiques'],
            ['kone.salimata@test.ci',   'KONE',     'Salimata',  '0700000022', 'vacataire',   'Mathématiques'],
            // PC
            ['dioulo.marc@test.ci',     'DIOULO',   'Marc',      '0700000023', 'contractuel', 'Physique-Chimie'],
            ['yao.celestine@test.ci',   'YAO',      'Célestine', '0700000024', 'vacataire',   'Physique-Chimie'],
            // FR
            ['toure.aissatou@test.ci',  'TOURE',    'Aïssatou',  '0700000025', 'titulaire',   'Français'],
            ['konan.roger@test.ci',     'KONAN',    'Roger',     '0700000026', 'vacataire',   'Français'],
            // HG
            ['assi.bernadette@test.ci', 'ASSI',     'Bernadette','0700000027', 'contractuel', 'Histoire-Géographie'],
            ['ouedraogo.luc@test.ci',   'OUEDRAOGO','Luc',       '0700000028', 'vacataire',   'Histoire-Géographie'],
            // EPS
            ['traore.moussa@test.ci',   'TRAORE',   'Moussa',    '0700000029', 'titulaire',   'EPS'],
            ['n_guessan.sylvie@test.ci','N\'GUESSAN','Sylvie',   '0700000030', 'vacataire',   'EPS'],
            // ANGL
            ['diallo.ibrahim@test.ci',  'DIALLO',   'Ibrahim',   '0700000031', 'titulaire',   'Anglais'],
            ['adj.christelle@test.ci',  'ADJ',      'Christelle','0700000032', 'vacataire',   'Anglais'],
            // BIVALENT : ANGL + EPS (exemple prof bivalent)
            ['seka.etienne@test.ci',    'SEKA',     'Etienne',   '0700000033', 'vacataire',   'Anglais/EPS'],
        ];

        $enseignants = [];
        foreach ($enseignantsData as $idx => [$email, $nom, $prenom, $tel, $statut, $specialite]) {
            $userEns = User::firstOrCreate(
                ['email' => $email],
                [
                    'etablissement_id'   => $etab->id,
                    'nom'                => $nom,
                    'prenom'             => $prenom,
                    'telephone'          => $tel,
                    'password'           => Hash::make('password'),
                    'role'               => 'enseignant',
                    'actif'              => true,
                    'premiere_connexion' => false,
                ]
            );

            $enseignants[$email] = Enseignant::firstOrCreate(
                ['user_id' => $userEns->id],
                [
                    'etablissement_id' => $etab->id,
                    'nom'              => $nom,
                    'prenom'           => $prenom,
                    'telephone'        => $tel,
                    'statut'           => $statut,
                    'specialite'       => $specialite,
                    'actif'            => true,
                ]
            );
        }

        // Pour la compatibilité avec les messages info ci-dessous
        $enseignant = $enseignants['gonzreu.adrien@test.ci'];

        // ── 10. Contraintes EDT dans le catalogue ─────────────────────────
        // Les codes doivent correspondre exactement aux constantes de ConstraintEngine
        $contraintes = [
            // Dures (bloquantes) — is_mandatory = true
            ['HARD_NO_CLASS_COLLISION',           'Pas deux cours pour la même classe en même temps',   'collision',   true,  1.00, true],
            ['HARD_NO_TEACHER_COLLISION',         'Pas deux cours pour le même prof en même temps',     'collision',   true,  1.00, true],
            ['HARD_NO_ROOM_COLLISION',            'Pas deux cours dans la même salle en même temps',    'collision',   true,  1.00, true],
            ['HARD_RESPECT_VACATAIRE_IMPORT',     'Respecter les indisponibilités importées vacataire', 'collision',   true,  1.00, false],
            ['HARD_NO_TEACHER_EXTERNAL_COLLISION','Prof déjà occupé dans un autre établissement',       'collision',   true,  1.00, false],
            // Souples (scoring)
            ['SOFT_EPS_HEURES_CHAUDES',           'EPS évité aux heures chaudes (10h–14h)',             'pedagogique', true,  0.80, false],
            ['SOFT_CONSECUTIVE_DISCIPLINE',       'Maths/Français en 2h consécutives (1er cycle)',      'pedagogique', true,  0.70, false],
            ['SOFT_TP_CONSECUTIVE_SAME_DAY',      'TP PC/SVT en tandem consécutif',                     'pedagogique', true,  0.70, false],
            ['PRIVATE_GROUP_VACATAIRE_DAYS',      'Regrouper les heures vacataire sur peu de jours',    'enseignant',  true,  0.60, false],
            ['SOFT_EQUITABLE_REPARTITION_SEMAINE','Heures prof réparties équitablement sur la semaine', 'enseignant',  true,  0.60, false],
            ['SOFT_NO_ISOLATED_HOUR',             'Éviter heure isolée pour un prof',                   'enseignant',  false, 0.50, false],
            ['SOFT_MAX_3_NIVEAUX_PAR_PROF',       'Max 3 niveaux différents par prof',                  'enseignant',  true,  0.50, false],
        ];

        foreach ($contraintes as [$code, $lib, $cat, $enabled, $weight, $mandatory]) {
            DB::table('edt_constraint_catalog')->updateOrInsert(
                ['code' => $code],
                [
                    'libelle'         => $lib,
                    'categorie'       => $cat,
                    'default_enabled' => $enabled,
                    'default_weight'  => $weight,
                    'is_mandatory'    => $mandatory,
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );
        }

        // ── 11. Salles ───────────────────────────────────────────────────
        $sallesData = [
            ['Salle 101', 'Bâtiment A', 40, 'classe'],
            ['Salle 102', 'Bâtiment A', 40, 'classe'],
            ['Salle 103', 'Bâtiment A', 40, 'classe'],
            ['Labo SVT',  'Bâtiment B', 30, 'laboratoire'],
            ['Terrain EPS', null,        0, 'sport'],
        ];

        foreach ($sallesData as [$nom, $batiment, $capacite, $type]) {
            Salle::firstOrCreate(
                ['etablissement_id' => $etab->id, 'nom' => $nom],
                ['batiment' => $batiment, 'capacite' => $capacite, 'type' => $type, 'active' => true]
            );
        }

        // ── 12. Référentiel horaire (source + profils + lignes) ───────────
        $source = DB::table('edt_referentiel_sources')->updateOrInsert(
            ['libelle' => 'Grille horaire test'],
            [
                'etablissement_id' => $etab->id,
                'source_document'  => 'Test seeds',
                'annee_reference'  => '2025-2026',
                'actif'            => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        );
        $sourceId = DB::table('edt_referentiel_sources')
            ->where('libelle', 'Grille horaire test')
            ->value('id');

        // Profils par niveau — tous les niveaux
        $niveauxProfils = [
            '6EME' => ['6ème',     'premier_cycle'],
            '5EME' => ['5ème',     'premier_cycle'],
            '4EME' => ['4ème',     'premier_cycle'],
            '3EME' => ['3ème',     'premier_cycle'],
            '2NDE' => ['2nde',     'second_cycle'],
            '1ERE' => ['1ère',     'second_cycle'],
            'TLE'  => ['Terminale','second_cycle'],
        ];

        // Répartition horaire par cycle (blocs hebdomadaires × durée)
        $matieresParCycle = [
            'premier_cycle' => [
                'SVT'  => [2, 55],
                'MATH' => [5, 55],
                'PC'   => [2, 55],
                'FR'   => [5, 55],
                'HG'   => [3, 55],
                'EPS'  => [2, 55],
                'ANGL' => [3, 55],
            ],
            'second_cycle' => [
                'SVT'  => [2, 55],
                'MATH' => [4, 55],
                'PC'   => [3, 55],
                'FR'   => [4, 55],
                'HG'   => [3, 55],
                'EPS'  => [2, 55],
                'ANGL' => [3, 55],
            ],
        ];

        foreach ($niveauxProfils as $niveauCode => [$libelle, $cycle]) {
            $profilId = DB::table('edt_referentiel_profils')
                ->where('source_id', $sourceId)
                ->where('niveau_code', $niveauCode)
                ->whereNull('option_code')
                ->value('id');

            if (!$profilId) {
                $profilId = DB::table('edt_referentiel_profils')->insertGetId([
                    'source_id'           => $sourceId,
                    'code'                => "PROFIL_{$niveauCode}",
                    'niveau_code'         => $niveauCode,
                    'option_code'         => null,
                    'libelle'             => "Grille horaire {$libelle}",
                    'cycle'               => $cycle,
                    'total_eleve_minutes' => 0,
                    'total_prof_minutes'  => 0,
                    'actif'               => true,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $matieresBlocs = $matieresParCycle[$cycle];

            $ordre = 1;
            foreach ($matieresBlocs as $code => [$nbBlocs, $duree]) {
                $matiereId = $matieres[$code]->id ?? null;
                if (!$matiereId) {
                    continue;
                }

                // Delete existing lignes for this profil+matière before recreating
                DB::table('edt_referentiel_lignes')
                    ->where('profil_id', $profilId)
                    ->where('matiere_id', $matiereId)
                    ->delete();

                for ($i = 0; $i < $nbBlocs; $i++) {
                    DB::table('edt_referentiel_lignes')->insert([
                        'profil_id'              => $profilId,
                        'matiere_id'             => $matiereId,
                        'obligatoire'            => true,
                        'facultatif'             => false,
                        'frequence'              => 'hebdomadaire',
                        'mode_seance'            => 'classe_entiere',
                        'volume_eleve_minutes'   => $duree,
                        'volume_prof_minutes'    => $duree,
                        'nb_blocs_souhaite'      => 1,
                        'blocs_consecutifs'      => false,
                        'ecart_min_jours'        => 1,
                        'ordre_montage'          => $ordre++,
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);
                }
            }
        }

        // ── 13. Paramètres EDT par défaut ────────────────────────────────
        DB::table('edt_parametres')->updateOrInsert(
            ['etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id],
            [
                'policy_id'                          => null,
                'mode_generation_defaut'             => 'prive_equilibre',
                'jours_autorises_json'               => json_encode(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi']),
                'creneaux_autorises_json'            => json_encode([]),
                'salles_autorisees_json'             => json_encode([]),
                'attendre_horaires_vacataires'       => false,
                'bloquer_si_vacataire_sans_horaire'  => false,
                'respecter_imports_vacataires'       => true,
                'regrouper_heures_vacataires'        => false,
                'autoriser_reduction_heures'         => false,
                'max_reduction_minutes_par_classe'   => null,
                'max_reduction_minutes_par_matiere'  => null,
                'autoriser_matieres_facultatives'    => false,
                'prioriser_classes_examen'           => true,
                'prioriser_permanents'               => true,
                'equilibrer_journees_classes'        => true,
                'equilibrer_journees_profs'          => true,
                'respecter_tp_consecutifs'           => false,
                'eviter_eps_heures_chaudes'          => true,
                'limiter_niveaux_prof'               => false,
                'max_niveaux_par_prof'               => 5,
                'limiter_heures_creuses'             => false,
                'max_heures_creuses_prof'            => null,
                'autoriser_trous'                    => false,
                'tolerer_surcharge_legere'           => false,
                'activer_apprentissage_ajustements'  => false,
                'verrouiller_ajustements_manuels_par_defaut' => false,
                'notes_generation'                   => null,
                'actif'                              => true,
                'created_by'                         => null,
                'updated_by'                         => null,
                'created_at'                         => now(),
                'updated_at'                         => now(),
            ]
        );

        // ── 14. Plages horaires par classe (exemples de vacation) ────────
        // 6ème 1 → Matin seulement (tous les jours)
        // 6ème 2 → Après-midi seulement (tous les jours), sauf mercredi : aucun cours
        // 6ème 3 → Journée complète (aucune restriction)
        // Autres classes → journée complète
        $plagesExemples = [
            '6ème 1' => [
                'global' => [['apres_midi', false]],  // bloque l'après-midi
                'jours'  => [],
            ],
            '6ème 2' => [
                'global' => [['matin', false]],        // bloque le matin
                'jours'  => [
                    'mercredi' => [['apres_midi', false]], // + mercredi après-midi bloqué
                ],
            ],
        ];

        foreach ($plagesExemples as $classeNom => $config) {
            $classe = $classes[$classeNom] ?? null;
            if (!$classe) {
                continue;
            }

            // Supprimer restrictions existantes pour éviter doublons
            EdtClassePlageHoraire::query()
                ->where('classe_id', $classe->id)
                ->where(function ($q) use ($annee) {
                    $q->whereNull('annee_scolaire_id')->orWhere('annee_scolaire_id', $annee->id);
                })
                ->delete();

            foreach ($config['global'] as [$plage, $autorise]) {
                EdtClassePlageHoraire::create([
                    'etablissement_id'  => $etab->id,
                    'annee_scolaire_id' => $annee->id,
                    'classe_id'         => $classe->id,
                    'jour'              => null,
                    'plage'             => $plage,
                    'autorise'          => $autorise,
                ]);
            }

            foreach ($config['jours'] as $jour => $restrictions) {
                foreach ($restrictions as [$plage, $autorise]) {
                    EdtClassePlageHoraire::create([
                        'etablissement_id'  => $etab->id,
                        'annee_scolaire_id' => $annee->id,
                        'classe_id'         => $classe->id,
                        'jour'              => $jour,
                        'plage'             => $plage,
                        'autorise'          => $autorise,
                    ]);
                }
            }
        }

        // ── 15. Contrainte plage horaire dans le catalogue ────────────────
        DB::table('edt_constraint_catalog')->updateOrInsert(
            ['code' => 'HARD_RESPECT_CLASSE_PLAGE_HORAIRE'],
            [
                'libelle'         => 'Respecter les plages matin/après-midi par classe',
                'categorie'       => 'collision',
                'default_enabled' => true,
                'default_weight'  => 1.00,
                'is_mandatory'    => false,
                'updated_at'      => now(),
                'created_at'      => now(),
            ]
        );

        // ── 16. Affectations enseignant → matière × classe ───────────────
        // Format : [email, [matiere_codes], volume_hebdo]
        // Chaque enseignant est affecté à toutes les classes pour sa/ses matières.
        // Le planner filtre sur cette table ; sans affectation = fallback tous profs.
        $affectationsDefs = [
            // SVT
            ['kouame.pierre@test.ci',   ['SVT'],        2],
            ['gonzreu.adrien@test.ci',  ['SVT'],        2],
            // MATH
            ['bamba.felix@test.ci',     ['MATH'],       5],
            ['kone.salimata@test.ci',   ['MATH'],       5],
            // PC
            ['dioulo.marc@test.ci',     ['PC'],         2],
            ['yao.celestine@test.ci',   ['PC'],         2],
            // FR
            ['toure.aissatou@test.ci',  ['FR'],         5],
            ['konan.roger@test.ci',     ['FR'],         5],
            // HG
            ['assi.bernadette@test.ci', ['HG'],         3],
            ['ouedraogo.luc@test.ci',   ['HG'],         3],
            // EPS
            ['traore.moussa@test.ci',   ['EPS'],        2],
            ['n_guessan.sylvie@test.ci',['EPS'],        2],
            // ANGL
            ['diallo.ibrahim@test.ci',  ['ANGL'],       3],
            ['adj.christelle@test.ci',  ['ANGL'],       3],
            // BIVALENT : enseigne ANGL et EPS
            ['seka.etienne@test.ci',    ['ANGL','EPS'], 3],
        ];

        foreach ($affectationsDefs as [$email, $matiereCodes, $volume]) {
            $ens = $enseignants[$email] ?? null;
            if (!$ens) {
                continue;
            }
            foreach ($matiereCodes as $code) {
                $mat = $matieres[$code] ?? null;
                if (!$mat) {
                    continue;
                }
                foreach ($classes as $classe) {
                    DB::table('affectations')->updateOrInsert(
                        [
                            'enseignant_id'     => $ens->id,
                            'classe_id'         => $classe->id,
                            'matiere_id'        => $mat->id,
                            'annee_scolaire_id' => $annee->id,
                        ],
                        [
                            'volume_horaire_hebdo'     => $volume,
                            'est_professeur_principal' => false,
                            'active'                   => true,
                            'created_at'               => now(),
                            'updated_at'               => now(),
                        ]
                    );
                }
            }
        }

        // ── 17. Trimestres ───────────────────────────────────────────────
        $trimestresData = [
            [1, 'Trimestre 1', '2025-09-01', '2025-12-20', true],
            [2, 'Trimestre 2', '2026-01-05', '2026-03-27', false],
            [3, 'Trimestre 3', '2026-04-06', '2026-06-30', false],
        ];
        $trimestres = [];
        foreach ($trimestresData as [$num, $lib, $deb, $fin, $enCours]) {
            $trimestres[$num] = DB::table('trimestres')->updateOrInsert(
                ['annee_scolaire_id' => $annee->id, 'numero' => $num],
                [
                    'libelle'     => $lib,
                    'date_debut'  => $deb,
                    'date_fin'    => $fin,
                    'en_cours'    => $enCours,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
            $trimestres[$num] = DB::table('trimestres')
                ->where('annee_scolaire_id', $annee->id)
                ->where('numero', $num)
                ->value('id');
        }

        // ── 18. Types d'évaluation ───────────────────────────────────────
        $typesEval = [
            ['DEVOIR',       'Devoir',        30.00],
            ['INTERRO',      'Interrogation', 20.00],
            ['COMPO',        'Composition',   50.00],
        ];
        foreach ($typesEval as [$code, $nom, $poids]) {
            DB::table('types_evaluation')->updateOrInsert(
                ['etablissement_id' => $etab->id, 'code' => $code],
                ['nom' => $nom, 'poids_pourcentage' => $poids, 'actif' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->command->info('');
        $this->command->info('✓  Données de test créées avec succès.');
        $this->command->info('   Super Admin    : superadmin@test.ci / password');
        $this->command->info('   Directeur      : directeur@test.ci  / password');
        $this->command->info('   15 enseignants (dont 1 bivalent ANGL+EPS)');
        $this->command->info('   21 classes (3 par niveau × 7 niveaux)');
        $this->command->info('   Plages exemple : 6ème 1 = matin, 6ème 2 = après-midi (sauf mer.)');
        $this->command->info("   Établissement ID : {$etab->id}");
        $this->command->info("   Enseignant GONZREU ID : {$enseignant->id}");
        $this->command->info("   URL EDT externe  : /emploi-du-temps/enseignants/{$enseignant->id}/horaires-externes");
        $this->command->info("   URL plages : /emploi-du-temps/parametres/plages");
    }
}
