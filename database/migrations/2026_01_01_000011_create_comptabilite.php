<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 11 — COMPTABILITÉ SCOLAIRE (Module 12)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Plan comptable simplifié école ──
        Schema::create('comptes_comptables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('numero', 20)->comment('Ex: 701, 601, 411, 512');
            $table->string('libelle', 200)->comment('Ex: Scolarité, Salaires, Fournitures');
            $table->enum('type', ['actif', 'passif', 'charge', 'produit', 'tresorerie']);
            $table->enum('categorie', [
                'scolarite', 'inscription', 'cantine', 'transport', 'uniformes',
                'activites', 'subventions', 'autres_revenus',
                'salaires', 'charges_sociales', 'fournitures', 'maintenance',
                'loyer', 'electricite', 'eau', 'telecom', 'assurances',
                'transport_charge', 'cantine_charge', 'formation', 'impots',
                'amortissements', 'autres_charges',
                'caisse', 'banque', 'mobile_money',
                'creances', 'dettes',
            ])->nullable();
            $table->string('parent_numero', 20)->nullable()->comment('Compte parent pour hiérarchie');
            $table->decimal('solde_initial', 14, 0)->default(0);
            $table->decimal('solde_actuel', 14, 0)->default(0);
            $table->boolean('actif')->default(true);
            $table->boolean('systeme')->default(false)->comment('Compte créé auto, non supprimable');
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero'], 'cc_etab_num_unique');
            $table->index(['etablissement_id', 'type']);
        });

        // ── Exercices comptables ──
        Schema::create('exercices_comptables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('libelle', 50)->comment('Ex: Exercice 2025-2026');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('en_cours')->default(false);
            $table->boolean('cloture')->default(false);
            $table->decimal('solde_ouverture', 14, 0)->default(0);
            $table->decimal('solde_cloture', 14, 0)->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'annee_scolaire_id'], 'ec_etab_annee_unique');
        });

        // ── Écritures comptables (journal) ──
        Schema::create('ecritures_comptables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices_comptables')->cascadeOnDelete();
            $table->string('numero_piece', 30)->comment('Ex: EC-2026-04-0001');
            $table->date('date_ecriture');
            $table->string('libelle', 300);
            $table->foreignId('compte_debit_id')->constrained('comptes_comptables');
            $table->foreignId('compte_credit_id')->constrained('comptes_comptables');
            $table->decimal('montant', 14, 0)->comment('En FCFA');
            $table->enum('type_piece', [
                'paiement_scolarite', 'depense', 'salaire', 'virement_interne',
                'remboursement', 'ajustement', 'ouverture', 'cloture', 'autre'
            ]);
            $table->string('reference_externe', 100)->nullable()->comment('Lien vers paiement_id, depense_id, etc.');
            $table->string('reference_type', 50)->nullable()->comment('paiement, depense, paie_enseignant');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('saisie_par')->constrained('users');
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('valide')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'date_ecriture'], 'ecr_etab_date_idx');
            $table->index(['exercice_id', 'type_piece'], 'ecr_exo_type_idx');
        });

        // ── Rapprochement bancaire ──
        Schema::create('rapprochements_bancaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('compte_id')->constrained('comptes_comptables');
            $table->string('mois', 7);
            $table->decimal('solde_releve_bancaire', 14, 0);
            $table->decimal('solde_comptable', 14, 0);
            $table->decimal('ecart', 14, 0)->default(0);
            $table->boolean('rapproche')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'compte_id', 'mois'], 'rappr_etab_cpt_mois_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapprochements_bancaires');
        Schema::dropIfExists('ecritures_comptables');
        Schema::dropIfExists('exercices_comptables');
        Schema::dropIfExists('comptes_comptables');
    }
};
