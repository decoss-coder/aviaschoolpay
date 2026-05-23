<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spécificités du système ivoirien :
 *  - trimestres.coefficient : pondération du trimestre dans la moyenne annuelle
 *    (par défaut : T1 = 1, T2 = 2, T3 = 2 — total = 5)
 *  - matieres.parent_matiere_id : hiérarchie pour les sous-disciplines
 *    (ex. FR → CF, OG, EO en premier cycle)
 *  - matieres.poids_dans_parent : coef de la sous-discipline DANS sa matière parente
 *    (ex. CF×3, OG×1, EO×1 dans FR)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trimestres', function (Blueprint $table) {
            if (!Schema::hasColumn('trimestres', 'coefficient')) {
                $table->decimal('coefficient', 4, 1)->default(1)->after('numero');
            }
        });

        // Initialiser les coefficients selon le standard ivoirien
        DB::table('trimestres')->where('numero', 1)->update(['coefficient' => 1]);
        DB::table('trimestres')->where('numero', 2)->update(['coefficient' => 2]);
        DB::table('trimestres')->where('numero', 3)->update(['coefficient' => 2]);
        // Semestres : équipondérés par défaut
        DB::table('trimestres')->where('numero', '>=', 4)->update(['coefficient' => 1]);

        Schema::table('matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('matieres', 'parent_matiere_id')) {
                $table->foreignId('parent_matiere_id')->nullable()->after('etablissement_id')
                      ->constrained('matieres')->nullOnDelete();
            }
            if (!Schema::hasColumn('matieres', 'poids_dans_parent')) {
                $table->decimal('poids_dans_parent', 4, 1)->default(1)->after('coefficient_defaut')
                      ->comment('Coef de la sous-discipline DANS sa matière parente');
            }
            if (!Schema::hasColumn('matieres', 'ordre')) {
                $table->unsignedSmallInteger('ordre')->default(0)->after('poids_dans_parent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trimestres', function (Blueprint $table) {
            if (Schema::hasColumn('trimestres', 'coefficient')) $table->dropColumn('coefficient');
        });
        Schema::table('matieres', function (Blueprint $table) {
            if (Schema::hasColumn('matieres', 'parent_matiere_id')) {
                try { $table->dropForeign(['parent_matiere_id']); } catch (\Throwable $e) {}
                $table->dropColumn('parent_matiere_id');
            }
            if (Schema::hasColumn('matieres', 'poids_dans_parent')) $table->dropColumn('poids_dans_parent');
            if (Schema::hasColumn('matieres', 'ordre')) $table->dropColumn('ordre');
        });
    }
};
