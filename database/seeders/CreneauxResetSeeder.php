<?php

namespace Database\Seeders;

use App\Models\Creneau;
use App\Models\Etablissement;
use Illuminate\Database\Seeder;

/**
 * Réinitialise les créneaux horaires de tous les établissements
 * sur la plage standard 07h00 → 18h00.
 *
 * Lancement :
 *   php artisan db:seed --class=CreneauxResetSeeder
 */
class CreneauxResetSeeder extends Seeder
{
    public function run(): void
    {
        // Plage standard 07h00 → 18h00 — 11 cours + 1 récré + 1 pause déjeuner
        $creneauxData = [
            ['C1',         '07:00', '07:55',  1, 'cours'],
            ['C2',         '07:55', '08:50',  2, 'cours'],
            ['C3',         '08:50', '09:45',  3, 'cours'],
            ['RÉCRÉATION', '09:45', '10:00',  4, 'recreation'],
            ['C4',         '10:00', '10:55',  5, 'cours'],
            ['C5',         '10:55', '11:50',  6, 'cours'],
            ['C6',         '11:50', '12:45',  7, 'cours'],
            ['PAUSE',      '12:45', '14:00',  8, 'pause_dejeuner'],
            ['C7',         '14:00', '14:55',  9, 'cours'],
            ['C8',         '14:55', '15:50', 10, 'cours'],
            ['C9',         '15:50', '16:45', 11, 'cours'],
            ['C10',        '16:45', '17:40', 12, 'cours'],
            ['C11',        '17:40', '18:00', 13, 'cours'],
        ];

        $etabs = Etablissement::all();
        $this->command->info("Mise à jour des créneaux pour {$etabs->count()} établissement(s)…");

        foreach ($etabs as $etab) {
            foreach ($creneauxData as [$lib, $deb, $fin, $ordre, $type]) {
                $existing = Creneau::where('etablissement_id', $etab->id)
                    ->where('libelle', $lib)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'heure_debut' => $deb,
                        'heure_fin'   => $fin,
                        'ordre'       => $ordre,
                        'type'        => $type,
                    ]);
                } else {
                    Creneau::create([
                        'etablissement_id' => $etab->id,
                        'libelle'          => $lib,
                        'heure_debut'      => $deb,
                        'heure_fin'        => $fin,
                        'type'             => $type,
                        'ordre'            => $ordre,
                    ]);
                }
            }

            $this->command->line("  ✓ {$etab->nom} → 13 créneaux (07h00 → 18h00)");
        }

        $this->command->info('Terminé.');
    }
}
