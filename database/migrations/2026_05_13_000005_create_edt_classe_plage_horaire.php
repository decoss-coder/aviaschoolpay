<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edt_classe_plage_horaire')) {
            Schema::create('edt_classe_plage_horaire', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();

                // null = s'applique à tous les jours de la semaine
                $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'])->nullable();

                $table->enum('plage', ['matin', 'apres_midi']);

                // true = la classe peut avoir cours sur cette plage
                // false = la classe NE PEUT PAS avoir cours sur cette plage
                $table->boolean('autorise')->default(false);

                $table->text('notes')->nullable();
                $table->timestamps();

                // Une seule règle par (classe, jour, plage, annee)
                $table->unique(
                    ['classe_id', 'jour', 'plage', 'annee_scolaire_id'],
                    'idx_plage_classe_jour_unique'
                );

                $table->index(['etablissement_id', 'annee_scolaire_id'], 'idx_plage_etab_annee');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('edt_classe_plage_horaire');
    }
};
