<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Niveau;
use App\Models\Serie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultSchoolLevelsSeeder extends Seeder
{
    public function run(): void
    {
        $targetEtabId = env('SEED_ETABLISSEMENT_ID');

        $etablissements = Etablissement::query()
            ->when($targetEtabId, fn ($query) => $query->where('id', (int) $targetEtabId))
            ->orderBy('id')
            ->get();

        if ($etablissements->isEmpty()) {
            $this->command?->warn('Aucun établissement trouvé pour le remplissage des niveaux.');
            return;
        }

        // Valeurs compatibles avec l'énumération existante de la colonne niveaux.cycle.
        $niveaux = [
            ['code' => '6EME',  'libelle' => '6e',         'cycle' => 'premier_cycle', 'ordre' => 10],
            ['code' => '5EME',  'libelle' => '5e',         'cycle' => 'premier_cycle', 'ordre' => 20],
            ['code' => '4EME',  'libelle' => '4e',         'cycle' => 'premier_cycle', 'ordre' => 30],
            ['code' => '3EME',  'libelle' => '3e',         'cycle' => 'premier_cycle', 'ordre' => 40],
            ['code' => '2NDE',  'libelle' => 'Seconde',    'cycle' => 'second_cycle',  'ordre' => 50],
            ['code' => '1ERE',  'libelle' => 'Première',   'cycle' => 'second_cycle',  'ordre' => 60],
            ['code' => 'TLE',   'libelle' => 'Terminale',  'cycle' => 'second_cycle',  'ordre' => 70],
        ];

        $series = [
            ['code' => 'A', 'libelle' => 'Série A'],
            ['code' => 'C', 'libelle' => 'Série C'],
            ['code' => 'D', 'libelle' => 'Série D'],
        ];

        DB::transaction(function () use ($etablissements, $niveaux, $series): void {
            foreach ($series as $serie) {
                Serie::updateOrCreate(
                    ['code' => $serie['code']],
                    ['libelle' => $serie['libelle']]
                );
            }

            foreach ($etablissements as $etablissement) {
                foreach ($niveaux as $niveau) {
                    Niveau::updateOrCreate(
                        [
                            'etablissement_id' => $etablissement->id,
                            'code' => $niveau['code'],
                        ],
                        [
                            'libelle' => $niveau['libelle'],
                            'cycle' => $niveau['cycle'],
                            'ordre' => $niveau['ordre'],
                            'frais_scolarite_defaut' => 0,
                            'frais_inscription_defaut' => 0,
                            'frais_reinscription_defaut' => 0,
                            'actif' => true,
                        ]
                    );
                }
            }
        });

        $this->command?->info('Niveaux et séries remplis avec succès.');
    }
}
