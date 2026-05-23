<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════
// 02 — UTILISATEURS, RÔLES ET PERMISSIONS
// ══════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── Users (étendu pour multi-rôles) ──
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->nullable();
            $table->string('telephone', 20)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', [
                'super_admin',
                'directeur',
                'sous_directeur',
                'surveillant',
                'comptable',
                'secretaire',
                'enseignant',
                'parent',
                'eleve',
                'drena',
                'ddena'
            ]);
            $table->string('avatar_path')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('premiere_connexion')->default(true);
            $table->timestamp('derniere_connexion')->nullable();
            $table->string('langue', 5)->default('fr');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['etablissement_id', 'role']);
            $table->index('telephone');
        });

        // ── Password reset ──
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('telephone', 20)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ── Sessions ──
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ── Personal access tokens (Sanctum) ──
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // ── Permissions granulaires ──
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->unique();
            $table->string('description')->nullable();
            $table->string('module', 50)->nullable()->comment('Module concerné');
            $table->timestamps();
        });

        // ── Pivot rôle-permission ──
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 30);
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role', 'permission_id', 'etablissement_id']);
        });

        // ── Journal d'activité (audit log) ──
        Schema::create('journal_activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('action', 100)->comment('Ex: creation_eleve, pointage_enseignant, paiement');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('ancien_valeurs')->nullable();
            $table->json('nouveau_valeurs')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['etablissement_id', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('journal_activites');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::enableForeignKeyConstraints();
    }
};
