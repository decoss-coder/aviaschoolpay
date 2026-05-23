<?php

namespace App\Services\Scolarite;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnneeScolaireTransitionService
{
    /**
     * Clôture une année archivée, prépare l'année en cours et synchronise le contexte applicatif.
     *
     * @return array{
     *     restoration_key: string,
     *     path: string,
     *     meta: array,
     *     annee_courante: ?AnneeScolaire,
     *     classes_provisionnees: int
     * }
     */
    public static function cloturerEtBasculer(AnneeScolaire $annee, User $user, bool $purgerDonnees = true): array
    {
        $etabId = $annee->etablissement_id;

        $anneeCourante = AnneeScolaireService::courantePourEtablissement($etabId);
        if (! $anneeCourante) {
            throw ValidationException::withMessages([
                'annee' => [
                    'Aucune année en cours. Créez et activez la nouvelle année avant de clôturer l\'ancienne.',
                ],
            ]);
        }

        if ($anneeCourante->id === $annee->id) {
            throw ValidationException::withMessages([
                'annee' => ['Impossible de clôturer l\'année actuellement active.'],
            ]);
        }

        $classesSource = Classe::query()
            ->where('annee_scolaire_id', $annee->id)
            ->where('active', true)
            ->get();

        $result = AnneeScolaireArchiveService::cloturerEtArchiver($annee, $user, $purgerDonnees);

        $classesProvisionnees = self::provisionnerClassesAnneeCourante($anneeCourante, $classesSource);

        $anneeCourante = $anneeCourante->fresh();
        AnneeScolaireService::initialiserContexte($etabId);

        return array_merge($result, [
            'annee_courante' => $anneeCourante,
            'classes_provisionnees' => $classesProvisionnees,
        ]);
    }

    /**
     * Recrée les classes vides de l'année en cours à partir de la structure de l'année clôturée.
     */
    public static function provisionnerClassesAnneeCourante(AnneeScolaire $anneeCourante, $classesSource): int
    {
        if ($classesSource->isEmpty()) {
            return 0;
        }

        $existantes = Classe::query()
            ->where('annee_scolaire_id', $anneeCourante->id)
            ->pluck('nom')
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))
            ->all();

        $creees = 0;

        DB::transaction(function () use ($classesSource, $anneeCourante, $existantes, &$creees) {
            foreach ($classesSource as $source) {
                $nomNormalise = mb_strtolower(trim($source->nom));
                if (in_array($nomNormalise, $existantes, true)) {
                    continue;
                }

                Classe::create([
                    'etablissement_id' => $anneeCourante->etablissement_id,
                    'annee_scolaire_id' => $anneeCourante->id,
                    'niveau_id' => $source->niveau_id,
                    'serie_id' => $source->serie_id,
                    'nom' => $source->nom,
                    'capacite' => $source->capacite,
                    'effectif' => 0,
                    'scolarite_annuelle' => $source->scolarite_annuelle,
                    'frais_inscription' => $source->frais_inscription,
                    'frais_reinscription' => $source->frais_reinscription,
                    'description' => $source->description,
                    'professeur_principal_id' => $source->professeur_principal_id,
                    'active' => true,
                ]);

                $existantes[] = $nomNormalise;
                $creees++;
            }
        });

        return $creees;
    }
}
