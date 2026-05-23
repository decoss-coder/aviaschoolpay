<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Étendre l'enum statut pour inclure les nouveaux états du SigfneSyncService
        DB::statement("ALTER TABLE remontees_sigfne MODIFY COLUMN statut ENUM(
            'preparation',
            'en_cours',
            'pret_envoi',
            'envoye',
            'termine',
            'erreur',
            'erreur_api',
            'valide_drena'
        ) NOT NULL DEFAULT 'preparation'");
    }

    public function down(): void
    {
        // Repasser à l'ancien enum (les lignes avec nouveaux statuts seront migrés vers 'termine'/'erreur')
        DB::statement("UPDATE remontees_sigfne SET statut = 'termine' WHERE statut IN ('pret_envoi', 'envoye')");
        DB::statement("UPDATE remontees_sigfne SET statut = 'erreur' WHERE statut = 'erreur_api'");
        DB::statement("ALTER TABLE remontees_sigfne MODIFY COLUMN statut ENUM(
            'preparation', 'en_cours', 'termine', 'erreur', 'valide_drena'
        ) NOT NULL DEFAULT 'preparation'");
    }
};
