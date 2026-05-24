<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_role_access_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('etablissement_id');
            $table->string('role', 50);
            $table->string('menu_key', 100);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'role', 'menu_key'], 'school_role_access_unique');
            $table->index(['etablissement_id', 'role'], 'school_role_access_etab_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_role_access_blocks');
    }
};
