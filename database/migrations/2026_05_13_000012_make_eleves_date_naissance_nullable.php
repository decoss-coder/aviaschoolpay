<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les imports depuis listes papier (Excel/PDF/photo) ne contiennent souvent
 * que nom + sexe + matricule. La date de naissance peut être renseignée plus
 * tard via l'édition individuelle de la fiche élève.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            $table->date('date_naissance')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            $table->date('date_naissance')->nullable(false)->change();
        });
    }
};
