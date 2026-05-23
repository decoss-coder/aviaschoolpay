<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Solde SMS par établissement (1 ligne par école) ───
        Schema::create('sms_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->unique()->constrained('etablissements')->cascadeOnDelete();
            $table->unsignedInteger('solde')->default(0)->comment('Nombre de SMS disponibles');
            $table->unsignedInteger('cumul_recharge')->default(0)->comment('Cumul SMS rechargés');
            $table->unsignedInteger('cumul_envoye')->default(0)->comment('Cumul SMS envoyés');
            $table->decimal('cumul_paye_fcfa', 14, 0)->default(0)->comment('Cumul versé à Avia');
            $table->timestamps();
        });

        // ─── Recharges SMS via Wave ───
        Schema::create('sms_recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('demandeur_id')->constrained('users');
            $table->string('reference', 40)->unique();
            $table->unsignedInteger('nb_sms');
            $table->decimal('montant_fcfa', 14, 0);
            $table->decimal('prix_unitaire_fcfa', 6, 0)->default(50);
            $table->string('wave_checkout_url', 500)->nullable();
            $table->enum('statut', ['en_attente_paiement', 'paye', 'credite', 'annule', 'expire'])
                ->default('en_attente_paiement');
            $table->timestamp('paye_at')->nullable();
            $table->timestamp('credite_at')->nullable();
            $table->foreignId('credite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes_admin')->nullable();
            $table->timestamps();
            $table->index(['etablissement_id', 'statut']);
        });

        // ─── Envois SMS ───
        Schema::create('sms_envois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('envoye_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('destinataire', 25)->comment('Numéro normalisé E.164');
            $table->string('destinataire_nom', 200)->nullable();
            $table->text('contenu');
            $table->enum('type', ['relance_impaye', 'annonce', 'note', 'absence', 'manuel', 'autre'])
                ->default('manuel');
            $table->enum('statut', ['en_attente', 'envoye', 'echec', 'recu', 'expire'])
                ->default('en_attente');
            $table->string('infobip_message_id', 80)->nullable();
            $table->text('infobip_response')->nullable();
            $table->string('erreur', 500)->nullable();
            $table->unsignedTinyInteger('nb_parties')->default(1)->comment('1 SMS = 160 car, sinon multi-parties');
            $table->string('reference_type', 50)->nullable()->comment('paiement, inscription, etc');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['etablissement_id', 'created_at']);
            $table->index(['etablissement_id', 'type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_envois');
        Schema::dropIfExists('sms_recharges');
        Schema::dropIfExists('sms_credits');
    }
};
