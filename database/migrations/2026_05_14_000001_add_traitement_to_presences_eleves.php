<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presences_eleves', function (Blueprint $table) {
            if (!Schema::hasColumn('presences_eleves', 'traite_par')) {
                $table->foreignId('traite_par')->nullable()->after('saisie_par')
                      ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('presences_eleves', 'traite_at')) {
                $table->timestamp('traite_at')->nullable()->after('traite_par');
            }
        });
    }

    public function down(): void
    {
        Schema::table('presences_eleves', function (Blueprint $table) {
            if (Schema::hasColumn('presences_eleves', 'traite_par')) {
                try { $table->dropForeign(['traite_par']); } catch (\Throwable $e) {}
                $table->dropColumn('traite_par');
            }
            if (Schema::hasColumn('presences_eleves', 'traite_at')) {
                $table->dropColumn('traite_at');
            }
        });
    }
};
