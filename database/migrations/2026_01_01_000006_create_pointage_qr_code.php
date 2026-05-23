<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 06 — POINTAGE QR CODE IMPRIMÉ + GPS (Module 4)
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── QR Codes (imprimés et fixés dans les salles) ──
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('salle_id')->constrained('salles')->cascadeOnDelete();
            $table->string('code_unique', 64)->unique()->comment('Hash SHA-256 identifiant le QR');
            $table->string('contenu_qr')->comment('Données encodées dans le QR imprimé');
            $table->boolean('actif')->default(true);
            $table->date('date_impression')->nullable();
            $table->date('date_desactivation')->nullable();
            $table->string('motif_desactivation')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'actif']);
        });

        // ── Pointages enseignants ──
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->nullOnDelete();
            $table->date('date');
            $table->enum('type_scan', ['arrivee', 'depart'])->default('arrivee');
            $table->time('heure_scan');
            $table->enum('methode', ['qr_gps', 'pin_gps', 'nfc_gps', 'manuel'])->default('qr_gps');
            $table->enum('statut', ['present', 'retard', 'absent', 'hors_zone', 'fraude_detectee'])->default('present');

            // Données GPS
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->decimal('gps_precision_metres', 6, 1)->nullable()->comment('Précision GPS en mètres');
            $table->decimal('distance_ecole_metres', 8, 1)->nullable()->comment('Distance calculée à l école');
            $table->boolean('gps_valide')->default(false)->comment('GPS dans le périmètre autorisé');
            $table->boolean('spoofing_detecte')->default(false)->comment('Tentative de faux GPS détectée');

            // Jeton de sécurité
            $table->string('token_validation', 64)->nullable()->comment('Jeton à usage unique serveur');
            $table->timestamp('token_expire_at')->nullable();
            $table->boolean('token_valide')->default(false);

            // Selfie optionnel
            $table->string('selfie_path')->nullable();

            // Vérification emploi du temps
            $table->boolean('conforme_emploi_temps')->default(true)->comment('Le scan correspond à l emploi du temps');

            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['enseignant_id', 'date']);
            $table->index(['etablissement_id', 'date', 'statut']);
            $table->index(['date', 'type_scan']);
        });

        // ── Code PIN journalier (Option B) ──
        Schema::create('codes_pin_journaliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->date('date');
            $table->string('code_pin', 6);
            $table->time('heure_generation');
            $table->time('heure_expiration');
            $table->boolean('envoye_sms')->default(false);
            $table->timestamps();

            $table->unique(['etablissement_id', 'date']);
        });

        // ── Alertes de pointage ──
        Schema::create('alertes_pointage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('pointage_id')->nullable()->constrained('pointages')->nullOnDelete();
            $table->date('date');
            $table->enum('type_alerte', [
                'absence', 'retard', 'hors_zone', 'spoofing_gps',
                'scan_trop_court', 'salle_incorrecte', 'absence_repetee'
            ]);
            $table->enum('gravite', ['info', 'warning', 'critique'])->default('warning');
            $table->text('message');
            $table->boolean('lue')->default(false);
            $table->boolean('traitee')->default(false);
            $table->foreignId('traitee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire_traitement')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'date', 'lue']);
        });

        // ── Historique mensuel ponctualité (pour rapports IA) ──
        Schema::create('stats_ponctualite_mensuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->string('mois', 7)->comment('Ex: 2026-04');
            $table->unsignedSmallInteger('jours_travailles')->default(0);
            $table->unsignedSmallInteger('presents')->default(0);
            $table->unsignedSmallInteger('retards')->default(0);
            $table->unsignedSmallInteger('absents')->default(0);
            $table->unsignedSmallInteger('absents_justifies')->default(0);
            $table->decimal('score_ponctualite', 5, 2)->default(100);
            $table->time('heure_arrivee_moyenne')->nullable();
            $table->unsignedSmallInteger('alertes_fraude')->default(0);
            $table->timestamps();

            $table->unique(['enseignant_id', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('stats_ponctualite_mensuelles');
        Schema::dropIfExists('alertes_pointage');
        Schema::dropIfExists('codes_pin_journaliers');
        Schema::dropIfExists('pointages');
        Schema::dropIfExists('qr_codes');

        Schema::enableForeignKeyConstraints();
    }
};
