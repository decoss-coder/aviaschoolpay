<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Liste de fournitures (1 par classe/année) ───
        Schema::create('listes_fournitures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('titre', 200)->default('Liste de fournitures');
            $table->text('notes')->nullable();
            $table->boolean('publie')->default(false);
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();

            $table->unique(['classe_id', 'annee_scolaire_id'], 'lf_classe_annee_unique');
        });

        // ─── Items (fournitures) ───
        Schema::create('fournitures_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liste_id')->constrained('listes_fournitures')->cascadeOnDelete();
            $table->string('libelle', 200);
            $table->string('categorie', 60)->nullable()->comment('Cahiers, Stylos, Livres, etc.');
            $table->unsignedSmallInteger('quantite')->default(1);
            $table->string('unite', 20)->nullable()->comment('pièce, paquet, boîte');
            $table->string('marque_suggeree', 100)->nullable();
            $table->boolean('obligatoire')->default(true);
            $table->text('observations')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['liste_id', 'categorie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournitures_items');
        Schema::dropIfExists('listes_fournitures');
    }
};
