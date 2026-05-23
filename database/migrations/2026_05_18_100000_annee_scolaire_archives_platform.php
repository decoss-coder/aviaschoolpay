<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            if (! Schema::hasColumn('annees_scolaires', 'archivee')) {
                $table->boolean('archivee')->default(false)->after('cloturee');
            }
            if (! Schema::hasColumn('annees_scolaires', 'archive_path')) {
                $table->string('archive_path', 500)->nullable()->after('archivee');
            }
            if (! Schema::hasColumn('annees_scolaires', 'archive_checksum')) {
                $table->string('archive_checksum', 64)->nullable()->after('archive_path');
            }
            if (! Schema::hasColumn('annees_scolaires', 'restoration_key_hash')) {
                $table->string('restoration_key_hash', 255)->nullable()->after('archive_checksum');
            }
            if (! Schema::hasColumn('annees_scolaires', 'restoration_key_vault')) {
                $table->text('restoration_key_vault')->nullable()->after('restoration_key_hash')
                    ->comment('Clé chiffrée (APP_KEY) — livrable par super admin après paiement');
            }
            if (! Schema::hasColumn('annees_scolaires', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('restoration_key_vault');
            }
            if (! Schema::hasColumn('annees_scolaires', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->after('archived_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('annees_scolaires', 'archive_meta')) {
                $table->json('archive_meta')->nullable()->after('archived_by');
            }
        });

        if (! Schema::hasTable('annee_scolaire_restauration_demandes')) {
        Schema::create('annee_scolaire_restauration_demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('demandeur_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('montant_fcfa')->default(500);
            $table->enum('statut', ['en_attente_paiement', 'paye', 'cle_livree', 'restauree', 'annulee'])->default('en_attente_paiement');
            $table->string('reference', 40)->unique();
            $table->string('wave_checkout_url', 512)->nullable();
            $table->timestamp('paye_at')->nullable();
            $table->timestamp('cle_livree_at')->nullable();
            $table->timestamp('restauree_at')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'statut'], 'as_restauration_etab_statut_idx');
        });
        }

        if (! Schema::hasTable('platform_settings')) {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('cle', 80)->primary();
            $table->text('valeur')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
        }

        if (\Illuminate\Support\Facades\DB::table('platform_settings')->where('cle', 'wave_lien_restauration_500')->doesntExist()) {
            \Illuminate\Support\Facades\DB::table('platform_settings')->insert([
                'cle' => 'wave_lien_restauration_500',
                'valeur' => 'https://pay.wave.com/m/M_ci_1Onagr26EsBs/c/ci/',
                'description' => 'Lien Wave restauration archive (500 FCFA)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \Illuminate\Support\Facades\DB::table('platform_settings')->insert([
                'cle' => 'wave_libelle_restauration',
                'valeur' => 'Avia Technologie',
                'description' => 'Libellé paiement restauration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('annee_scolaire_restauration_demandes');

        Schema::table('annees_scolaires', function (Blueprint $table) {
            foreach ([
                'archive_meta', 'archived_by', 'archived_at', 'restoration_key_vault',
                'restoration_key_hash', 'archive_checksum', 'archive_path', 'archivee',
            ] as $col) {
                if (Schema::hasColumn('annees_scolaires', $col)) {
                    if ($col === 'archived_by') {
                        $table->dropForeign(['archived_by']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
