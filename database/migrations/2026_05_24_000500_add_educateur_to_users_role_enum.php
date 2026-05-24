<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','fondateur','directeur','directeur_adjoint','gestionnaire','secretaire','comptable','censeur','educateur','enseignant','parent','eleve') NOT NULL DEFAULT 'directeur'");
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'educateur')
            ->update(['role' => 'censeur']);

        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','fondateur','directeur','directeur_adjoint','gestionnaire','secretaire','comptable','censeur','enseignant','parent','eleve') NOT NULL DEFAULT 'directeur'");
    }
};
