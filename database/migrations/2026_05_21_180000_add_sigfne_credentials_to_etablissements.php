<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->boolean('sigfne_actif')->default(false)->after('code_desps')
                ->comment('Activer la synchronisation SIGFNE/AGFNE');
            $table->string('sigfne_login', 100)->nullable()->after('sigfne_actif')
                ->comment('Identifiant SIGFNE (souvent = code_desps)');
            $table->text('sigfne_token')->nullable()->after('sigfne_login')
                ->comment('Token ou mot de passe chiffré');
            $table->string('sigfne_plateforme', 20)->nullable()->after('sigfne_token')
                ->comment('agfne (secondaire) ou agcp (primaire)');
            $table->timestamp('sigfne_derniere_sync')->nullable()->after('sigfne_plateforme');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn([
                'sigfne_actif', 'sigfne_login', 'sigfne_token',
                'sigfne_plateforme', 'sigfne_derniere_sync',
            ]);
        });
    }
};
