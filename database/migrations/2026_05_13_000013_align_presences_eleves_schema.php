<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligne presences_eleves avec le modèle PresenceEleve :
 *  - Ajoute matiere_id, enseignant_id (pour le cahier d'appel par séance)
 *  - Ajoute observation (free text)
 *  - Renomme/ajoute justification (texte) — distinct du flag justifie booleen
 *  - Élargit l'enum statut pour aligner sur 'dispense' (au lieu de excuse)
 *
 * Conserve la compat ascendante : on garde 'excuse' dans l'enum + on ajoute
 * 'dispense' pour matcher le code récent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presences_eleves', function (Blueprint $table) {
            if (!Schema::hasColumn('presences_eleves', 'matiere_id')) {
                $table->foreignId('matiere_id')->nullable()->after('classe_id')
                      ->constrained('matieres')->nullOnDelete();
            }
            if (!Schema::hasColumn('presences_eleves', 'enseignant_id')) {
                $table->foreignId('enseignant_id')->nullable()->after('matiere_id')
                      ->constrained('enseignants')->nullOnDelete();
            }
            if (!Schema::hasColumn('presences_eleves', 'observation')) {
                $table->text('observation')->nullable()->after('motif');
            }
            if (!Schema::hasColumn('presences_eleves', 'justification')) {
                $table->text('justification')->nullable()->after('motif');
            }
            if (!Schema::hasColumn('presences_eleves', 'creneau_id')) {
                $table->foreignId('creneau_id')->nullable()->after('periode')
                      ->constrained('creneaux')->nullOnDelete();
            }
        });

        // Élargir l'enum statut (MySQL uniquement ; SQLite tests : colonne déjà compatible).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            \DB::statement("ALTER TABLE `presences_eleves` MODIFY COLUMN `statut` ENUM('present','absent','retard','excuse','dispense') NOT NULL DEFAULT 'present'");
        }
    }

    public function down(): void
    {
        Schema::table('presences_eleves', function (Blueprint $table) {
            foreach (['creneau_id', 'justification', 'observation', 'enseignant_id', 'matiere_id'] as $col) {
                if (Schema::hasColumn('presences_eleves', $col)) {
                    try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            \DB::statement("ALTER TABLE `presences_eleves` MODIFY COLUMN `statut` ENUM('present','absent','retard','excuse') NOT NULL DEFAULT 'present'");
        }
    }
};
