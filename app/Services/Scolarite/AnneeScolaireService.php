<?php

namespace App\Services\Scolarite;

use App\Models\AnneeScolaire;
use App\Models\Trimestre;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnneeScolaireService
{
    public static function courantePourEtablissement(int $etablissementId): ?AnneeScolaire
    {
        return AnneeScolaire::query()
            ->where('etablissement_id', $etablissementId)
            ->where('en_cours', true)
            ->where('cloturee', false)
            ->where('archivee', false)
            ->first();
    }

    public static function couranteOuEchec(int $etablissementId): AnneeScolaire
    {
        $annee = self::courantePourEtablissement($etablissementId);

        if (! $annee) {
            throw ValidationException::withMessages([
                'annee_scolaire' => ['Aucune année scolaire en cours. Paramétrez une année dans Administration → Années scolaires.'],
            ]);
        }

        return $annee;
    }

    public static function initialiserContexte(int $etablissementId): ?AnneeScolaire
    {
        $annee = self::courantePourEtablissement($etablissementId);
        AnneeScolaireContext::set($annee, $etablissementId);

        return $annee;
    }

    public static function creer(
        int $etablissementId,
        string $libelle,
        string $dateDebut,
        string $dateFin,
        bool $activer = false
    ): AnneeScolaire {
        if (strtotime($dateFin) < strtotime($dateDebut)) {
            throw ValidationException::withMessages([
                'date_fin' => ['La date de fin doit être postérieure à la date de début.'],
            ]);
        }

        return DB::transaction(function () use ($etablissementId, $libelle, $dateDebut, $dateFin, $activer) {
            if ($activer) {
                AnneeScolaire::query()
                    ->where('etablissement_id', $etablissementId)
                    ->where('en_cours', true)
                    ->update(['en_cours' => false]);
            }

            $annee = AnneeScolaire::create([
                'etablissement_id' => $etablissementId,
                'libelle' => $libelle,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'en_cours' => $activer,
                'cloturee' => false,
                'archivee' => false,
            ]);

            self::creerTrimestresParDefaut($annee);

            if ($activer) {
                AnneeScolaireContext::set($annee, $etablissementId);
            }

            return $annee;
        });
    }

    public static function activer(AnneeScolaire $annee): AnneeScolaire
    {
        if ($annee->archivee) {
            throw ValidationException::withMessages([
                'annee' => ['Cette année est encore archivée (chiffrée). Restaurez-la avec la clé avant de l\'activer.'],
            ]);
        }

        return DB::transaction(function () use ($annee) {
            AnneeScolaire::query()
                ->where('etablissement_id', $annee->etablissement_id)
                ->where('id', '!=', $annee->id)
                ->update(['en_cours' => false]);

            $annee->update(['en_cours' => true, 'cloturee' => false]);
            $annee = $annee->fresh();

            if (! empty($annee->archive_meta['restaurer_le'])) {
                AnneeScolaireDonneesService::synchroniserDepuisInscriptions($annee);
            }

            AnneeScolaireContext::set($annee, $annee->etablissement_id);

            return $annee;
        });
    }

    public static function creerTrimestresParDefaut(AnneeScolaire $annee): void
    {
        if ($annee->trimestres()->exists()) {
            return;
        }

        $debut = $annee->date_debut;
        $fin = $annee->date_fin;
        $totalDays = max(1, $debut->diffInDays($fin));
        $segment = (int) floor($totalDays / 3);

        for ($n = 1; $n <= 3; $n++) {
            $start = $debut->copy()->addDays(($n - 1) * $segment);
            $end = $n === 3 ? $fin : $debut->copy()->addDays($n * $segment - 1);

            Trimestre::create([
                'annee_scolaire_id' => $annee->id,
                'numero' => $n,
                'libelle' => "Trimestre {$n}",
                'date_debut' => $start,
                'date_fin' => $end,
                'en_cours' => $n === 1,
            ]);
        }
    }

    /** @return array<int, AnneeScolaire> */
    public static function listePourEtablissement(int $etablissementId): array
    {
        return AnneeScolaire::query()
            ->where('etablissement_id', $etablissementId)
            ->orderByDesc('date_debut')
            ->get()
            ->all();
    }
}
