<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->string('titre', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['devoir', 'exercice', 'tp', 'projet', 'lecture', 'interrogation'])->default('devoir');
            $table->date('date_publication');
            $table->date('date_limite')->nullable();
            $table->string('fichier_path')->nullable();
            $table->boolean('publie')->default(false);
            $table->timestamps();

            $table->index(['classe_id', 'matiere_id']);
            $table->index(['enseignant_id', 'annee_scolaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoirs');
    }
};
