<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('emploi_du_temps') || ! Schema::hasColumn('emploi_du_temps', 'salle_id')) {
            return;
        }

        $foreignKeys = DB::select("\n            SELECT CONSTRAINT_NAME\n            FROM information_schema.KEY_COLUMN_USAGE\n            WHERE TABLE_SCHEMA = DATABASE()\n              AND TABLE_NAME = 'emploi_du_temps'\n              AND COLUMN_NAME = 'salle_id'\n              AND REFERENCED_TABLE_NAME IS NOT NULL\n        ");

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE emploi_du_temps DROP FOREIGN KEY `' . $foreignKey->CONSTRAINT_NAME . '`');
        }

        DB::statement('ALTER TABLE emploi_du_temps MODIFY salle_id BIGINT UNSIGNED NULL');

        DB::statement('ALTER TABLE emploi_du_temps ADD CONSTRAINT emploi_du_temps_salle_id_foreign FOREIGN KEY (salle_id) REFERENCES salles(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('emploi_du_temps') || ! Schema::hasColumn('emploi_du_temps', 'salle_id')) {
            return;
        }

        $foreignKeys = DB::select("\n            SELECT CONSTRAINT_NAME\n            FROM information_schema.KEY_COLUMN_USAGE\n            WHERE TABLE_SCHEMA = DATABASE()\n              AND TABLE_NAME = 'emploi_du_temps'\n              AND COLUMN_NAME = 'salle_id'\n              AND REFERENCED_TABLE_NAME IS NOT NULL\n        ");

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE emploi_du_temps DROP FOREIGN KEY `' . $foreignKey->CONSTRAINT_NAME . '`');
        }

        DB::statement('ALTER TABLE emploi_du_temps MODIFY salle_id BIGINT UNSIGNED NOT NULL');

        DB::statement('ALTER TABLE emploi_du_temps ADD CONSTRAINT emploi_du_temps_salle_id_foreign FOREIGN KEY (salle_id) REFERENCES salles(id) ON DELETE CASCADE');
    }
};
