<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 05 — GESTION DES ENSEIGNANTS (Module 3)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Enseignants ──
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('matricule_mena', 30)->nullable()->comment('Matricule fonctionnaire MENA');
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance')->nullable();
            $table->string('telephone', 20);
            $table->string('telephone_2', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->string('diplome_plus_eleve', 100)->nullable();
            $table->string('specialite', 100)->nullable();
            $table->enum('statut', ['titulaire', 'contractuel', 'vacataire', 'stagiaire'])->default('titulaire');
            $table->date('date_prise_fonction')->nullable();
            $table->decimal('salaire_base', 12, 0)->nullable()->comment('Salaire en FCFA');
            $table->string('banque', 100)->nullable();
            $table->string('numero_compte', 50)->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('score_ponctualite', 5, 2)->default(100)->comment('Score IA 0-100');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['etablissement_id', 'actif']);
        });

        // ── Affectations matière-classe-enseignant ──
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->decimal('volume_horaire_hebdo', 4, 1)->default(2);
            $table->boolean('est_professeur_principal')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['enseignant_id', 'classe_id', 'matiere_id', 'annee_scolaire_id'], 'affectation_unique');
        });

        // ── Congés et permissions ──
        Schema::create('conges_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->enum('type', ['conge_maladie', 'conge_maternite', 'permission', 'absence_autorisee', 'formation', 'mission']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('motif');
            $table->string('piece_justificative_path')->nullable();
            $table->enum('statut', ['demande', 'approuve', 'refuse', 'annule'])->default('demande');
            $table->foreignId('approuve_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_approbation')->nullable();
            $table->foreignId('remplacant_id')->nullable()->constrained('enseignants')->nullOnDelete();
            $table->timestamps();

            $table->index(['enseignant_id', 'date_debut', 'date_fin']);
        });

        // ── Paie enseignants ──
        Schema::create('paie_enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('mois', 7)->comment('Ex: 2026-04');
            $table->decimal('salaire_base', 12, 0);
            $table->decimal('primes', 12, 0)->default(0);
            $table->decimal('retenues', 12, 0)->default(0);
            $table->decimal('retenue_absence', 12, 0)->default(0)->comment('Déduction pour absences');
            $table->decimal('net_a_payer', 12, 0);
            $table->unsignedTinyInteger('jours_presents')->default(0);
            $table->unsignedTinyInteger('jours_absents')->default(0);
            $table->unsignedTinyInteger('jours_retard')->default(0);
            $table->enum('statut_paiement', ['en_attente', 'paye', 'annule'])->default('en_attente');
            $table->date('date_paiement')->nullable();
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference_paiement', 100)->nullable();
            $table->timestamps();

            $table->unique(['enseignant_id', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('paie_enseignants');
        Schema::dropIfExists('conges_permissions');
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('enseignants');

        Schema::enableForeignKeyConstraints();
    }
};
