<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (! Schema::hasColumn('classes', 'scolarite_annuelle')) {
                $table->unsignedBigInteger('scolarite_annuelle')->default(0)->after('effectif');
            }
            if (! Schema::hasColumn('classes', 'frais_inscription')) {
                $table->unsignedBigInteger('frais_inscription')->default(0)->after('scolarite_annuelle');
            }
            if (! Schema::hasColumn('classes', 'frais_reinscription')) {
                $table->unsignedBigInteger('frais_reinscription')->default(0)->after('frais_inscription');
            }
            if (! Schema::hasColumn('classes', 'description')) {
                $table->text('description')->nullable()->after('frais_reinscription');
            }
        });

        Schema::table('niveaux', function (Blueprint $table) {
            if (! Schema::hasColumn('niveaux', 'frais_inscription_defaut')) {
                $table->unsignedBigInteger('frais_inscription_defaut')->default(0)->after('frais_scolarite_defaut');
            }
            if (! Schema::hasColumn('niveaux', 'frais_reinscription_defaut')) {
                $table->unsignedBigInteger('frais_reinscription_defaut')->default(0)->after('frais_inscription_defaut');
            }
        });

        Schema::table('inscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('inscriptions', 'montant_inscription')) {
                $table->unsignedBigInteger('montant_inscription')->default(0)->after('montant_scolarite');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('inscriptions', 'montant_inscription')) {
                $table->dropColumn('montant_inscription');
            }
        });

        Schema::table('niveaux', function (Blueprint $table) {
            foreach (['frais_reinscription_defaut', 'frais_inscription_defaut'] as $col) {
                if (Schema::hasColumn('niveaux', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            foreach (['description', 'frais_reinscription', 'frais_inscription', 'scolarite_annuelle'] as $col) {
                if (Schema::hasColumn('classes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
