<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('matieres', 'heures_hebdo_defaut')) {
            Schema::table('matieres', function (Blueprint $table) {
                $table->decimal('heures_hebdo_defaut', 5, 2)
                    ->nullable()
                    ->after('coefficient_defaut');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('matieres', 'heures_hebdo_defaut')) {
            Schema::table('matieres', function (Blueprint $table) {
                $table->dropColumn('heures_hebdo_defaut');
            });
        }
    }
};
