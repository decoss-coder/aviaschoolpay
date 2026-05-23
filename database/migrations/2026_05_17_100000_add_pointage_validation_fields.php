<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            $table->foreignId('emploi_du_temps_id')
                ->nullable()
                ->after('salle_id')
                ->constrained('emploi_du_temps')
                ->nullOnDelete();

            $table->string('validation_finale', 32)
                ->default('provisoire')
                ->after('conforme_emploi_temps');

            $table->string('cahier_texte_status', 32)
                ->default('en_attente')
                ->after('cahier_texte_validated');

            $table->timestamp('cahier_texte_deadline_at')->nullable()->after('cahier_texte_validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('emploi_du_temps_id');
            $table->dropColumn(['validation_finale', 'cahier_texte_status', 'cahier_texte_deadline_at']);
        });
    }
};
