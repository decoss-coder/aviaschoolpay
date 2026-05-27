<?php

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('avia:reset-real-test {--etablissement-id=} {--all-schools} {--eleves-par-classe=52} {--confirm=}', function () {
    if ($this->option('confirm') !== 'RESET-REAL-TEST') {
        $this->error('Commande bloquée. Ajoute --confirm=RESET-REAL-TEST pour confirmer le reset.');
        return self::FAILURE;
    }

    $allSchools = (bool) $this->option('all-schools');
    $etabId = $this->option('etablissement-id') ? (int) $this->option('etablissement-id') : null;
    $elevesParClasse = max(1, min(60, (int) $this->option('eleves-par-classe')));

    if (!$allSchools && !$etabId) {
        $this->error('Indique --etablissement-id=ID ou --all-schools.');
        return self::FAILURE;
    }

    $etabs = $allSchools
        ? Etablissement::query()->orderBy('id')->get()
        : Etablissement::query()->where('id', $etabId)->get();

    if ($etabs->isEmpty()) {
        $this->error('Aucun établissement trouvé.');
        return self::FAILURE;
    }

    $this->warn('Reset contrôlé : établissements et paramètres conservés. Données scolaires réinitialisées.');

    $result = DB::transaction(function () use ($etabs, $elevesParClasse) {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $colsCache = [];
        $cols = function (string $table) use (&$colsCache): array {
            if (!isset($colsCache[$table])) {
                $colsCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
            }
            return $colsCache[$table];
        };
        $has = fn (string $table, string $column): bool => in_array($column, $cols($table), true);
        $filter = fn (string $table, array $payload): array => array_intersect_key($payload, array_flip($cols($table)));
        $upsert = function (string $table, array $keys, array $payload) use ($filter) {
            if (!Schema::hasTable($table)) return null;
            $keys = $filter($table, $keys);
            $payload = $filter($table, $payload);
            if (empty($keys)) return null;
            DB::table($table)->updateOrInsert($keys, $payload);
            return DB::table($table)->where($keys)->value('id');
        };
        $deleteIn = function (string $table, string $column, $ids) use ($has) {
            $ids = collect($ids)->filter()->values();
            if ($ids->isEmpty() || !Schema::hasTable($table) || !$has($table, $column)) return;
            DB::table($table)->whereIn($column, $ids)->delete();
        };

        $summary = ['schools' => 0, 'classes' => 0, 'students' => 0, 'teachers' => 0, 'rooms' => 0, 'affectations' => 0, 'averages' => 0];

        foreach ($etabs as $etab) {
            $summary['schools']++;
            $annee = AnneeScolaire::where('etablissement_id', $etab->id)->where('en_cours', true)->orderByDesc('id')->first()
                ?: AnneeScolaire::where('etablissement_id', $etab->id)->orderByDesc('id')->first();

            if (!$annee) {
                $anneeId = $upsert('annees_scolaires', ['etablissement_id' => $etab->id, 'libelle' => '2026-2027'], [
                    'etablissement_id' => $etab->id, 'libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-07-31', 'en_cours' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $annee = AnneeScolaire::find($anneeId);
            }

            $classeIds = Schema::hasTable('classes') ? DB::table('classes')->where('etablissement_id', $etab->id)->pluck('id') : collect();
            $eleveIds = Schema::hasTable('eleves') ? DB::table('eleves')->where('etablissement_id', $etab->id)->pluck('id') : collect();
            $enseignantIds = Schema::hasTable('enseignants') ? DB::table('enseignants')->where('etablissement_id', $etab->id)->pluck('id') : collect();
            $matiereIds = Schema::hasTable('matieres') ? DB::table('matieres')->where('etablissement_id', $etab->id)->pluck('id') : collect();
            $salleIds = Schema::hasTable('salles') ? DB::table('salles')->where('etablissement_id', $etab->id)->pluck('id') : collect();

            foreach (['moyennes_annuelles','moyennes_generales','moyennes_matieres','notes','inscriptions','paiements','presence_eleves','parents_eleves','eleve_parent','emplois_du_temps'] as $table) {
                $deleteIn($table, 'eleve_id', $eleveIds);
                $deleteIn($table, 'classe_id', $classeIds);
            }
            foreach (['evaluations','affectations','emplois_du_temps','edt_generation_runs','edt_generation_issues'] as $table) {
                $deleteIn($table, 'classe_id', $classeIds);
                $deleteIn($table, 'enseignant_id', $enseignantIds);
                $deleteIn($table, 'salle_id', $salleIds);
            }
            $deleteIn('eleves', 'id', $eleveIds);
            $deleteIn('enseignants', 'id', $enseignantIds);
            $deleteIn('classes', 'id', $classeIds);
            $deleteIn('salles', 'id', $salleIds);
            $deleteIn('matieres', 'id', $matiereIds);

            $niveauRows = [
                ['6E','Sixième','premier_cycle',10], ['5E','Cinquième','premier_cycle',20], ['4E','Quatrième','premier_cycle',30], ['3E','Troisième','premier_cycle',40],
                ['2NDE','Seconde','second_cycle',50], ['1ERE','Première','second_cycle',60], ['TLE','Terminale','second_cycle',70],
            ];
            $niveauIds = [];
            foreach ($niveauRows as [$code,$lib,$cycle,$ordre]) {
                $niveauIds[$code] = $upsert('niveaux', ['etablissement_id' => $etab->id, 'code' => $code], [
                    'etablissement_id' => $etab->id, 'code' => $code, 'libelle' => $lib, 'cycle' => $cycle, 'ordre' => $ordre,
                    'frais_scolarite_defaut' => 0, 'frais_inscription_defaut' => 0, 'frais_reinscription_defaut' => 0, 'actif' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $matiereRows = [
                ['FR','Français',4,'lettres',null,1], ['CF','Composition française',2,'lettres','FR',1], ['OG','Orthographe et grammaire',1,'lettres','FR',2], ['EO','Expression orale',1,'lettres','FR',3],
                ['HG','Histoire-Géographie',2,'lettres',null,2], ['ANG','Anglais',2,'lettres',null,3], ['ESP','Espagnol',1,'lettres',null,4], ['ALL','Allemand',1,'lettres',null,5],
                ['MATH','Mathématiques',3,'sciences',null,6], ['PC','Physique-Chimie',2,'sciences',null,7], ['SVT','Sciences de la Vie et de la Terre',2,'sciences',null,8],
                ['EDHC','EDHC',1,'autres',null,9], ['EPS','Éducation Physique et Sportive',1,'autres',null,10], ['AP','Arts plastiques / Éducation',1,'autres',null,11], ['LECTURE','Lecture',1,'autres',null,12], ['AUTRE2','Autre discipline 2',1,'autres',null,13], ['COND','Conduite',1,'autres',null,14], ['PHILO','Philosophie',2,'lettres',null,15],
            ];
            $matiereIdsByCode = [];
            foreach ($matiereRows as [$code,$nom,$coef,$groupe,$parentCode,$ordre]) {
                $matiereIdsByCode[$code] = $upsert('matieres', ['etablissement_id' => $etab->id, 'code' => $code], [
                    'etablissement_id' => $etab->id, 'parent_matiere_id' => $parentCode ? ($matiereIdsByCode[$parentCode] ?? null) : null,
                    'nom' => $nom, 'code' => $code, 'coefficient_defaut' => $coef, 'poids_dans_parent' => max(1, $coef), 'ordre' => $ordre, 'groupe' => $groupe, 'active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $teacherRows = [
                ['Kindo','Mariam','F','titulaire',['FR']], ['Mian','Armand','M','titulaire',['HG']], ['Ndri','Nadia','F','titulaire',['ANG']], ['Foto','Michel','M','vacataire',['ESP','ALL']],
                ['Nde','Noël','M','titulaire',['MATH']], ['Kouame','Paul','M','titulaire',['PC']], ['Tuo','Irène','F','titulaire',['SVT']], ['Due','Juliette','F','titulaire',['EDHC']],
                ['Diarraouba','Salif','M','contractuel',['EPS']], ['Kouassi','Gedeon','M','titulaire',['COND']], ['Boty','Armel','M','vacataire',['LECTURE','AP']], ['Nguessan','Patrick','M','vacataire',['PHILO']],
            ];
            $teachersByCode = [];
            foreach ($teacherRows as $i => [$nom,$prenom,$sexe,$statut,$disciplines]) {
                $mat = 'REAL-E'.$etab->id.'-'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT);
                $id = $upsert('enseignants', ['etablissement_id' => $etab->id, 'matricule_mena' => $mat], [
                    'etablissement_id' => $etab->id, 'matricule_mena' => $mat, 'nom' => $nom, 'prenom' => $prenom, 'sexe' => $sexe,
                    'telephone' => '0709'.str_pad((string)$etab->id,2,'0',STR_PAD_LEFT).str_pad((string)($i+1),4,'0',STR_PAD_LEFT),
                    'email' => 'prof.real'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT).'.ecole'.$etab->id.'@aviaschoolpay.local',
                    'specialite' => implode(' / ', $disciplines), 'statut' => $statut, 'salaire_base' => $statut === 'vacataire' ? 0 : 150000, 'taux_horaire' => $statut === 'vacataire' ? 2500 : 0,
                    'heures_contractuelles_mois' => 72, 'actif' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $summary['teachers']++;
                foreach ($disciplines as $code) $teachersByCode[$code][] = $id;
            }

            for ($i=1; $i<=10; $i++) {
                $upsert('salles', ['etablissement_id' => $etab->id, 'nom' => 'Salle '.$i], [
                    'etablissement_id' => $etab->id, 'nom' => 'Salle '.$i, 'batiment' => 'Bloc A', 'capacite' => 55, 'type' => 'classe', 'active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $summary['rooms']++;
            }

            $classRows = [
                ['6E','6ème 1'], ['6E','6ème 2'], ['5E','5ème 1'], ['5E','5ème 2'], ['4E','4ème 1'], ['4E','4ème 2'], ['3E','3ème 1'], ['3E','3ème 2'],
                ['2NDE','2nde C 1'], ['2NDE','2nde C 2'], ['2NDE','2nde A1'], ['2NDE','2nde A2'], ['1ERE','1ère D1'], ['1ERE','1ère D2'], ['1ERE','1ère C1'], ['1ERE','1ère C2'], ['1ERE','1ère A1'], ['1ERE','1ère A2'], ['TLE','Tle D1'], ['TLE','Tle D2'], ['TLE','Tle C1'], ['TLE','Tle C2'], ['TLE','Tle A1'], ['TLE','Tle A2'],
            ];
            $classes = [];
            foreach ($classRows as [$niv,$nom]) {
                $id = $upsert('classes', ['etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id, 'nom' => $nom], [
                    'etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id, 'niveau_id' => $niveauIds[$niv] ?? null, 'nom' => $nom,
                    'capacite' => 60, 'effectif' => $elevesParClasse, 'scolarite_annuelle' => 0, 'frais_inscription' => 0, 'frais_reinscription' => 0, 'active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $classes[] = ['id'=>$id,'niveau'=>$niv,'nom'=>$nom];
                $summary['classes']++;
            }

            $prenoms = ['Awa','Mariam','Fatou','Aminata','Kouadio','Yao','Koffi','Konan','Adjoua','Nadia','Serge','Armand','Ibrahim','Mema','Ange','Flora','Binta','Lassina','Patrick','Irène'];
            $noms = ['BAGATE','KOUAME','KOUASSI','KOFFI','KONE','TRAORE','BAMBA','YAO','SORO','NDE','TUO','DUE','MIAN','FOTO','NDAYE','COULIBALY'];
            $studentIndex = 1;
            foreach ($classes as $classeRow) {
                for ($i=1; $i<=$elevesParClasse; $i++) {
                    $sexe = $i % 2 === 0 ? 'M' : 'F';
                    $nom = $noms[($i + $studentIndex) % count($noms)];
                    $prenom = $prenoms[($i + $studentIndex * 2) % count($prenoms)];
                    $matricule = str_pad((string)($etab->id),2,'0',STR_PAD_LEFT).str_pad((string)$classeRow['id'],4,'0',STR_PAD_LEFT).str_pad((string)$i,3,'0',STR_PAD_LEFT).($sexe === 'M' ? 'M' : 'F');
                    $eleveId = $upsert('eleves', ['etablissement_id' => $etab->id, 'matricule_desps' => $matricule], [
                        'etablissement_id' => $etab->id, 'classe_id' => $classeRow['id'], 'matricule_interne' => 'AVI-'.$annee->libelle.'-'.$matricule, 'matricule_desps' => $matricule,
                        'nom' => $nom, 'prenom' => $prenom, 'sexe' => $sexe, 'date_naissance' => now()->subYears(10 + ($studentIndex % 8))->subDays($i)->toDateString(),
                        'lieu_naissance' => 'Kongasso', 'nationalite' => 'Ivoirienne', 'statut' => 'inscrit', 'statut_eleve' => $studentIndex % 4 === 0 ? 'NAFF' : 'AFF', 'actif' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $summary['students']++;
                    $studentIndex++;
                }
            }

            $plans = function (string $niveau): array {
                $base = ['FR'=>4,'HG'=>2,'ANG'=>2,'MATH'=>3,'PC'=>2,'SVT'=>2,'EDHC'=>1,'EPS'=>1,'AP'=>1,'LECTURE'=>1,'AUTRE2'=>1,'COND'=>1];
                if (in_array($niveau, ['4E','3E','2NDE','1ERE','TLE'], true)) $base += ['ESP'=>1,'ALL'=>1];
                if (in_array($niveau, ['2NDE','1ERE','TLE'], true)) $base += ['PHILO'=>2];
                return $base;
            };
            foreach ($classes as $classeRow) {
                foreach ($plans($classeRow['niveau']) as $code => $volume) {
                    $teacherPool = $teachersByCode[$code] ?? [];
                    if (empty($teacherPool) || empty($matiereIdsByCode[$code])) continue;
                    $teacherId = $teacherPool[$summary['affectations'] % count($teacherPool)];
                    $upsert('affectations', ['enseignant_id'=>$teacherId,'classe_id'=>$classeRow['id'],'matiere_id'=>$matiereIdsByCode[$code],'annee_scolaire_id'=>$annee->id], [
                        'enseignant_id'=>$teacherId,'classe_id'=>$classeRow['id'],'matiere_id'=>$matiereIdsByCode[$code],'annee_scolaire_id'=>$annee->id,'volume_horaire_hebdo'=>$volume,'est_professeur_principal'=>$code==='FR','active'=>true,'created_at'=>now(),'updated_at'=>now(),
                    ]);
                    $summary['affectations']++;
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return $summary;
    });

    $this->info('Reset terminé.');
    foreach ($result as $key => $value) {
        $this->line($key.' : '.$value);
    }

    return self::SUCCESS;
})->purpose('Reset controlled school data and seed official Côte d’Ivoire test data');
