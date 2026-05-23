<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bascule le cahier d'appel en mode "par séance" :
 *  - Supprime l'ancienne unique (eleve_id, date, periode) qui interdisait
 *    plusieurs présences par jour pour un même élève.
 *  - Ajoute une unique compatible créneau : (eleve_id, date, creneau_id)
 *    plus un index utile (classe_id, date, creneau_id).
 *
 * Les entrées globales journalières (creneau_id = NULL) restent permises
 * via une unique séparée (eleve_id, date, periode) avec une condition mais
 * MySQL ne supporte pas les unique partielles → on garde simplement
 * l'unique sur (eleve_id, date, creneau_id) qui couvre les deux usages
 * grâce au NULL semantics MySQL (plusieurs NULL autorisés).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Étape 1 : créer un index sur eleve_id seul pour que la FK
        // ait son propre support quand on droppera l'ancienne unique.
        $existingIndexes = collect(DB::select("SHOW INDEX FROM presences_eleves"))
            ->pluck('Key_name')->unique()->all();

        if (!in_array('presences_eleves_eleve_id_index', $existingIndexes)) {
            Schema::table('presences_eleves', function (Blueprint $table) {
                $table->index('eleve_id');
            });
        }

        // Étape 2 : drop l'ancienne unique (eleve_id, date, periode)
        $idx = collect(DB::select("SHOW INDEX FROM presences_eleves"))
            ->pluck('Key_name')->unique()->all();

        Schema::table('presences_eleves', function (Blueprint $table) use ($idx) {
            foreach ($idx as $key) {
                if (str_starts_with($key, 'presences_eleves_eleve_id_date_periode')) {
                    $table->dropUnique($key);
                }
            }
        });

        // Étape 3 : nouvelle unique (eleve_id, date, creneau_id)
        Schema::table('presences_eleves', function (Blueprint $table) {
            $table->unique(['eleve_id', 'date', 'creneau_id'], 'presences_eleve_date_creneau_unique');
            $table->index(['classe_id', 'date', 'creneau_id'], 'presences_classe_date_creneau_idx');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('presences_eleves', function (Blueprint $table) {
            try { $table->dropUnique('presences_eleve_date_creneau_unique'); } catch (\Throwable $e) {}
            try { $table->dropIndex('presences_classe_date_creneau_idx'); } catch (\Throwable $e) {}
            $table->unique(['eleve_id', 'date', 'periode']);
        });
    }
};
