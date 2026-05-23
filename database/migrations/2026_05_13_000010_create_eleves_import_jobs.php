<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleves_import_jobs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classe_cible_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->nullOnDelete();

            $table->enum('source', ['excel', 'csv', 'pdf', 'photo_ocr', 'saisie_rapide']);
            $table->string('fichier_original')->nullable();
            $table->string('fichier_path')->nullable();
            $table->unsignedBigInteger('fichier_taille')->nullable();

            $table->enum('statut', ['upload', 'parsing', 'preview', 'importing', 'completed', 'failed', 'cancelled'])
                  ->default('upload');

            $table->json('donnees_brutes')->nullable();
            $table->json('donnees_normalisees')->nullable();
            $table->json('erreurs')->nullable();
            $table->json('metadonnees')->nullable();

            $table->unsignedInteger('total_lignes')->default(0);
            $table->unsignedInteger('lignes_valides')->default(0);
            $table->unsignedInteger('lignes_erreur')->default(0);
            $table->unsignedInteger('lignes_importees')->default(0);

            $table->unsignedTinyInteger('progression')->default(0);
            $table->text('message_progression')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'statut']);
            $table->index(['etablissement_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleves_import_jobs');
    }
};
