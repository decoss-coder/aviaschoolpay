<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 12 — DÉPENSES, TRÉSORERIE (Module 13) + BUDGET (Module 14)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════ MODULE 13 : DÉPENSES & TRÉSORERIE ═══════════

        // ── Catégories de dépenses ──
        Schema::create('categories_depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 100)->comment('Ex: Fournitures, Maintenance, Électricité');
            $table->string('code', 20);
            $table->enum('type', ['fixe', 'variable', 'exceptionnelle'])->default('variable');
            $table->boolean('recurrente')->default(false);
            $table->string('compte_comptable_numero', 20)->nullable()->comment('Lien au plan comptable');
            $table->string('icone', 50)->nullable();
            $table->string('couleur', 7)->nullable()->comment('Code hex pour UI');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code'], 'cat_dep_etab_code_uniq');
        });

        // ── Dépenses ──
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables')->cascadeOnDelete();
            $table->foreignId('categorie_id')->constrained('categories_depenses');
            $table->string('reference', 30)->unique()->comment('Ex: DEP-2026-04-0001');
            $table->string('libelle', 300);
            $table->text('description')->nullable();
            $table->decimal('montant', 14, 0)->comment('En FCFA');
            $table->date('date_depense');
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement', 'mobile_money', 'carte'])->default('especes');
            $table->string('beneficiaire', 200)->nullable()->comment('Fournisseur ou personne');
            $table->string('numero_facture', 50)->nullable();
            $table->string('justificatif_path')->nullable()->comment('Photo facture/reçu');
            $table->enum('frequence', ['ponctuelle', 'quotidienne', 'hebdomadaire', 'mensuelle', 'trimestrielle', 'annuelle'])->default('ponctuelle');

            // Workflow validation
            $table->enum('statut', ['brouillon', 'soumise', 'approuvee', 'rejetee', 'payee', 'annulee'])->default('brouillon');
            $table->foreignId('soumise_par')->constrained('users');
            $table->foreignId('approuvee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_approbation')->nullable();
            $table->text('motif_rejet')->nullable();

            // Comptabilisation
            $table->foreignId('ecriture_id')->nullable()->constrained('ecritures_comptables')->nullOnDelete();
            $table->boolean('comptabilisee')->default(false);

            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['etablissement_id', 'date_depense'], 'dep_etab_date_idx');
            $table->index(['etablissement_id', 'categorie_id'], 'dep_etab_cat_idx');
            $table->index(['etablissement_id', 'statut'], 'dep_etab_stat_idx');
        });

        // ── Comptes de trésorerie (caisse, banque, mobile money) ──
        Schema::create('comptes_tresorerie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 100)->comment('Ex: Caisse principale, Compte SGBCI, Orange Money');
            $table->enum('type', ['caisse', 'banque', 'mobile_money']);
            $table->string('numero_compte', 50)->nullable()->comment('Numéro de compte bancaire');
            $table->string('banque', 100)->nullable();
            $table->string('operateur', 50)->nullable()->comment('Orange, MTN, Wave pour mobile_money');
            $table->decimal('solde_initial', 14, 0)->default(0);
            $table->decimal('solde_actuel', 14, 0)->default(0);
            $table->string('compte_comptable_numero', 20)->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('principal')->default(false);
            $table->timestamps();

            $table->index(['etablissement_id', 'type']);
        });

        // ── Mouvements de trésorerie ──
        Schema::create('mouvements_tresorerie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('compte_tresorerie_id')->constrained('comptes_tresorerie');
            $table->enum('sens', ['entree', 'sortie']);
            $table->decimal('montant', 14, 0);
            $table->decimal('solde_avant', 14, 0);
            $table->decimal('solde_apres', 14, 0);
            $table->date('date_mouvement');
            $table->string('libelle', 300);
            $table->string('reference_type', 50)->nullable()->comment('paiement, depense, virement, salaire');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('saisie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['compte_tresorerie_id', 'date_mouvement'], 'mvt_cpt_date_idx');
            $table->index(['etablissement_id', 'date_mouvement'], 'mvt_etab_date_idx');
        });

        // ── Virements internes (entre comptes) ──
        Schema::create('virements_internes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('compte_source_id')->constrained('comptes_tresorerie');
            $table->foreignId('compte_destination_id')->constrained('comptes_tresorerie');
            $table->decimal('montant', 14, 0);
            $table->date('date_virement');
            $table->string('motif', 200)->nullable();
            $table->foreignId('effectue_par')->constrained('users');
            $table->timestamps();
        });

        // ═══════════ MODULE 14 : BUDGET & PILOTAGE ═══════════

        // ── Budgets ──
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables')->cascadeOnDelete();
            $table->string('libelle', 200)->comment('Ex: Budget annuel 2025-2026');
            $table->enum('periodicite', ['mensuel', 'trimestriel', 'annuel'])->default('annuel');
            $table->decimal('total_prevu_revenus', 14, 0)->default(0);
            $table->decimal('total_prevu_depenses', 14, 0)->default(0);
            $table->decimal('total_reel_revenus', 14, 0)->default(0);
            $table->decimal('total_reel_depenses', 14, 0)->default(0);
            $table->enum('statut', ['brouillon', 'valide', 'en_cours', 'cloture'])->default('brouillon');
            $table->foreignId('cree_par')->constrained('users');
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Lignes budgétaires ──
        Schema::create('lignes_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('categorie_depense_id')->nullable()->constrained('categories_depenses')->nullOnDelete();
            $table->string('compte_comptable_numero', 20)->nullable();
            $table->string('libelle', 200);
            $table->enum('type', ['revenu', 'depense']);
            $table->enum('service', ['scolarite', 'cantine', 'transport', 'activites', 'salaires', 'fonctionnement', 'investissement', 'autre'])->default('fonctionnement');
            $table->string('mois', 7)->nullable()->comment('Pour budget mensuel, ex: 2026-04');

            // Montants
            $table->decimal('montant_prevu', 14, 0)->default(0);
            $table->decimal('montant_reel', 14, 0)->default(0);
            $table->decimal('ecart', 14, 0)->default(0);
            $table->decimal('taux_realisation', 5, 2)->default(0)->comment('Réel / Prévu en %');

            // Alertes
            $table->boolean('alerte_depassement')->default(false);
            $table->unsignedTinyInteger('seuil_alerte_pourcent')->default(90);

            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['budget_id', 'type'], 'lb_budget_type_idx');
            $table->index(['budget_id', 'service'], 'lb_budget_serv_idx');
        });

        // ── Analyse masse salariale ──
        Schema::create('analyses_masse_salariale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('mois', 7);
            $table->decimal('total_salaires', 14, 0)->default(0);
            $table->decimal('total_charges_sociales', 14, 0)->default(0);
            $table->decimal('total_primes', 14, 0)->default(0);
            $table->decimal('masse_salariale_totale', 14, 0)->default(0);
            $table->decimal('revenus_mois', 14, 0)->default(0);
            $table->decimal('ratio_ms_revenus', 5, 2)->default(0)->comment('Masse salariale / Revenus en %');
            $table->unsignedSmallInteger('nb_enseignants')->default(0);
            $table->unsignedSmallInteger('nb_personnel_admin')->default(0);
            $table->decimal('cout_moyen_enseignant', 12, 0)->default(0);
            $table->timestamps();

            $table->unique(['etablissement_id', 'mois'], 'ams_etab_mois_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses_masse_salariale');
        Schema::dropIfExists('lignes_budgetaires');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('virements_internes');
        Schema::dropIfExists('mouvements_tresorerie');
        Schema::dropIfExists('comptes_tresorerie');
        Schema::dropIfExists('depenses');
        Schema::dropIfExists('categories_depenses');
    }
};
