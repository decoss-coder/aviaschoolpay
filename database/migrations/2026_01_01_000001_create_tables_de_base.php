<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 01 — TABLES DE BASE (Fondation du système)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Groupes scolaires (Module 10 - Multi-établissements) ──
        Schema::create('groupes_scolaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle', 20)->nullable();
            $table->text('adresse')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // ── Établissements scolaires ──
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_scolaire_id')->nullable()->constrained('groupes_scolaires')->nullOnDelete();
            $table->string('nom');
            $table->string('code_desps', 20)->unique()->comment('Code établissement DESPS ex: 639000');
            $table->string('sigle', 20)->nullable();
            $table->enum('type', ['prescolaire', 'primaire', 'secondaire', 'lycee', 'mixte'])->default('secondaire');
            $table->enum('statut_juridique', ['public', 'prive_laic', 'prive_confessionnel', 'communautaire'])->default('prive_laic');
            $table->text('adresse');
            $table->string('ville', 100)->default('Abidjan');
            $table->string('commune', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('drena', 100)->nullable()->comment('Direction Régionale');
            $table->string('ddena', 100)->nullable()->comment('Direction Départementale');
            $table->string('telephone', 20);
            $table->string('email')->nullable();
            $table->string('site_web')->nullable();
            $table->string('logo_path')->nullable();
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('gps_rayon_metres')->default(100)->comment('Rayon de géolocalisation pour le pointage');
            $table->string('directeur_nom')->nullable();
            $table->string('directeur_telephone', 20)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('code_desps');
            $table->index('type');
        });

        // ── Années scolaires ──
        Schema::create('annees_scolaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('libelle', 20)->comment('Ex: 2025-2026');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('en_cours')->default(false);
            $table->boolean('cloturee')->default(false);
            $table->timestamps();

            $table->unique(['etablissement_id', 'libelle']);
        });

        // ── Trimestres ──
        Schema::create('trimestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero')->comment('1, 2 ou 3');
            $table->string('libelle', 50)->comment('Ex: Trimestre 1');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->date('date_cloture_notes')->nullable()->comment('Date limite saisie des notes');
            $table->date('date_remontee_desps')->nullable()->comment('Date limite remontée SIGFNE');
            $table->boolean('en_cours')->default(false);
            $table->boolean('notes_cloturees')->default(false);
            $table->boolean('moyennes_remontees')->default(false);
            $table->timestamps();

            $table->unique(['annee_scolaire_id', 'numero']);
        });

        // ── Paramètres système ──
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('cle', 100);
            $table->text('valeur')->nullable();
            $table->string('type', 20)->default('string')->comment('string, integer, boolean, json');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('parametres');
        Schema::dropIfExists('trimestres');
        Schema::dropIfExists('annees_scolaires');
        Schema::dropIfExists('etablissements');
        Schema::dropIfExists('groupes_scolaires');

        Schema::enableForeignKeyConstraints();
    }
};
