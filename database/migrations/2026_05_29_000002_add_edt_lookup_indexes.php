<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('emploi_du_temps')) {
            return;
        }

        DB::statement('ALTER TABLE emploi_du_temps ADD INDEX edt_classe_slot_idx (etablissement_id, annee_scolaire_id, jour, creneau_id, classe_id)');
        DB::statement('ALTER TABLE emploi_du_temps ADD INDEX edt_enseignant_slot_idx (etablissement_id, annee_scolaire_id, jour, creneau_id, enseignant_id)');
        DB::statement('ALTER TABLE emploi_du_temps ADD INDEX edt_salle_slot_idx (etablissement_id, annee_scolaire_id, jour, creneau_id, salle_id)');
    }

    public function down(): void
    {
    }
};
