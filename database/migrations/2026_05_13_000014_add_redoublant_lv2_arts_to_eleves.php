<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les champs visibles sur la "LISTE DE CLASSE" officielle MENA :
 *  - redoublant : R (booléen)
 *  - lv2        : Langue Vivante 2 (Espagnol, Allemand, Arabe...)
 *  - option_arts: option arts (Musique, Arts plastiques...)
 *  - educateur  : nom de l'éducateur (vie scolaire)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            if (!Schema::hasColumn('eleves', 'redoublant')) {
                $table->boolean('redoublant')->default(false)->after('date_naissance');
            }
            if (!Schema::hasColumn('eleves', 'lv2')) {
                $table->string('lv2', 30)->nullable()->after('redoublant');
            }
            if (!Schema::hasColumn('eleves', 'option_arts')) {
                $table->string('option_arts', 30)->nullable()->after('lv2');
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'educateur')) {
                $table->string('educateur', 100)->nullable()->after('professeur_principal_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            foreach (['redoublant', 'lv2', 'option_arts'] as $c) {
                if (Schema::hasColumn('eleves', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'educateur')) $table->dropColumn('educateur');
        });
    }
};
