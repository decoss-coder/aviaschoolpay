<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 13 — RENTABILITÉ (M15) + SIMULATION (M16) + COCKPIT IA (M17)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════ MODULE 15 : RENTABILITÉ & COÛTS ═══════════

        // ── Centres de profit (cantine, transport, uniformes, activités) ──
        Schema::create('centres_profit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 100)->comment('Ex: Cantine, Transport, Uniformes');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code'], 'cp_etab_code_unique');
        });

        // ── Revenus par centre / service ──
        Schema::create('revenus_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('centre_profit_id')->nullable()->constrained('centres_profit')->nullOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables');
            $table->string('mois', 7);
            $table->string('libelle', 200);
            $table->decimal('montant', 14, 0);
            $table->enum('type', ['recurrent', 'ponctuel'])->default('recurrent');
            $table->string('source', 100)->nullable()->comment('Inscription, cantine, transport, etc.');
            $table->timestamps();

            $table->index(['etablissement_id', 'mois'], 'rs_etab_mois_idx');
        });

        // ── Analyse de rentabilité (snapshots calculés par IA) ──
        Schema::create('analyses_rentabilite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables');
            $table->string('mois', 7)->nullable()->comment('Null = annuel');
            $table->enum('niveau_analyse', ['etablissement', 'classe', 'niveau', 'filiere', 'service', 'centre_profit']);
            $table->string('cible_label', 100)->comment('Ex: 3ème A, Cantine, Série D');
            $table->unsignedBigInteger('cible_id')->nullable();

            // Données financières
            $table->decimal('revenus', 14, 0)->default(0);
            $table->decimal('couts_directs', 14, 0)->default(0)->comment('Salaires enseignants, fournitures');
            $table->decimal('couts_indirects', 14, 0)->default(0)->comment('Part loyer, électricité, admin');
            $table->decimal('cout_total', 14, 0)->default(0);
            $table->decimal('marge_brute', 14, 0)->default(0);
            $table->decimal('marge_nette', 14, 0)->default(0);
            $table->decimal('taux_marge', 5, 2)->default(0)->comment('Marge / Revenus en %');
            $table->boolean('rentable')->default(true);

            // Par élève
            $table->unsignedSmallInteger('nb_eleves')->default(0);
            $table->decimal('revenu_par_eleve', 12, 0)->default(0);
            $table->decimal('cout_par_eleve', 12, 0)->default(0);
            $table->decimal('marge_par_eleve', 12, 0)->default(0);

            // Par enseignant (si applicable)
            $table->unsignedSmallInteger('nb_enseignants')->default(0);
            $table->decimal('cout_par_enseignant', 12, 0)->default(0);

            $table->json('details')->nullable()->comment('Ventilation détaillée');
            $table->timestamps();

            $table->index(['etablissement_id', 'exercice_id', 'niveau_analyse'], 'ar_etab_exo_niv_idx');
        });

        // ── Seuil de rentabilité ──
        Schema::create('seuils_rentabilite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables');
            $table->decimal('charges_fixes_totales', 14, 0)->default(0);
            $table->decimal('charges_variables_totales', 14, 0)->default(0);
            $table->decimal('revenu_moyen_par_eleve', 12, 0)->default(0);
            $table->decimal('cout_variable_par_eleve', 12, 0)->default(0);
            $table->decimal('marge_contribution_unitaire', 12, 0)->default(0);
            $table->unsignedSmallInteger('nb_eleves_seuil')->default(0)->comment('Nombre minimum élèves');
            $table->decimal('revenu_seuil', 14, 0)->default(0)->comment('Revenu minimum FCFA');
            $table->unsignedSmallInteger('nb_eleves_actuels')->default(0);
            $table->decimal('marge_securite_pourcent', 5, 2)->default(0);
            $table->boolean('au_dessus_seuil')->default(true);
            $table->json('details_calcul')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'exercice_id'], 'sr_etab_exo_unique');
        });

        // ═══════════ MODULE 16 : SIMULATION FINANCIÈRE ═══════════

        // ── Scénarios de simulation ──
        Schema::create('simulations_financieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('cree_par')->constrained('users');
            $table->string('nom', 200)->comment('Ex: Augmentation scolarité +10%, 50 élèves de plus');
            $table->text('description')->nullable();
            $table->enum('type', [
                'augmentation_effectif', 'reduction_effectif',
                'augmentation_tarif', 'reduction_tarif',
                'ajout_service', 'suppression_service',
                'recrutement', 'reduction_personnel',
                'investissement', 'reduction_couts',
                'scenario_libre'
            ]);
            $table->enum('horizon', ['3_mois', '6_mois', '1_an', '2_ans', '3_ans'])->default('1_an');

            // Paramètres de simulation
            $table->json('parametres')->comment('JSON: {nb_eleves_supplementaires: 50, hausse_scolarite_pourcent: 10}');

            // Résultats calculés par IA
            $table->json('resultats')->nullable()->comment('JSON: revenus, depenses, marge, tresorerie par mois');
            $table->decimal('impact_revenus', 14, 0)->nullable();
            $table->decimal('impact_depenses', 14, 0)->nullable();
            $table->decimal('impact_marge', 14, 0)->nullable();
            $table->decimal('impact_tresorerie', 14, 0)->nullable();
            $table->decimal('roi_pourcent', 7, 2)->nullable()->comment('Retour sur investissement');
            $table->unsignedSmallInteger('delai_rentabilite_mois')->nullable();

            $table->enum('statut', ['brouillon', 'calcule', 'archive'])->default('brouillon');
            $table->boolean('favori')->default(false);
            $table->timestamps();

            $table->index(['etablissement_id', 'type'], 'sim_etab_type_idx');
        });

        // ── Projections financières (calculées automatiquement) ──
        Schema::create('projections_financieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables');
            $table->string('mois_projection', 7)->comment('Mois futur projeté');
            $table->decimal('revenus_projetes', 14, 0)->default(0);
            $table->decimal('depenses_projetees', 14, 0)->default(0);
            $table->decimal('tresorerie_projetee', 14, 0)->default(0);
            $table->decimal('confiance_pourcent', 5, 2)->default(0)->comment('Niveau de confiance IA');
            $table->json('hypotheses')->nullable();
            $table->date('date_calcul');
            $table->timestamps();

            $table->index(['etablissement_id', 'mois_projection'], 'pf_etab_mois_idx');
        });

        // ═══════════ MODULE 17 : COCKPIT DIRIGEANT IA ═══════════

        // ── Score de santé financière ──
        Schema::create('scores_financiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->date('date_calcul');

            // Score global 0-100
            $table->decimal('score_global', 5, 2);
            $table->enum('indicateur', ['vert', 'orange', 'rouge'])->comment('Basé sur score_global');

            // Sous-scores
            $table->decimal('score_tresorerie', 5, 2)->nullable()->comment('Liquidité, solvabilité');
            $table->decimal('score_recouvrement', 5, 2)->nullable();
            $table->decimal('score_rentabilite', 5, 2)->nullable();
            $table->decimal('score_budget', 5, 2)->nullable()->comment('Respect du budget');
            $table->decimal('score_masse_salariale', 5, 2)->nullable();
            $table->decimal('score_endettement', 5, 2)->nullable();

            // Indicateurs clés
            $table->decimal('ratio_liquidite', 5, 2)->nullable()->comment('Actif court terme / Passif court terme');
            $table->decimal('ratio_ms_revenus', 5, 2)->nullable()->comment('Masse salariale / Revenus');
            $table->decimal('ratio_charges_fixes', 5, 2)->nullable()->comment('Charges fixes / Revenus');
            $table->decimal('fonds_roulement_mois', 4, 1)->nullable()->comment('Combien de mois de fonctionnement en réserve');

            // IA
            $table->json('risques_detectes')->nullable()->comment('Liste des risques identifiés');
            $table->json('recommandations')->nullable()->comment('Actions suggérées par IA');
            $table->json('tendances')->nullable()->comment('Évolution sur 3-6 mois');

            $table->timestamps();

            $table->index(['etablissement_id', 'date_calcul'], 'sf_etab_date_idx');
        });

        // ── Alertes financières ──
        Schema::create('alertes_financieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->enum('type', [
                'depassement_budget', 'tresorerie_basse', 'masse_salariale_elevee',
                'depense_anormale', 'depense_inhabituelle', 'deficit_structurel',
                'impaye_critique', 'risque_liquidite', 'seuil_rentabilite',
                'ecart_budget', 'charge_recurrente_oubliee', 'anomalie_comptable'
            ]);
            $table->enum('gravite', ['info', 'warning', 'critique'])->default('warning');
            $table->string('titre', 200);
            $table->text('message');
            $table->text('recommandation_ia')->nullable();
            $table->decimal('montant_concerne', 14, 0)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('lue')->default(false);
            $table->boolean('traitee')->default(false);
            $table->foreignId('traitee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_prise')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'traitee', 'gravite'], 'af_etab_trait_grav_idx');
        });

        // ── Tableau de bord financier (snapshots quotidiens pour historique) ──
        Schema::create('snapshots_financiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->date('date_snapshot');

            // Trésorerie
            $table->decimal('solde_caisse', 14, 0)->default(0);
            $table->decimal('solde_banque', 14, 0)->default(0);
            $table->decimal('solde_mobile_money', 14, 0)->default(0);
            $table->decimal('tresorerie_totale', 14, 0)->default(0);

            // Revenus/Dépenses du jour
            $table->decimal('revenus_jour', 14, 0)->default(0);
            $table->decimal('depenses_jour', 14, 0)->default(0);

            // Cumuls mois
            $table->decimal('revenus_mois_cumul', 14, 0)->default(0);
            $table->decimal('depenses_mois_cumul', 14, 0)->default(0);

            // Cumuls exercice
            $table->decimal('revenus_exercice_cumul', 14, 0)->default(0);
            $table->decimal('depenses_exercice_cumul', 14, 0)->default(0);
            $table->decimal('resultat_exercice', 14, 0)->default(0);

            // Recouvrement
            $table->decimal('creances_totales', 14, 0)->default(0);
            $table->unsignedSmallInteger('nb_impayes')->default(0);

            $table->timestamps();

            $table->unique(['etablissement_id', 'date_snapshot'], 'snap_etab_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshots_financiers');
        Schema::dropIfExists('alertes_financieres');
        Schema::dropIfExists('scores_financiers');
        Schema::dropIfExists('projections_financieres');
        Schema::dropIfExists('simulations_financieres');
        Schema::dropIfExists('seuils_rentabilite');
        Schema::dropIfExists('analyses_rentabilite');
        Schema::dropIfExists('revenus_services');
        Schema::dropIfExists('centres_profit');
    }
};
