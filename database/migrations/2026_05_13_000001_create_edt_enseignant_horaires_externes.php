<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// Emplois du temps externes des enseignants (autres écoles)
// + Nouvelles contraintes IA issues du guide ACE (Côte d'Ivoire)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Table : horaires d'un prof dans d'autres établissements ──
        if (!Schema::hasTable('edt_enseignant_horaires_externes')) {
            Schema::create('edt_enseignant_horaires_externes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->string('etablissement_externe', 200)->comment('Nom de l\'autre école');
                $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
                $table->time('heure_debut');
                $table->time('heure_fin');
                $table->boolean('valide')->default(true)->comment('Validé par l\'admin');
                $table->enum('source', ['manuel', 'import', 'ocr'])->default('manuel');
                $table->text('commentaire')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['enseignant_id', 'jour'], 'idx_ext_ens_jour');
                $table->index(['enseignant_id', 'annee_scolaire_id'], 'idx_ext_ens_annee');
            });
        }

        // ── Ajout des nouvelles contraintes au catalogue ──
        $now = now();
        $nouvelles = [
            [
                'code' => 'HARD_NO_TEACHER_EXTERNAL_COLLISION',
                'libelle' => 'Pas de chevauchement inter-écoles',
                'description' => 'Interdit de placer un prof à une heure où il enseigne déjà dans un autre établissement.',
                'categorie' => 'collision',
                'default_enabled' => true,
                'default_weight' => 1.00,
                'is_mandatory' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SOFT_CONSECUTIVE_DISCIPLINE',
                'libelle' => 'Regrouper 2h consécutives Maths/Français (1er cycle)',
                'description' => 'Favorise le placement des heures de Maths et Français en créneaux consécutifs pour les classes du 1er cycle (6è-3è), conformément au guide ACE.',
                'categorie' => 'pedagogique',
                'default_enabled' => true,
                'default_weight' => 0.75,
                'is_mandatory' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SOFT_TP_CONSECUTIVE_SAME_DAY',
                'libelle' => 'TP PC/SVT consécutifs le même jour',
                'description' => 'Les séances de TP (Physique-Chimie et SVT) doivent être placées en créneaux consécutifs dans la même journée, avec les deux groupes. Tandem recommandé par le guide ACE.',
                'categorie' => 'pedagogique',
                'default_enabled' => true,
                'default_weight' => 0.90,
                'is_mandatory' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SOFT_EQUITABLE_REPARTITION_SEMAINE',
                'libelle' => 'Répartition équitable des heures sur la semaine',
                'description' => 'Évite de concentrer toutes les heures d\'un professeur sur un ou deux jours. Pénalise les candidats qui surchargent un jour déjà occupé.',
                'categorie' => 'enseignant',
                'default_enabled' => true,
                'default_weight' => 0.60,
                'is_mandatory' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SOFT_NO_ISOLATED_HOUR',
                'libelle' => 'Éviter les heures isolées pour le professeur',
                'description' => 'Pénalise le placement d\'une heure unique séparée par un grand intervalle des autres heures du prof dans la journée (déplacement inutile). Conforme Annexe 2 guide ACE.',
                'categorie' => 'enseignant',
                'default_enabled' => true,
                'default_weight' => 0.50,
                'is_mandatory' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SOFT_MAX_3_NIVEAUX_PAR_PROF',
                'libelle' => 'Maximum 3 niveaux par professeur',
                'description' => 'Pénalise l\'attribution d\'un 4ème niveau différent à un même professeur (sauf EDHC, Arts plastiques, Musique). Conforme Annexe 2 guide ACE.',
                'categorie' => 'enseignant',
                'default_enabled' => true,
                'default_weight' => 0.70,
                'is_mandatory' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insérer uniquement les codes qui n'existent pas déjà
        foreach ($nouvelles as $contrainte) {
            $exists = DB::table('edt_constraint_catalog')
                ->where('code', $contrainte['code'])
                ->exists();

            if (!$exists) {
                DB::table('edt_constraint_catalog')->insert($contrainte);
            }
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('edt_enseignant_horaires_externes');
        Schema::enableForeignKeyConstraints();

        $codes = [
            'HARD_NO_TEACHER_EXTERNAL_COLLISION',
            'SOFT_CONSECUTIVE_DISCIPLINE',
            'SOFT_TP_CONSECUTIVE_SAME_DAY',
            'SOFT_EQUITABLE_REPARTITION_SEMAINE',
            'SOFT_NO_ISOLATED_HOUR',
            'SOFT_MAX_3_NIVEAUX_PAR_PROF',
        ];

        DB::table('edt_constraint_catalog')->whereIn('code', $codes)->delete();
    }
};
