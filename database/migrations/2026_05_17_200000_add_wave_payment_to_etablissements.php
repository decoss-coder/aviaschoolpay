<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            if (! Schema::hasColumn('etablissements', 'wave_actif')) {
                $table->boolean('wave_actif')->default(false)->after('actif');
            }
            if (! Schema::hasColumn('etablissements', 'wave_libelle')) {
                $table->string('wave_libelle', 120)->nullable()->after('wave_actif')
                    ->comment('Libellé affiché ex: Nom de l\'école sur Wave');
            }
            if (! Schema::hasColumn('etablissements', 'wave_lien_base')) {
                $table->text('wave_lien_base')->nullable()->after('wave_libelle')
                    ->comment('URL Wave sans montant, ex: https://pay.wave.com/m/.../c/ci/');
            }
            if (! Schema::hasColumn('etablissements', 'wave_configured_at')) {
                $table->timestamp('wave_configured_at')->nullable()->after('wave_lien_base');
            }
            if (! Schema::hasColumn('etablissements', 'wave_configured_by')) {
                $table->foreignId('wave_configured_by')->nullable()->after('wave_configured_at')
                    ->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('paiements', function (Blueprint $table) {
            if (! Schema::hasColumn('paiements', 'wave_checkout_url')) {
                $table->string('wave_checkout_url', 512)->nullable()->after('paydunya_invoice_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            if (Schema::hasColumn('paiements', 'wave_checkout_url')) {
                $table->dropColumn('wave_checkout_url');
            }
        });

        Schema::table('etablissements', function (Blueprint $table) {
            foreach (['wave_configured_by', 'wave_configured_at', 'wave_lien_base', 'wave_libelle', 'wave_actif'] as $col) {
                if (Schema::hasColumn('etablissements', $col)) {
                    if ($col === 'wave_configured_by') {
                        $table->dropForeign(['wave_configured_by']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
