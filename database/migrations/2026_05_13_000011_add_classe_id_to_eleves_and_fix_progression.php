<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Ajoute eleves.classe_id (le modèle Eleve l'expose dans $fillable mais
 *   la colonne n'avait jamais été créée → SQLSTATE[42S22] lors d'inserts).
 * - Élargit eleves_import_jobs.message_progression en TEXT pour stocker les
 *   stack traces SQL complets.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eleves', 'classe_id')) {
            Schema::table('eleves', function (Blueprint $table) {
                $table->foreignId('classe_id')
                      ->nullable()
                      ->after('etablissement_id')
                      ->constrained('classes')
                      ->nullOnDelete();

                $table->index(['classe_id', 'actif'], 'eleves_classe_actif_idx');
            });
        }

        if (Schema::hasTable('eleves_import_jobs') && Schema::hasColumn('eleves_import_jobs', 'message_progression')) {
            Schema::table('eleves_import_jobs', function (Blueprint $table) {
                $table->text('message_progression')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('eleves', 'classe_id')) {
            Schema::table('eleves', function (Blueprint $table) {
                $table->dropForeign(['classe_id']);
                $table->dropIndex('eleves_classe_actif_idx');
                $table->dropColumn('classe_id');
            });
        }
    }
};
