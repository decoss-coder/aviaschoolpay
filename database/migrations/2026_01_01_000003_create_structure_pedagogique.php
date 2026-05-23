<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 03 — STRUCTURE PÉDAGOGIQUE (niveaux, classes, matières)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Niveaux (cycle) ──
        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('code', 20)->comment('Ex: 6eme, 5eme, 4eme, 3eme, 2nde, 1ere, Tle');
            $table->string('libelle', 50)->comment('Ex: Sixième, Cinquième...');
            $table->enum('cycle', ['prescolaire', 'primaire', 'premier_cycle', 'second_cycle'])->default('premier_cycle');
            $table->unsignedTinyInteger('ordre')->comment('Pour le tri');
            $table->decimal('frais_scolarite_defaut', 12, 0)->default(0)->comment('Montant en FCFA');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code']);
        });

        // ── Séries (pour le second cycle) ──
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('A, C, D, etc.');
            $table->string('libelle', 100)->comment('Ex: Série A (Lettres), Série D (Sciences)');
            $table->timestamps();
        });

        // ── Classes ──
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained('niveaux')->cascadeOnDelete();
            $table->foreignId('serie_id')->nullable()->constrained('series')->nullOnDelete();
            $table->string('nom', 30)->comment('Ex: 3ème A, Tle D1');
            $table->unsignedSmallInteger('capacite')->default(60);
            $table->unsignedSmallInteger('effectif')->default(0);
            $table->foreignId('professeur_principal_id')->nullable()->comment('Enseignant PP');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'annee_scolaire_id', 'nom']);
            $table->index('niveau_id');
        });

        // ── Salles de classe (pour le pointage QR Code) ──
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 30)->comment('Ex: Salle A1, Labo Physique');
            $table->string('batiment', 50)->nullable();
            $table->unsignedSmallInteger('capacite')->default(60);
            $table->enum('type', ['classe', 'laboratoire', 'informatique', 'sport', 'amphitheatre', 'autre'])->default('classe');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'nom']);
        });

        // ── Matières ──
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom', 100)->comment('Ex: Mathématiques');
            $table->string('code', 20)->comment('Ex: MATH, FRAN, ANG');
            $table->unsignedTinyInteger('coefficient_defaut')->default(1);
            $table->string('groupe', 50)->nullable()->comment('Ex: Sciences, Lettres, Langues');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code']);
        });

        // ── Coefficients matière par niveau ──
        Schema::create('matiere_niveau', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained('niveaux')->cascadeOnDelete();
            $table->foreignId('serie_id')->nullable()->constrained('series')->nullOnDelete();
            $table->unsignedTinyInteger('coefficient');
            $table->decimal('volume_horaire_hebdo', 4, 1)->default(2)->comment('Heures par semaine');
            $table->boolean('obligatoire')->default(true);
            $table->timestamps();

            $table->unique(['matiere_id', 'niveau_id', 'serie_id']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('matiere_niveau');
        Schema::dropIfExists('matieres');
        Schema::dropIfExists('salles');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('series');
        Schema::dropIfExists('niveaux');

        Schema::enableForeignKeyConstraints();
    }
};
