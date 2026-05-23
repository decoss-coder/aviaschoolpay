<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            if (! Schema::hasColumn('paiements', 'poste_cible')) {
                $table->string('poste_cible', 20)->default('auto')->after('mode')
                    ->comment('inscription, scolarite, auto');
            }
            if (! Schema::hasColumn('paiements', 'canal_paiement')) {
                $table->string('canal_paiement', 20)->nullable()->after('poste_cible')
                    ->comment('manuel, wave');
            }
            if (! Schema::hasColumn('paiements', 'montant_inscription')) {
                $table->unsignedInteger('montant_inscription')->default(0)->after('montant');
            }
            if (! Schema::hasColumn('paiements', 'montant_scolarite')) {
                $table->unsignedInteger('montant_scolarite')->default(0)->after('montant_inscription');
            }
            if (! Schema::hasColumn('paiements', 'motif_annulation')) {
                $table->string('motif_annulation', 500)->nullable()->after('observations');
            }
            if (! Schema::hasColumn('paiements', 'date_validation')) {
                $table->timestamp('date_validation')->nullable()->after('date_paiement');
            }
            if (! Schema::hasColumn('paiements', 'reference_transaction')) {
                $table->string('reference_transaction', 120)->nullable()->after('reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $cols = [
                'poste_cible', 'canal_paiement', 'montant_inscription', 'montant_scolarite',
                'motif_annulation', 'date_validation', 'reference_transaction',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('paiements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
