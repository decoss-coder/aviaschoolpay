<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            if (! Schema::hasColumn('matieres', 'heures_hebdo_premier_cycle')) {
                $table->decimal('heures_hebdo_premier_cycle', 5, 2)
                    ->nullable()
                    ->after('heures_hebdo_defaut');
            }

            if (! Schema::hasColumn('matieres', 'heures_hebdo_second_cycle')) {
                $table->decimal('heures_hebdo_second_cycle', 5, 2)
                    ->nullable()
                    ->after('heures_hebdo_premier_cycle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            if (Schema::hasColumn('matieres', 'heures_hebdo_second_cycle')) {
                $table->dropColumn('heures_hebdo_second_cycle');
            }

            if (Schema::hasColumn('matieres', 'heures_hebdo_premier_cycle')) {
                $table->dropColumn('heures_hebdo_premier_cycle');
            }
        });
    }
};
