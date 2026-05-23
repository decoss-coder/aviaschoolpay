<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Événements scolaires (calendrier annuel) ───
        Schema::create('evenements_scolaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('titre', 200);
            $table->enum('type', [
                'rentree', 'vacances', 'examen', 'conseil_classe', 'reunion_parents',
                'fete', 'sortie', 'ferie', 'autre',
            ])->default('autre');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->text('description')->nullable();
            $table->string('lieu', 200)->nullable();
            $table->string('couleur', 7)->nullable();
            $table->boolean('toute_journee')->default(true);
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->boolean('publie')->default(false);
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();

            $table->index(['etablissement_id', 'annee_scolaire_id', 'date_debut'], 'es_etab_annee_date');
        });

        // ─── Conseils de classe ───
        Schema::create('conseils_classe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained('trimestres')->cascadeOnDelete();
            $table->date('date_conseil');
            $table->time('heure_debut');
            $table->time('heure_fin')->nullable();
            $table->string('lieu', 200);
            $table->text('ordre_du_jour');
            $table->text('participants')->nullable()->comment('Liste libre');
            $table->enum('statut', ['planifie', 'tenu', 'reporte', 'annule'])->default('planifie');
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();

            $table->index(['etablissement_id', 'date_conseil'], 'cc_etab_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conseils_classe');
        Schema::dropIfExists('evenements_scolaires');
    }
};
