<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 04 — GESTION DES ÉLÈVES (Module 2 - SIS)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Parents / Tuteurs ──
        Schema::create('parents_tuteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['M', 'F']);
            $table->string('telephone', 20);
            $table->string('telephone_2', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->string('profession', 100)->nullable();
            $table->enum('lien_parente', ['pere', 'mere', 'tuteur', 'tutrice', 'oncle', 'tante', 'frere', 'soeur', 'autre'])->default('pere');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['etablissement_id', 'telephone']);
        });

        // ── Élèves ──
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('matricule_interne', 30)->comment('Ex: AVIA-2026-0001');
            $table->string('matricule_desps', 20)->nullable()->comment('Matricule national DESPS ex: 17443596U');
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('lieu_naissance', 100)->nullable();
            $table->string('nationalite', 50)->default('Ivoirienne');
            $table->string('numero_extrait_naissance', 50)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('adresse')->nullable();
            $table->string('groupe_sanguin', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->text('maladies_chroniques')->nullable();
            $table->string('contact_urgence_nom', 100)->nullable();
            $table->string('contact_urgence_tel', 20)->nullable();
            $table->enum('statut', ['pre_inscrit', 'inscrit', 'transfere', 'radie', 'diplome', 'abandonne'])->default('pre_inscrit');
            $table->date('date_premiere_inscription')->nullable();
            $table->string('ecole_precedente', 200)->nullable();
            $table->text('observations')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['etablissement_id', 'matricule_interne']);
            $table->index('matricule_desps');
            $table->index(['etablissement_id', 'statut']);
        });

        // ── Pivot Élève-Parent ──
        Schema::create('eleve_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents_tuteurs')->cascadeOnDelete();
            $table->boolean('est_contact_principal')->default(false);
            $table->boolean('autorise_recuperation')->default(true);
            $table->timestamps();

            $table->unique(['eleve_id', 'parent_id']);
        });

        // ── Inscriptions (par année scolaire) ──
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->date('date_inscription');
            $table->enum('type', ['nouvelle', 'renouvellement', 'transfert_entrant'])->default('nouvelle');
            $table->enum('statut', ['en_attente', 'validee', 'annulee'])->default('en_attente');
            $table->decimal('montant_scolarite', 12, 0)->comment('Montant en FCFA');
            $table->decimal('reduction', 12, 0)->default(0)->comment('Bourse ou réduction en FCFA');
            $table->decimal('montant_net', 12, 0)->comment('Scolarité - réduction');
            $table->text('motif_reduction')->nullable();
            $table->boolean('dossier_complet')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']);
            $table->index(['classe_id', 'annee_scolaire_id']);
        });

        // ── Documents élèves ──
        Schema::create('documents_eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->enum('type', [
                'extrait_naissance', 'certificat_scolarite', 'bulletin',
                'photo_identite', 'carnet_vaccination', 'certificat_medical',
                'decision_affectation', 'quitus', 'autre'
            ]);
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->unsignedInteger('taille_octets')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->boolean('verifie')->default(false);
            $table->timestamps();
        });

        // ── Présence élèves (absences quotidiennes) ──
        Schema::create('presences_eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->date('date');
            $table->enum('statut', ['present', 'absent', 'retard', 'excuse'])->default('present');
            $table->enum('periode', ['matin', 'apres_midi', 'journee'])->default('journee');
            $table->string('motif')->nullable();
            $table->boolean('justifie')->default(false);
            $table->foreignId('saisie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['eleve_id', 'date', 'periode']);
            $table->index(['classe_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('presences_eleves');
        Schema::dropIfExists('documents_eleves');
        Schema::dropIfExists('inscriptions');
        Schema::dropIfExists('eleve_parent');
        Schema::dropIfExists('eleves');
        Schema::dropIfExists('parents_tuteurs');

        Schema::enableForeignKeyConstraints();
    }
};
