<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moyennes_matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('moyennes_matieres', 'publie')) {
                $table->boolean('publie')->default(false)->after('saisie_directe');
            }
            if (!Schema::hasColumn('moyennes_matieres', 'date_publication')) {
                $table->timestamp('date_publication')->nullable()->after('publie');
            }
        });
    }

    public function down(): void
    {
        Schema::table('moyennes_matieres', function (Blueprint $table) {
            foreach (['publie', 'date_publication'] as $c) {
                if (Schema::hasColumn('moyennes_matieres', $c)) $table->dropColumn($c);
            }
        });
    }
};
