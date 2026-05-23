<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════════════
// Module EDT — Tables du moteur de génération IA
// (Référentiel, Politiques, Scénarios, Runs, Vacataires)
// ══════════════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Catalogue de contraintes ──────────────────────────────────
        if (!Schema::hasTable('edt_constraint_catalog')) {
            Schema::create('edt_constraint_catalog', function (Blueprint $table) {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('libelle', 200);
                $table->text('description')->nullable();
                $table->string('categorie', 60)->nullable();
                $table->boolean('default_enabled')->default(true);
                $table->decimal('default_weight', 5, 2)->default(1.00);
                $table->boolean('is_mandatory')->default(false);
                $table->timestamps();
            });
        }

        // ── 2. Référentiel horaires (sources officielles) ─────────────────
        if (!Schema::hasTable('edt_referentiel_sources')) {
            Schema::create('edt_referentiel_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
                $table->string('libelle', 200);
                $table->string('source_document', 200)->nullable();
                $table->date('date_reference')->nullable();
                $table->string('annee_reference', 10)->nullable();
                $table->text('description')->nullable();
                $table->boolean('actif')->default(true);
                $table->timestamps();
            });
        }

        // ── 3. Profils horaires (ex : 2nde C, option Maths-Physique) ──────
        if (!Schema::hasTable('edt_referentiel_profils')) {
            Schema::create('edt_referentiel_profils', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_id')->constrained('edt_referentiel_sources')->cascadeOnDelete();
                $table->string('code', 60);
                $table->string('niveau_code', 40);
                $table->string('option_code', 40)->nullable();
                $table->string('libelle', 200);
                $table->string('cycle', 20)->nullable();
                $table->unsignedSmallInteger('total_eleve_minutes')->default(0);
                $table->unsignedSmallInteger('total_prof_minutes')->default(0);
                $table->boolean('actif')->default(true);
                $table->timestamps();

                $table->index(['source_id', 'niveau_code'], 'idx_profil_source_niveau');
            });
        }

        // ── 4. Lignes de référentiel (matière × profil) ───────────────────
        if (!Schema::hasTable('edt_referentiel_lignes')) {
            Schema::create('edt_referentiel_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profil_id')->constrained('edt_referentiel_profils')->cascadeOnDelete();
                $table->foreignId('matiere_id')->nullable()->constrained('matieres')->nullOnDelete();
                $table->boolean('obligatoire')->default(true);
                $table->boolean('facultatif')->default(false);
                $table->string('expression_source', 100)->nullable();
                $table->string('frequence', 50)->nullable();
                $table->string('mode_seance', 50)->nullable();
                $table->unsignedSmallInteger('volume_classe_entiere_minutes')->nullable();
                $table->unsignedSmallInteger('volume_demi_classe_minutes')->nullable();
                $table->unsignedSmallInteger('volume_eleve_minutes')->nullable();
                $table->unsignedSmallInteger('volume_prof_minutes')->nullable();
                $table->unsignedTinyInteger('nb_blocs_souhaite')->nullable();
                $table->boolean('blocs_consecutifs')->default(false);
                $table->unsignedTinyInteger('ecart_min_jours')->nullable();
                $table->unsignedTinyInteger('ordre_montage')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ── 5. Politiques de génération ───────────────────────────────────
        if (!Schema::hasTable('edt_policies')) {
            Schema::create('edt_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->string('nom', 150);
                $table->string('mode_generation', 50)->nullable();
                $table->text('description')->nullable();
                $table->boolean('autoriser_reduction_heures')->default(false);
                $table->boolean('autoriser_matieres_facultatives')->default(false);
                $table->boolean('prioriser_classes_examen')->default(true);
                $table->boolean('prioriser_permanents')->default(true);
                $table->boolean('attendre_horaires_vacataires')->default(false);
                $table->unsignedSmallInteger('max_reduction_minutes_par_classe')->nullable();
                $table->unsignedSmallInteger('max_reduction_minutes_par_matiere')->nullable();
                $table->boolean('actif')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ── 6. Overrides de politique par classe ──────────────────────────
        if (!Schema::hasTable('edt_policy_class_overrides')) {
            Schema::create('edt_policy_class_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_id')->constrained('edt_policies')->cascadeOnDelete();
                $table->foreignId('classe_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('niveau_reglementaire_code', 40)->nullable();
                $table->string('option_reglementaire_code', 40)->nullable();
                $table->unsignedSmallInteger('total_cible_minutes')->nullable();
                $table->unsignedSmallInteger('total_min_minutes')->nullable();
                $table->text('commentaire')->nullable();
                $table->timestamps();
            });
        }

        // ── 7. Overrides de politique par matière ─────────────────────────
        if (!Schema::hasTable('edt_policy_matiere_overrides')) {
            Schema::create('edt_policy_matiere_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_id')->constrained('edt_policies')->cascadeOnDelete();
                $table->foreignId('classe_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('niveau_reglementaire_code', 40)->nullable();
                $table->string('option_reglementaire_code', 40)->nullable();
                $table->foreignId('matiere_id')->nullable()->constrained('matieres')->nullOnDelete();
                $table->boolean('enabled')->default(true);
                $table->unsignedSmallInteger('volume_cible_minutes')->nullable();
                $table->unsignedSmallInteger('volume_min_minutes')->nullable();
                $table->unsignedTinyInteger('priorite')->nullable();
                $table->string('motif', 200)->nullable();
                $table->timestamps();
            });
        }

        // ── 8. Paramètres de génération par établissement/année ───────────
        if (!Schema::hasTable('edt_parametres')) {
            Schema::create('edt_parametres', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->foreignId('policy_id')->nullable()->constrained('edt_policies')->nullOnDelete();
                $table->string('mode_generation_defaut', 50)->nullable();
                $table->json('jours_autorises_json')->nullable();
                $table->json('creneaux_autorises_json')->nullable();
                $table->json('salles_autorisees_json')->nullable();
                $table->boolean('attendre_horaires_vacataires')->default(false);
                $table->boolean('bloquer_si_vacataire_sans_horaire')->default(false);
                $table->boolean('respecter_imports_vacataires')->default(true);
                $table->boolean('regrouper_heures_vacataires')->default(false);
                $table->boolean('autoriser_reduction_heures')->default(false);
                $table->unsignedSmallInteger('max_reduction_minutes_par_classe')->nullable();
                $table->unsignedSmallInteger('max_reduction_minutes_par_matiere')->nullable();
                $table->boolean('autoriser_matieres_facultatives')->default(false);
                $table->boolean('prioriser_classes_examen')->default(true);
                $table->boolean('prioriser_permanents')->default(true);
                $table->boolean('equilibrer_journees_classes')->default(true);
                $table->boolean('equilibrer_journees_profs')->default(true);
                $table->boolean('respecter_tp_consecutifs')->default(true);
                $table->boolean('eviter_eps_heures_chaudes')->default(true);
                $table->boolean('limiter_niveaux_prof')->default(true);
                $table->unsignedTinyInteger('max_niveaux_par_prof')->default(3);
                $table->boolean('limiter_heures_creuses')->default(false);
                $table->unsignedTinyInteger('max_heures_creuses_prof')->nullable();
                $table->boolean('autoriser_trous')->default(false);
                $table->boolean('tolerer_surcharge_legere')->default(false);
                $table->boolean('activer_apprentissage_ajustements')->default(false);
                $table->boolean('verrouiller_ajustements_manuels_par_defaut')->default(false);
                $table->text('notes_generation')->nullable();
                $table->boolean('actif')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['etablissement_id', 'annee_scolaire_id'], 'uq_edt_param_etab_annee');
            });
        }

        // ── 9. Scénarios de génération ────────────────────────────────────
        if (!Schema::hasTable('edt_generation_scenarios')) {
            Schema::create('edt_generation_scenarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->foreignId('policy_id')->nullable()->constrained('edt_policies')->nullOnDelete();
                $table->string('nom', 150);
                $table->string('mode_generation', 50)->nullable();
                $table->string('portee', 50)->nullable();
                $table->json('jours_json')->nullable();
                $table->json('creneaux_json')->nullable();
                $table->json('salles_json')->nullable();
                $table->json('options_json')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['etablissement_id', 'annee_scolaire_id'], 'idx_scenario_etab_annee');
            });
        }

        // ── 10. Contraintes d'un scénario ─────────────────────────────────
        if (!Schema::hasTable('edt_generation_scenario_constraints')) {
            Schema::create('edt_generation_scenario_constraints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scenario_id')->constrained('edt_generation_scenarios')->cascadeOnDelete();
                $table->foreignId('constraint_id')->constrained('edt_constraint_catalog')->cascadeOnDelete();
                $table->boolean('enabled')->default(true);
                $table->decimal('weight', 5, 2)->nullable();
                $table->json('params_json')->nullable();
                $table->timestamps();

                $table->unique(['scenario_id', 'constraint_id'], 'uq_scenario_constraint');
            });
        }

        // ── 11. Portée d'un scénario (classes/enseignants ciblés) ─────────
        if (!Schema::hasTable('edt_generation_scenario_scopes')) {
            Schema::create('edt_generation_scenario_scopes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scenario_id')->constrained('edt_generation_scenarios')->cascadeOnDelete();
                $table->string('scope_type', 50);
                $table->unsignedBigInteger('scope_id');
                // Pas de timestamps (table pivot légère)
            });
        }

        // ── 12. Exécutions de génération ──────────────────────────────────
        if (!Schema::hasTable('edt_generation_runs')) {
            Schema::create('edt_generation_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scenario_id')->nullable()->constrained('edt_generation_scenarios')->nullOnDelete();
                $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->string('run_uuid', 36)->unique();
                $table->string('status', 30)->default('pending');
                $table->decimal('score_global', 8, 4)->nullable();
                $table->json('summary_json')->nullable();
                $table->json('conformite_json')->nullable();
                $table->datetime('started_at')->nullable();
                $table->datetime('finished_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['etablissement_id', 'status'], 'idx_run_etab_status');
            });
        }

        // ── 13. Problèmes détectés lors d'un run ──────────────────────────
        if (!Schema::hasTable('edt_generation_issues')) {
            Schema::create('edt_generation_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('run_id')->constrained('edt_generation_runs')->cascadeOnDelete();
                $table->string('niveau', 20);
                $table->string('issue_code', 80);
                $table->string('scope_type', 50)->nullable();
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->text('message');
                $table->json('details_json')->nullable();
                $table->timestamps();

                $table->index(['run_id', 'niveau'], 'idx_issue_run_niveau');
            });
        }

        // ── 14. Imports de disponibilités vacataires ──────────────────────
        if (!Schema::hasTable('edt_vacataire_imports')) {
            Schema::create('edt_vacataire_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
                $table->string('source_type', 30)->default('photo');
                $table->string('fichier_path')->nullable();
                $table->string('original_filename', 255)->nullable();
                $table->json('payload_extrait_json')->nullable();
                $table->text('resume_extraction')->nullable();
                $table->unsignedSmallInteger('confidence_score')->default(0);
                $table->string('status', 30)->default('uploade');
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->datetime('validated_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['enseignant_id', 'status'], 'idx_vacimport_ens_status');
            });
        }

        // ── 15. Créneaux extraits des disponibilités vacataires ───────────
        if (!Schema::hasTable('edt_vacataire_slots')) {
            Schema::create('edt_vacataire_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('import_id')->nullable()->constrained('edt_vacataire_imports')->nullOnDelete();
                $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
                $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
                $table->time('heure_debut');
                $table->time('heure_fin');
                $table->foreignId('creneau_id')->nullable()->constrained('creneaux')->nullOnDelete();
                $table->string('etat', 30)->default('disponible');
                $table->string('site_externe', 200)->nullable();
                $table->text('commentaire')->nullable();
                $table->decimal('source_confidence', 5, 2)->nullable();
                $table->timestamps();

                $table->index(['enseignant_id', 'jour'], 'idx_vacslot_ens_jour');
            });
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'edt_vacataire_slots',
            'edt_vacataire_imports',
            'edt_generation_issues',
            'edt_generation_runs',
            'edt_generation_scenario_scopes',
            'edt_generation_scenario_constraints',
            'edt_generation_scenarios',
            'edt_parametres',
            'edt_policy_matiere_overrides',
            'edt_policy_class_overrides',
            'edt_policies',
            'edt_referentiel_lignes',
            'edt_referentiel_profils',
            'edt_referentiel_sources',
            'edt_constraint_catalog',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
};
