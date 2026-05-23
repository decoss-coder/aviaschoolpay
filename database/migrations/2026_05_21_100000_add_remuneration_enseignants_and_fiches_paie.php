<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Paramètres rémunération sur enseignants ───
        Schema::table('enseignants', function (Blueprint $table) {
            $table->enum('type_remuneration', ['fixe', 'horaire', 'mixte'])
                ->default('fixe')
                ->after('salaire_base')
                ->comment('fixe = salaire de base seul, horaire = uniquement taux × heures, mixte = base + taux × heures');
            $table->decimal('taux_horaire', 8, 0)->default(0)->after('type_remuneration')
                ->comment('Taux horaire en FCFA');
            $table->decimal('heures_contractuelles_mois', 5, 1)->nullable()->after('taux_horaire')
                ->comment('Heures contractuelles mensuelles (pour calcul prorata)');
        });

        // ─── Fiches de paie ───
        Schema::create('fiches_paie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->string('reference', 30)->unique()->comment('Ex: FP-2026-05-0001');
            $table->string('mois', 7)->comment('YYYY-MM');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->enum('type_remuneration', ['fixe', 'horaire', 'mixte']);

            // Calculs
            $table->decimal('salaire_base', 12, 0)->default(0);
            $table->decimal('taux_horaire', 8, 0)->default(0);
            $table->decimal('heures_travaillees', 6, 2)->default(0)->comment('Heures effectives depuis pointage');
            $table->decimal('heures_contractuelles', 6, 2)->nullable();
            $table->decimal('montant_horaire', 12, 0)->default(0)->comment('heures × taux');

            $table->decimal('primes', 12, 0)->default(0);
            $table->decimal('indemnites', 12, 0)->default(0);
            $table->decimal('avances', 12, 0)->default(0);
            $table->decimal('retenues', 12, 0)->default(0)->comment('Absences, CNPS, IUTS, etc.');
            $table->json('details_primes')->nullable();
            $table->json('details_retenues')->nullable();

            $table->decimal('salaire_brut', 12, 0)->default(0);
            $table->decimal('cotisations_sociales', 12, 0)->default(0)->comment('Part salariale CNPS');
            $table->decimal('impots', 12, 0)->default(0)->comment('IUTS');
            $table->decimal('salaire_net', 12, 0)->default(0);

            // Pointage stats
            $table->unsignedSmallInteger('nb_jours_travailles')->default(0);
            $table->unsignedSmallInteger('nb_jours_absents')->default(0);
            $table->unsignedSmallInteger('nb_retards')->default(0);

            // Workflow
            $table->enum('statut', ['brouillon', 'validee', 'payee', 'annulee'])->default('brouillon');
            $table->foreignId('generee_par')->constrained('users');
            $table->foreignId('validee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->date('date_paiement_effectif')->nullable();
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement', 'mobile_money'])->nullable();

            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enseignant_id', 'mois'], 'fp_enseignant_mois_unique');
            $table->index(['etablissement_id', 'mois'], 'fp_etab_mois_idx');
            $table->index(['etablissement_id', 'statut'], 'fp_etab_stat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiches_paie');

        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropColumn(['type_remuneration', 'taux_horaire', 'heures_contractuelles_mois']);
        });
    }
};
