<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('inscriptions', 'montant_inscription')) {
                $table->unsignedBigInteger('montant_inscription')->default(0)->after('statut');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('inscriptions', 'montant_inscription')) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->dropColumn('montant_inscription');
            });
        }
    }
};
