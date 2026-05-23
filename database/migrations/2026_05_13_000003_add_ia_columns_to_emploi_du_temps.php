<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            if (!Schema::hasColumn('emploi_du_temps', 'source')) {
                $table->enum('source', ['ia', 'manuel', 'ajustement'])->default('manuel')->after('actif');
            }
            if (!Schema::hasColumn('emploi_du_temps', 'generation_uuid')) {
                $table->string('generation_uuid', 36)->nullable()->after('source');
            }
            if (!Schema::hasColumn('emploi_du_temps', 'locked_by_user')) {
                $table->boolean('locked_by_user')->default(false)->after('generation_uuid');
            }
            if (!Schema::hasColumn('emploi_du_temps', 'ia_score')) {
                $table->decimal('ia_score', 8, 2)->nullable()->after('locked_by_user');
            }
            if (!Schema::hasColumn('emploi_du_temps', 'last_adjusted_by')) {
                $table->foreignId('last_adjusted_by')->nullable()->constrained('users')->nullOnDelete()->after('ia_score');
            }
            if (!Schema::hasColumn('emploi_du_temps', 'last_adjusted_at')) {
                $table->timestamp('last_adjusted_at')->nullable()->after('last_adjusted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropColumn([
                'source', 'generation_uuid', 'locked_by_user',
                'ia_score', 'last_adjusted_by', 'last_adjusted_at',
            ]);
        });
    }
};
