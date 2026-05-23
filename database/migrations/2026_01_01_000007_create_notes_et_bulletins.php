<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 07 — NOTES, ÉVALUATIONS ET BULLETINS (Module 5)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Types d'évaluation ──
        Schema::create('types_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 50)->comment('Ex: Devoir, Interrogation, Composition, Examen blanc');
            $table->string('code', 20);
            $table->decimal('poids_pourcentage', 5, 2)->default(100)->comment('Poids dans la moyenne du trimestre');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // ── Évaluations ──
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained('trimestres')->cascadeOnDelete();
            $table->foreignId('type_evaluation_id')->constrained('types_evaluation')->cascadeOnDelete();
            $table->string('titre', 200)->comment('Ex: Devoir n°1 - Équations du 2nd degré');
            $table->date('date_evaluation');
            $table->decimal('note_sur', 4, 1)->default(20)->comment('Barème: 10, 20, 40...');
            $table->decimal('coefficient', 3, 1)->default(1);
            $table->text('description')->nullable();
            $table->enum('statut', ['brouillon', 'en_saisie', 'cloturee', 'validee'])->default('brouillon');
            $table->boolean('notes_publiees')->default(false);
            $table->timestamps();

            $table->index(['classe_id', 'trimestre_id']);
            $table->index(['matiere_id', 'trimestre_id']);
        });

        // ── Notes individuelles ──
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->decimal('note', 5, 2)->nullable()->comment('Note obtenue');
            $table->boolean('absent')->default(false);
            $table->boolean('dispense')->default(false);
            $table->text('observation')->nullable();
            $table->foreignId('saisie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_saisie')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'eleve_id']);
            $table->index('eleve_id');
        });

        // ── Moyennes par matière par trimestre ──
        Schema::create('moyennes_matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained('trimestres')->cascadeOnDelete();
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->decimal('moyenne_ponderee', 5, 2)->nullable()->comment('Moyenne × coefficient');
            $table->unsignedSmallInteger('rang_classe')->nullable();
            $table->decimal('note_min_classe', 5, 2)->nullable();
            $table->decimal('note_max_classe', 5, 2)->nullable();
            $table->decimal('moyenne_classe', 5, 2)->nullable();
            $table->string('appreciation', 200)->nullable()->comment('Peut être générée par IA');
            $table->timestamps();

            $table->unique(['eleve_id', 'matiere_id', 'trimestre_id']);
            $table->index(['classe_id', 'trimestre_id']);
        });

        // ── Moyennes générales trimestrielles ──
        Schema::create('moyennes_generales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained('trimestres')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->decimal('total_points', 8, 2)->nullable();
            $table->decimal('total_coefficients', 6, 1)->nullable();
            $table->unsignedSmallInteger('rang')->nullable();
            $table->unsignedSmallInteger('effectif_classe')->nullable();
            $table->decimal('moyenne_premier', 5, 2)->nullable();
            $table->decimal('moyenne_dernier', 5, 2)->nullable();
            $table->decimal('moyenne_classe', 5, 2)->nullable();
            $table->string('appreciation_generale', 200)->nullable();
            $table->enum('mention', ['tableau_honneur', 'encouragements', 'felicitations', 'avertissement', 'blame', 'aucune'])->default('aucune');
            $table->unsignedSmallInteger('total_absences')->default(0);
            $table->unsignedSmallInteger('absences_justifiees')->default(0);
            $table->unsignedSmallInteger('total_retards')->default(0);
            $table->timestamps();

            $table->unique(['eleve_id', 'trimestre_id']);
            $table->index(['classe_id', 'trimestre_id']);
        });

        // ── Moyennes annuelles ──
        Schema::create('moyennes_annuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->decimal('moyenne_t1', 5, 2)->nullable();
            $table->decimal('moyenne_t2', 5, 2)->nullable();
            $table->decimal('moyenne_t3', 5, 2)->nullable();
            $table->decimal('moyenne_annuelle', 5, 2)->nullable();
            $table->unsignedSmallInteger('rang_annuel')->nullable();
            $table->enum('decision', ['passage', 'redoublement', 'exclusion', 'en_attente'])->default('en_attente');
            $table->string('classe_suivante', 30)->nullable()->comment('Classe affectée pour l année suivante');
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']);
        });

        // ── Bulletins générés (PDF) ──
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained('trimestres')->cascadeOnDelete();
            $table->string('fichier_pdf_path')->nullable();
            $table->enum('statut', ['brouillon', 'valide', 'publie', 'imprime'])->default('brouillon');
            $table->boolean('signe_par_directeur')->default(false);
            $table->boolean('remis_parent')->default(false);
            $table->date('date_remise')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'trimestre_id']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('bulletins');
        Schema::dropIfExists('moyennes_annuelles');
        Schema::dropIfExists('moyennes_generales');
        Schema::dropIfExists('moyennes_matieres');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('types_evaluation');

        Schema::enableForeignKeyConstraints();
    }
};
