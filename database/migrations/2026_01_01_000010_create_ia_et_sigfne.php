<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 10 — IA AIDE À LA DÉCISION (Module 9) + SIGFNE/DESPS (Module 11)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════ MODULE 9 : IA AIDE À LA DÉCISION ═══════════

        // ── Prédictions IA ──
        Schema::create('predictions_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->enum('type', [
                'recouvrement',          // Prédiction taux de recouvrement
                'reussite_examen',       // Prédiction réussite BEPC/BAC
                'decrochage_eleve',      // Détection décrochage
                'orientation',           // Suggestion orientation 3ème
                'absence_enseignant',    // Prédiction absence
                'anomalie_notes',        // Détection anomalies notation
                'performance_classe',    // Analyse comparative classes
                'budget',               // Projection budgétaire
            ]);
            $table->string('cible_type')->nullable()->comment('Ex: eleve, enseignant, classe');
            $table->unsignedBigInteger('cible_id')->nullable();
            $table->decimal('score_confiance', 5, 2)->nullable()->comment('0-100');
            $table->json('donnees_entree')->nullable();
            $table->json('resultat')->nullable();
            $table->text('recommandation')->nullable();
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'critique'])->default('moyenne');
            $table->boolean('vue_par_directeur')->default(false);
            $table->boolean('action_prise')->default(false);
            $table->text('action_description')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'type', 'created_at']);
            $table->index(['cible_type', 'cible_id']);
        });

        // ── Score de santé établissement (généré par IA) ──
        Schema::create('scores_sante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->date('date_calcul');
            $table->decimal('score_global', 5, 2)->comment('Score 0-100');
            $table->decimal('score_pedagogie', 5, 2)->nullable();
            $table->decimal('score_finances', 5, 2)->nullable();
            $table->decimal('score_presence', 5, 2)->nullable();
            $table->decimal('score_communication', 5, 2)->nullable();
            $table->decimal('score_conformite_desps', 5, 2)->nullable();
            $table->json('details')->nullable()->comment('Détail des facteurs');
            $table->json('recommandations')->nullable()->comment('Actions suggérées par IA');
            $table->timestamps();

            $table->index(['etablissement_id', 'date_calcul']);
        });

        // ── Conversations IA (chatbot directeur/parent) ──
        Schema::create('conversations_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->text('question');
            $table->text('reponse');
            $table->string('contexte', 100)->nullable()->comment('Ex: finances, pedagogie, pointage');
            $table->json('sources_donnees')->nullable()->comment('Tables/données utilisées pour la réponse');
            $table->unsignedSmallInteger('satisfaction')->nullable()->comment('1-5 étoiles');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // ═══════════ MODULE 11 : CONFORMITÉ SIGFNE / DESPS ═══════════

        // ── Remontées de moyennes vers SIGFNE ──
        Schema::create('remontees_sigfne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained('trimestres')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->enum('plateforme', ['agfne', 'agcp', 'agce_deco', 'agcepe_deco'])->default('agfne');
            $table->enum('type', ['moyennes_trimestrielles', 'moyennes_annuelles', 'dfa', 'fichier_examens'])->default('moyennes_trimestrielles');
            $table->unsignedSmallInteger('total_eleves')->default(0);
            $table->unsignedSmallInteger('eleves_remontes')->default(0);
            $table->unsignedSmallInteger('eleves_en_erreur')->default(0);
            $table->unsignedSmallInteger('eleves_sans_matricule')->default(0);
            $table->enum('statut', ['preparation', 'en_cours', 'termine', 'erreur', 'valide_drena'])->default('preparation');
            $table->string('fichier_export_path')->nullable()->comment('Fichier CSV/Excel généré');
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_validation_drena')->nullable();
            $table->foreignId('envoye_par')->nullable()->constrained('users')->nullOnDelete();
            $table->json('erreurs_detail')->nullable()->comment('JSON des erreurs rencontrées');
            $table->json('reponse_sigfne')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'trimestre_id']);
            $table->index(['statut']);
        });

        // ── Détail remontée par élève ──
        Schema::create('remontee_eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remontee_sigfne_id')->constrained('remontees_sigfne')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->string('matricule_desps', 20)->nullable();
            $table->decimal('moyenne_remontee', 5, 2)->nullable();
            $table->enum('statut', ['ok', 'erreur_matricule', 'erreur_moyenne', 'non_trouve', 'en_attente'])->default('en_attente');
            $table->text('message_erreur')->nullable();
            $table->timestamps();

            $table->index('remontee_sigfne_id');
        });

        // ── Décisions de fin d'année (DFA) ──
        Schema::create('decisions_fin_annee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->decimal('moyenne_annuelle', 5, 2);
            $table->enum('decision', ['passage', 'redoublement', 'exclusion', 'orientation'])->default('passage');
            $table->string('classe_proposee', 50)->nullable()->comment('Classe suivante proposée');
            $table->foreignId('serie_proposee_id')->nullable()->constrained('series')->nullOnDelete()->comment('Pour orientation en 2nde');
            $table->string('suggestion_ia', 200)->nullable()->comment('Suggestion IA d orientation');

            // Workflow validation
            $table->enum('statut_validation', [
                'proposition', 'valide_conseil_classe', 'valide_directeur',
                'soumis_sigfne', 'approuve_drena', 'refuse_drena'
            ])->default('proposition');
            $table->foreignId('valide_par_pp')->nullable()->constrained('users')->nullOnDelete()->comment('Prof principal');
            $table->timestamp('date_validation_pp')->nullable();
            $table->foreignId('valide_par_directeur')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation_directeur')->nullable();
            $table->timestamp('date_soumission_sigfne')->nullable();
            $table->timestamp('date_approbation_drena')->nullable();
            $table->text('motif_refus_drena')->nullable();

            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']);
            $table->index(['etablissement_id', 'annee_scolaire_id', 'statut_validation'], 'dfa_etab_annee_statut_idx');
        });

        // ── Transferts inter-établissements ──
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('etablissement_origine_id')->constrained('etablissements');
            $table->foreignId('etablissement_destination_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->string('etablissement_destination_nom')->nullable()->comment('Si hors réseau AviaSchoolPay');
            $table->string('etablissement_destination_code_desps', 20)->nullable();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->enum('type', ['transfert_sortant', 'transfert_entrant']);
            $table->enum('statut', ['demande', 'quitus_emis', 'accepte', 'refuse', 'annule'])->default('demande');
            $table->date('date_demande');
            $table->date('date_effectif')->nullable();
            $table->string('motif', 200)->nullable();
            $table->string('fiche_transfert_path')->nullable()->comment('Fiche officielle DESPS PDF');
            $table->string('quitus_path')->nullable();
            $table->string('numero_decision_sigfne', 50)->nullable();
            $table->timestamps();

            $table->index(['eleve_id', 'annee_scolaire_id']);
        });

        // ── Inscriptions en ligne (suivi campagne DESPS) ──
        Schema::create('inscriptions_en_ligne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('matricule_desps', 20);
            $table->enum('canal', ['orange', 'mtn', 'moov', 'tresor_pay', 'web'])->nullable();
            $table->enum('statut', ['pre_inscrit', 'confirme', 'echec', 'non_reconnu'])->default('pre_inscrit');
            $table->text('message_erreur')->nullable();
            $table->timestamp('date_inscription_en_ligne')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'annee_scolaire_id'], 'insc_ligne_etab_annee_idx');
        });

        // ── Rapports DESPS générés ──
        Schema::create('rapports_desps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->enum('type', [
                'liste_nominative', 'rapport_conformite', 'statistiques_effectifs',
                'rapport_moyennes', 'rapport_dfa', 'fichier_orientation',
                'fichier_examens_bepc', 'fichier_examens_bac', 'fichier_cepe'
            ]);
            $table->string('titre', 200);
            $table->string('fichier_path');
            $table->enum('format', ['pdf', 'csv', 'xlsx'])->default('pdf');
            $table->boolean('genere_par_ia')->default(false);
            $table->foreignId('genere_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['etablissement_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('rapports_desps');
        Schema::dropIfExists('inscriptions_en_ligne');
        Schema::dropIfExists('transferts');
        Schema::dropIfExists('decisions_fin_annee');
        Schema::dropIfExists('remontee_eleves');
        Schema::dropIfExists('remontees_sigfne');
        Schema::dropIfExists('conversations_ia');
        Schema::dropIfExists('scores_sante');
        Schema::dropIfExists('predictions_ia');

        Schema::enableForeignKeyConstraints();
    }
};
