<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $t) {
            // true par défaut : la direction peut enregistrer espèces / chèque / virement.
            // Si false, seuls les paiements en ligne (Wave) sont possibles.
            $t->boolean('paiements_manuels_actifs')->default(true)->after('wave_actif');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $t) {
            $t->dropColumn('paiements_manuels_actifs');
        });
    }
};
