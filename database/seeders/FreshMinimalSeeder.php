<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder MINIMAL post-reset :
 *  - 1 établissement "siège Avia" (juste pour l'attachement FK)
 *  - 1 super_admin
 *
 * Tout le reste (établissements clients, années, classes, etc.)
 * est à créer par le super_admin via l'interface web.
 *
 * Usage : php artisan db:seed --class=FreshMinimalSeeder
 */
class FreshMinimalSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Établissement siège Avia (porteur du super_admin) ──
        $siege = Etablissement::firstOrCreate(
            ['code_desps' => 'AVIA-SIEGE'],
            [
                'nom'              => 'Avia Technologie — Siège',
                'sigle'            => 'AVIA',
                'type'             => 'mixte',
                'statut_juridique' => 'prive_laic',
                'adresse'          => 'Siège Avia',
                'ville'            => 'Abidjan',
                'commune'          => null,
                'telephone'        => '+225 0000000000',
                'email'            => 'contact@avia-tech.ci',
                'actif'            => true,
            ]
        );

        // ── 2. Super admin ──
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@avia.ci'],
            [
                'etablissement_id'   => $siege->id,
                'nom'                => 'ADMIN',
                'prenom'             => 'Super',
                'telephone'          => '0700000000',
                'password'           => Hash::make('password'),
                'role'               => 'super_admin',
                'actif'              => true,
                'premiere_connexion' => false,
            ]
        );

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  Base réinitialisée — environnement vierge prêt');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  Connexion super_admin :');
        $this->command->info('    Email    : superadmin@avia.ci');
        $this->command->info('    Password : password');
        $this->command->info('');
        $this->command->info('  Établissement siège Avia créé (ID '.$siege->id.')');
        $this->command->info('  → Utilisez l\'interface /admin/etablissements pour créer');
        $this->command->info('    les établissements clients réels.');
        $this->command->info('═══════════════════════════════════════════════════════');
    }
}
