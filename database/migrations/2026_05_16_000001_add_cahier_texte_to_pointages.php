<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            // Photo du cahier de texte (preuve du cours effectué)
            $table->string('cahier_texte_path', 255)->nullable()->after('selfie_path');
            // Données OCR extraites du cahier de texte (date, créneau, contenu)
            $table->json('cahier_texte_data')->nullable()->after('cahier_texte_path');
            // Résultat de la validation IA
            $table->boolean('cahier_texte_validated')->default(false)->after('cahier_texte_data');
            $table->timestamp('cahier_texte_validated_at')->nullable()->after('cahier_texte_validated');
            // Score de confiance OCR (0-100)
            $table->unsignedTinyInteger('cahier_texte_confidence')->nullable()->after('cahier_texte_validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            $table->dropColumn([
                'cahier_texte_path',
                'cahier_texte_data',
                'cahier_texte_validated',
                'cahier_texte_validated_at',
                'cahier_texte_confidence',
            ]);
        });
    }
};
