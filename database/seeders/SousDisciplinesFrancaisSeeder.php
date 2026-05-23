<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Matiere;
use Illuminate\Database\Seeder;

/**
 * Sous-disciplines de Français (collège 6e/5e/4e/3e) — standard ivoirien :
 *   • CF (Composition française)   — poids 3
 *   • OG (Orthographe & grammaire) — poids 1
 *   • EO (Expression orale)        — poids 1
 *
 * Lancement :
 *   php artisan db:seed --class=SousDisciplinesFrancaisSeeder
 */
class SousDisciplinesFrancaisSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            ['code' => 'CF', 'nom' => 'Composition française',    'poids' => 3, 'ordre' => 1],
            ['code' => 'OG', 'nom' => 'Orthographe et grammaire', 'poids' => 1, 'ordre' => 2],
            ['code' => 'EO', 'nom' => 'Expression orale',         'poids' => 1, 'ordre' => 3],
        ];

        $etabs = Etablissement::all();
        $this->command->info("Application des sous-disciplines Français à {$etabs->count()} établissement(s)…");

        foreach ($etabs as $etab) {
            $fr = Matiere::where('etablissement_id', $etab->id)
                ->whereIn('code', ['FR', 'FRA', 'FRAN', 'FRANC'])
                ->whereNull('parent_matiere_id')
                ->first();

            if (! $fr) {
                $this->command->warn("  ⚠ {$etab->nom} : matière 'Français' (FR/FRA/FRAN) introuvable — sautée.");
                continue;
            }

            $created = 0;
            foreach ($presets as $p) {
                $existing = Matiere::where('etablissement_id', $etab->id)
                    ->where('code', $p['code'])
                    ->first();

                if ($existing) {
                    // Mise à jour si nécessaire
                    $existing->update([
                        'parent_matiere_id' => $fr->id,
                        'poids_dans_parent' => $p['poids'],
                        'ordre'             => $p['ordre'],
                        'active'            => true,
                    ]);
                } else {
                    Matiere::create([
                        'etablissement_id'   => $etab->id,
                        'parent_matiere_id'  => $fr->id,
                        'code'               => $p['code'],
                        'nom'                => $p['nom'],
                        'coefficient_defaut' => $fr->coefficient_defaut,
                        'poids_dans_parent'  => $p['poids'],
                        'ordre'              => $p['ordre'],
                        'active'             => true,
                    ]);
                    $created++;
                }
            }

            $this->command->line("  ✓ {$etab->nom} : {$created} créée(s), " . (count($presets) - $created) . " mise(s) à jour");
        }

        $this->command->info('Terminé. Note : les sous-disciplines s\'appliquent automatiquement aux classes 6e/5e/4e/3e dès qu\'un prof Français leur est affecté.');
    }
}
