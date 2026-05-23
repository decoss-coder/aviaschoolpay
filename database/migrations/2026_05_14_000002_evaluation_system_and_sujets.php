<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. etablissements.systeme_evaluation : trimestre / semestre / quadrimestre
 * 2. evaluations.fichier_sujet_path : PDF du sujet à distribuer aux élèves
 * 3. moyennes_matieres : saisie_directe + enseignant_id + saisie_par
 * 4. devoirs.fichier_corrige_path : corrigé optionnel
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            if (!Schema::hasColumn('etablissements', 'systeme_evaluation')) {
                $table->enum('systeme_evaluation', ['trimestre', 'semestre', 'quadrimestre'])
                      ->default('trimestre')
                      ->after('actif');
            }
        });

        Schema::table('evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluations', 'fichier_sujet_path')) {
                $table->string('fichier_sujet_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('evaluations', 'fichier_corrige_path')) {
                $table->string('fichier_corrige_path')->nullable()->after('fichier_sujet_path');
            }
        });

        Schema::table('moyennes_matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('moyennes_matieres', 'saisie_directe')) {
                $table->boolean('saisie_directe')->default(false)->after('appreciation');
            }
            if (!Schema::hasColumn('moyennes_matieres', 'enseignant_id')) {
                $table->foreignId('enseignant_id')->nullable()->after('matiere_id')
                      ->constrained('enseignants')->nullOnDelete();
            }
            if (!Schema::hasColumn('moyennes_matieres', 'saisie_par')) {
                $table->foreignId('saisie_par')->nullable()->after('enseignant_id')
                      ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('moyennes_matieres', 'date_saisie')) {
                $table->timestamp('date_saisie')->nullable()->after('saisie_par');
            }
        });

        Schema::table('devoirs', function (Blueprint $table) {
            if (!Schema::hasColumn('devoirs', 'fichier_corrige_path')) {
                $table->string('fichier_corrige_path')->nullable()->after('fichier_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            if (Schema::hasColumn('etablissements', 'systeme_evaluation')) $table->dropColumn('systeme_evaluation');
        });
        Schema::table('evaluations', function (Blueprint $table) {
            foreach (['fichier_sujet_path', 'fichier_corrige_path'] as $c) {
                if (Schema::hasColumn('evaluations', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('moyennes_matieres', function (Blueprint $table) {
            foreach (['saisie_directe', 'date_saisie'] as $c) {
                if (Schema::hasColumn('moyennes_matieres', $c)) $table->dropColumn($c);
            }
            foreach (['enseignant_id', 'saisie_par'] as $c) {
                if (Schema::hasColumn('moyennes_matieres', $c)) {
                    try { $table->dropForeign([$c]); } catch (\Throwable $e) {}
                    $table->dropColumn($c);
                }
            }
        });
        Schema::table('devoirs', function (Blueprint $table) {
            if (Schema::hasColumn('devoirs', 'fichier_corrige_path')) $table->dropColumn('fichier_corrige_path');
        });
    }
};
