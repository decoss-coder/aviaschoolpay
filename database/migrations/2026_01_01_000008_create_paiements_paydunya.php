<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 08 — PAIEMENTS PAYDUNYA & SCOLARITÉ FCFA (Module 6)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Plans de paiement ──
        Schema::create('plans_paiement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 100)->comment('Ex: Paiement trimestriel, Paiement mensuel');
            $table->unsignedTinyInteger('nombre_echeances');
            $table->json('echeances_config')->nullable()->comment('JSON: [{mois: 10, pourcentage: 40}, ...]');
            $table->boolean('par_defaut')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // ── Échéances par élève ──
        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('plan_paiement_id')->nullable()->constrained('plans_paiement')->nullOnDelete();
            $table->unsignedTinyInteger('numero_echeance');
            $table->string('libelle', 100)->comment('Ex: 1ère tranche, 2ème tranche');
            $table->decimal('montant', 12, 0)->comment('Montant en FCFA');
            $table->date('date_echeance');
            $table->decimal('montant_paye', 12, 0)->default(0);
            $table->decimal('reste_a_payer', 12, 0);
            $table->enum('statut', ['a_venir', 'en_cours', 'paye', 'en_retard', 'partiellement_paye'])->default('a_venir');
            $table->unsignedSmallInteger('nb_relances_envoyees')->default(0);
            $table->date('derniere_relance_date')->nullable();
            $table->timestamps();

            $table->index(['inscription_id', 'statut']);
        });

        // ── Paiements reçus ──
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('echeance_id')->nullable()->constrained('echeances')->nullOnDelete();
            $table->foreignId('encaisse_par')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 50)->unique()->comment('Référence unique AviaSchoolPay');
            $table->decimal('montant', 12, 0)->comment('Montant en FCFA');
            $table->date('date_paiement');

            $table->enum('mode', [
                'orange_money', 'mtn_money', 'moov_money', 'wave',
                'carte_bancaire', 'virement', 'especes', 'cheque'
            ]);
            $table->enum('statut', ['en_attente', 'confirme', 'echoue', 'rembourse', 'annule'])->default('en_attente');

            // PayDunya
            $table->string('paydunya_token', 100)->nullable()->comment('Token de transaction PayDunya');
            $table->string('paydunya_invoice_url')->nullable()->comment('URL facture PayDunya');
            $table->string('paydunya_response_code', 20)->nullable();
            $table->text('paydunya_response_text')->nullable();
            $table->json('paydunya_metadata')->nullable()->comment('Réponse complète PayDunya');
            $table->timestamp('paydunya_callback_at')->nullable();

            // Reçu
            $table->string('numero_recu', 50)->nullable()->comment('Ex: REC-2026-04-0001');
            $table->string('recu_pdf_path')->nullable();
            $table->boolean('recu_envoye_sms')->default(false);

            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'date_paiement']);
            $table->index(['eleve_id', 'statut']);
            $table->index('paydunya_token');
        });

        // ── Relances automatiques ──
        Schema::create('relances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('echeance_id')->nullable()->constrained('echeances')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parents_tuteurs')->nullOnDelete();
            $table->enum('canal', ['sms', 'whatsapp', 'email', 'notification_app'])->default('sms');
            $table->text('message');
            $table->decimal('montant_du', 12, 0);
            $table->enum('statut_envoi', ['programme', 'envoye', 'delivre', 'echoue'])->default('programme');
            $table->timestamp('date_envoi')->nullable();
            $table->string('lien_paiement_paydunya')->nullable()->comment('Lien PayDunya personnalisé');
            $table->timestamps();

            $table->index(['etablissement_id', 'statut_envoi']);
        });

        // ── Tableau de recouvrement (snapshot mensuel) ──
        Schema::create('recouvrement_mensuel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('mois', 7);
            $table->decimal('total_attendu', 14, 0)->default(0);
            $table->decimal('total_encaisse', 14, 0)->default(0);
            $table->decimal('total_reste', 14, 0)->default(0);
            $table->decimal('taux_recouvrement', 5, 2)->default(0);
            $table->unsignedSmallInteger('eleves_a_jour')->default(0);
            $table->unsignedSmallInteger('eleves_en_retard')->default(0);
            $table->unsignedSmallInteger('eleves_impaye_total')->default(0);
            $table->json('repartition_par_mode')->nullable()->comment('JSON: {orange_money: 5000000, ...}');
            $table->timestamps();

            $table->unique(['etablissement_id', 'annee_scolaire_id', 'mois'], 'recouv_etab_annee_mois_unique');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('recouvrement_mensuel');
        Schema::dropIfExists('relances');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('echeances');
        Schema::dropIfExists('plans_paiement');

        Schema::enableForeignKeyConstraints();
    }
};
