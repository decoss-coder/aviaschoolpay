<?php

namespace App\Console\Commands;

use App\Models\AnneeScolaire;
use App\Models\EmploiDuTemps;
use App\Services\Scolarite\AnneeScolaireArchiveService;
use Illuminate\Console\Command;

class InspecterArchiveEdtCommand extends Command
{
    protected $signature = 'annee:inspecter-archive-edt {annee_id?}';

    protected $description = 'Affiche le nombre de créneaux EDT en base et dans le fichier .enc';

    public function handle(): int
    {
        $query = AnneeScolaire::query()->orderByDesc('date_debut');
        if ($this->argument('annee_id')) {
            $query->where('id', $this->argument('annee_id'));
        }

        foreach ($query->get() as $annee) {
            $enBase = EmploiDuTemps::where('annee_scolaire_id', $annee->id)->count();
            $dansArchive = -1;
            if ($annee->archive_path && $annee->restoration_key_vault) {
                $dansArchive = AnneeScolaireArchiveService::compterEmploiDansArchive($annee);
            }

            $this->line(sprintf(
                '%s (#%d) | en_cours=%d | base=%d | archive=%s | fichier=%s',
                $annee->libelle,
                $annee->id,
                (int) $annee->en_cours,
                $enBase,
                $dansArchive < 0 ? 'n/a' : (string) $dansArchive,
                $annee->archive_path ?: '—'
            ));
        }

        return self::SUCCESS;
    }
}
