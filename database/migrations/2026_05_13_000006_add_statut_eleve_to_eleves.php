<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            if (!Schema::hasColumn('eleves', 'statut_eleve')) {
                $table->string('statut_eleve', 10)->nullable()->default(null)->after('actif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            if (Schema::hasColumn('eleves', 'statut_eleve')) {
                $table->dropColumn('statut_eleve');
            }
        });
    }
};
