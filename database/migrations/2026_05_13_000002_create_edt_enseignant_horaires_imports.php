<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── Table de suivi des imports d'emplois du temps d'autres établissements ──
// Chaque upload image/PDF d'un prof crée un enregistrement ici.
// Après validation OCR, les créneaux sont sauvegardés dans edt_enseignant_horaires_externes.

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edt_enseignant_horaires_imports')) {
            Schema::create('edt_enseignant_horaires_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
                $table->enum('source_type', ['photo', 'image', 'scan', 'pdf'])->default('photo');
                $table->string('fichier_path')->nullable();
                $table->string('original_filename', 255)->nullable();
                $table->enum('statut', ['uploade', 'analyse', 'valide', 'erreur'])->default('uploade');
                $table->json('payload_ocr_json')->nullable()->comment('Résultat brut retourné par OpenAI');
                $table->string('etablissement_detecte', 200)->nullable()->comment('Nom de l\'école détecté par OCR');
                $table->string('professeur_detecte', 200)->nullable()->comment('Nom du prof détecté par OCR');
                $table->unsignedSmallInteger('confidence_score')->default(0)->comment('Score OCR 0-100');
                $table->text('notes_ocr')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();

                $table->index(['enseignant_id', 'statut'], 'idx_horimport_ens_statut');
            });
        }

        // Ajouter import_id sur edt_enseignant_horaires_externes si absent
        if (!Schema::hasColumn('edt_enseignant_horaires_externes', 'import_id')) {
            Schema::table('edt_enseignant_horaires_externes', function (Blueprint $table) {
                $table->foreignId('import_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('edt_enseignant_horaires_imports')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('edt_enseignant_horaires_externes', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropColumn('import_id');
        });

        Schema::dropIfExists('edt_enseignant_horaires_imports');

        Schema::enableForeignKeyConstraints();
    }
};
