<?php

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('avia:prepare-edt-ai {--etablissement-id=} {--annee-id=} {--confirm=}', function () {
    if ($this->option('confirm') !== 'PREPARE-EDT-AI') {
        $this->error('Commande bloquée. Ajoute --confirm=PREPARE-EDT-AI pour confirmer.');
        return self::FAILURE;
    }

    $etabId = $this->option('etablissement-id') ? (int) $this->option('etablissement-id') : null;
    if (! $etabId) {
        $this->error('Indique --etablissement-id=ID.');
        return self::FAILURE;
    }

    $etab = Etablissement::find($etabId);
    if (! $etab) {
        $this->error('Établissement introuvable.');
        return self::FAILURE;
    }

    $annee = $this->option('annee-id')
        ? AnneeScolaire::where('etablissement_id', $etab->id)->find((int) $this->option('annee-id'))
        : AnneeScolaire::where('etablissement_id', $etab->id)->where('en_cours', true)->orderByDesc('id')->first();

    $annee ??= AnneeScolaire::where('etablissement_id', $etab->id)->orderByDesc('id')->first();

    if (! $annee) {
        $this->error('Aucune année scolaire trouvée pour cet établissement.');
        return self::FAILURE;
    }

    $this->warn('Préparation EDT IA pour : '.$etab->nom.' — '.$annee->libelle);
    $this->warn('Les élèves, classes, matières, enseignants et affectations sont conservés.');

    $result = DB::transaction(function () use ($etab, $annee) {
        $colsCache = [];
        $cols = function (string $table) use (&$colsCache): array {
            if (! isset($colsCache[$table])) {
                $colsCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
            }
            return $colsCache[$table];
        };
        $has = fn (string $table, string $column): bool => in_array($column, $cols($table), true);
        $filter = fn (string $table, array $payload): array => array_intersect_key($payload, array_flip($cols($table)));
        $upsert = function (string $table, array $keys, array $payload) use ($filter) {
            if (! Schema::hasTable($table)) return null;
            $keys = $filter($table, $keys);
            $payload = $filter($table, $payload);
            if (empty($keys)) return null;
            DB::table($table)->updateOrInsert($keys, $payload);
            return DB::table($table)->where($keys)->value('id');
        };
        $deleteScoped = function (string $table) use ($etab, $annee, $has) {
            if (! Schema::hasTable($table)) return 0;
            $q = DB::table($table);
            if ($has($table, 'etablissement_id')) {
                $q->where('etablissement_id', $etab->id);
            }
            if ($has($table, 'annee_scolaire_id')) {
                $q->where('annee_scolaire_id', $annee->id);
            }
            return $q->delete();
        };

        $deleted = [
            'emploi_du_temps' => $deleteScoped('emploi_du_temps'),
            'emplois_du_temps' => $deleteScoped('emplois_du_temps'),
            'edt_generation_runs' => $deleteScoped('edt_generation_runs'),
            'edt_generation_issues' => $deleteScoped('edt_generation_issues'),
        ];

        if (Schema::hasTable('creneaux')) {
            $creneaux = [
                ['C1', '07:10:00', '08:05:00', 'cours', 1, false, false],
                ['C2', '08:05:00', '09:00:00', 'cours', 2, false, false],
                ['C3', '09:00:00', '09:55:00', 'cours', 3, false, false],
                ['Récréation', '09:55:00', '10:10:00', 'recreation', 4, true, false],
                ['C4', '10:10:00', '11:05:00', 'cours', 5, false, false],
                ['C5', '11:05:00', '12:00:00', 'cours', 6, false, false],
                ['Pause déjeuner', '12:00:00', '13:30:00', 'pause_dejeuner', 7, false, true],
                ['C6', '13:30:00', '14:25:00', 'cours', 8, false, false],
                ['C7', '14:25:00', '15:20:00', 'cours', 9, false, false],
                ['C8', '15:20:00', '16:15:00', 'cours', 10, false, false],
                ['C9', '16:15:00', '17:10:00', 'cours', 11, false, false],
                ['C10', '17:10:00', '18:05:00', 'cours', 12, false, false],
            ];

            foreach ($creneaux as [$libelle, $debut, $fin, $type, $ordre, $recreation, $pause]) {
                $upsert('creneaux', ['etablissement_id' => $etab->id, 'libelle' => $libelle], [
                    'etablissement_id' => $etab->id,
                    'libelle' => $libelle,
                    'heure_debut' => $debut,
                    'heure_fin' => $fin,
                    'type' => $type,
                    'ordre' => $ordre,
                    'est_recreation' => $recreation,
                    'est_pause' => $pause,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('salles')) {
            for ($i = 1; $i <= 10; $i++) {
                $upsert('salles', ['etablissement_id' => $etab->id, 'nom' => 'Salle '.$i], [
                    'etablissement_id' => $etab->id,
                    'nom' => 'Salle '.$i,
                    'batiment' => 'Bloc A',
                    'capacite' => 55,
                    'type' => 'classe',
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('edt_policies')) {
            if ($has('edt_policies', 'actif')) {
                DB::table('edt_policies')
                    ->where('etablissement_id', $etab->id)
                    ->where('annee_scolaire_id', $annee->id)
                    ->update(['actif' => false, 'updated_at' => now()]);
            }

            $upsert('edt_policies', ['etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id, 'nom' => 'Préparation IA EDT'], [
                'etablissement_id' => $etab->id,
                'annee_scolaire_id' => $annee->id,
                'nom' => 'Préparation IA EDT',
                'mode_generation' => 'ia',
                'description' => 'Politique propre pour relancer la génération IA après vidage des emplois du temps.',
                'autoriser_reduction_heures' => true,
                'autoriser_matieres_facultatives' => true,
                'prioriser_classes_examen' => true,
                'prioriser_permanents' => true,
                'attendre_horaires_vacataires' => false,
                'max_reduction_minutes_par_classe' => 0,
                'max_reduction_minutes_par_matiere' => 0,
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $counts = [
            'classes' => Schema::hasTable('classes') ? DB::table('classes')->where('etablissement_id', $etab->id)->where('annee_scolaire_id', $annee->id)->count() : 0,
            'eleves' => Schema::hasTable('eleves') ? DB::table('eleves')->where('etablissement_id', $etab->id)->where('actif', true)->count() : 0,
            'enseignants' => Schema::hasTable('enseignants') ? DB::table('enseignants')->where('etablissement_id', $etab->id)->where('actif', true)->count() : 0,
            'affectations' => Schema::hasTable('affectations') ? DB::table('affectations')->where('annee_scolaire_id', $annee->id)->where('active', true)->count() : 0,
            'creneaux_cours' => Schema::hasTable('creneaux') ? DB::table('creneaux')->where('etablissement_id', $etab->id)->where('type', 'cours')->count() : 0,
            'salles' => Schema::hasTable('salles') ? DB::table('salles')->where('etablissement_id', $etab->id)->where('active', true)->count() : 0,
        ];

        return ['deleted' => $deleted, 'counts' => $counts];
    });

    $this->info('Préparation terminée.');
    $this->line('EDT supprimés : '.(($result['deleted']['emploi_du_temps'] ?? 0) + ($result['deleted']['emplois_du_temps'] ?? 0)));
    $this->line('Runs IA supprimés : '.($result['deleted']['edt_generation_runs'] ?? 0));
    $this->line('Issues IA supprimées : '.($result['deleted']['edt_generation_issues'] ?? 0));

    foreach ($result['counts'] as $key => $value) {
        $this->line($key.' : '.$value);
    }

    return self::SUCCESS;
})->purpose('Clear timetable generations and prepare parameters for EDT AI generation');
