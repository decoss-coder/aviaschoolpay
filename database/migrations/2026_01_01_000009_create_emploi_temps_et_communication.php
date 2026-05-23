<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 09 — EMPLOI DU TEMPS (Module 7) + COMMUNICATION (Module 8)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════ MODULE 7 : EMPLOI DU TEMPS ═══════════

        // ── Créneaux horaires ──
        Schema::create('creneaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('libelle', 50)->comment('Ex: 1er cours, 2ème cours, Récréation');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->enum('type', ['cours', 'recreation', 'pause_dejeuner'])->default('cours');
            $table->unsignedTinyInteger('ordre');
            $table->timestamps();

            $table->unique(['etablissement_id', 'ordre']);
        });

        // ── Emploi du temps ──
        Schema::create('emploi_du_temps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->nullOnDelete();
            $table->foreignId('creneau_id')->constrained('creneaux')->cascadeOnDelete();
            $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
            $table->date('valide_du')->nullable();
            $table->date('valide_au')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['classe_id', 'jour']);
            $table->index(['enseignant_id', 'jour']);
            $table->index(['salle_id', 'jour']);
        });

        // ═══════════ MODULE 8 : COMMUNICATION ═══════════

        // ── Messages internes ──
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('expediteur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('destinataire_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained('classes')->nullOnDelete()->comment('Si message de groupe');
            $table->enum('type_destinataire', ['individuel', 'classe', 'niveau', 'tous_parents', 'tous_enseignants', 'tous'])->default('individuel');
            $table->string('sujet', 200);
            $table->text('contenu');
            $table->string('piece_jointe_path')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamp('lu_at')->nullable();
            $table->boolean('important')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['destinataire_id', 'lu']);
            $table->index(['etablissement_id', 'created_at']);
        });

        // ── Annonces / Circulaires ──
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre', 200);
            $table->text('contenu');
            $table->enum('type', ['annonce', 'circulaire', 'convocation', 'evenement', 'urgent']);
            $table->enum('audience', ['tous', 'parents', 'enseignants', 'eleves', 'personnel']);
            $table->string('piece_jointe_path')->nullable();
            $table->date('date_debut_affichage');
            $table->date('date_fin_affichage')->nullable();
            $table->boolean('envoyer_sms')->default(false);
            $table->boolean('envoyer_notification')->default(true);
            $table->boolean('publiee')->default(false);
            $table->timestamps();

            $table->index(['etablissement_id', 'publiee']);
        });

        // ── Notifications push / SMS ──
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('titre', 200);
            $table->text('message');
            $table->enum('canal', ['app', 'sms', 'email', 'whatsapp'])->default('app');
            $table->enum('type', [
                'paiement', 'note', 'absence', 'pointage', 'annonce',
                'bulletin', 'relance', 'desps', 'alerte_ia', 'systeme'
            ]);
            $table->string('lien_action')->nullable()->comment('URL vers la page concernée');
            $table->boolean('lue')->default(false);
            $table->timestamp('lue_at')->nullable();
            $table->boolean('envoyee')->default(false);
            $table->timestamp('envoyee_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lue', 'created_at']);
            $table->index(['etablissement_id', 'type']);
        });

        // ── SMS envoyés (log) ──
        Schema::create('sms_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('telephone_destinataire', 20);
            $table->text('message');
            $table->enum('type', ['relance', 'pointage', 'note', 'annonce', 'pin_journalier', 'alerte', 'autre']);
            $table->enum('statut', ['en_attente', 'envoye', 'delivre', 'echoue'])->default('en_attente');
            $table->string('provider', 50)->nullable()->comment('Ex: Twilio, Orange SMS API');
            $table->string('provider_message_id', 100)->nullable();
            $table->decimal('cout_fcfa', 8, 0)->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('sms_log');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('annonces');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('emploi_du_temps');
        Schema::dropIfExists('creneaux');

        Schema::enableForeignKeyConstraints();
    }
};
