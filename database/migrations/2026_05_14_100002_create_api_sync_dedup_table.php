<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_dedup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_mutation_id', 64);
            $table->string('resource_type', 64);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('response_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_mutation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_dedup');
    }
};
