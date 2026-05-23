<?php

namespace App\Services\Comptabilite;

use App\Models\AnneeScolaire;
use App\Models\CompteComptable;
use App\Models\Etablissement;
use App\Models\ExerciceComptable;
use Illuminate\Support\Facades\DB;

class SyscohadaPlanService
{
    public const CLASSES = [
        '1' => 'Capitaux et ressources durables',
        '2' => 'Immobilisations',
        '3' => 'Stocks',
        '4' => 'Comptes de tiers',
        '5' => 'Trésorerie',
        '6' => 'Charges',
        '7' => 'Produits',
        '8' => 'Autres charges et produits',
    ];

    public function ensureBase(Etablissement $etablissement): array
    {
        return DB::transaction(function () use ($etablissement) {
            $createdAccounts = $this->ensureAccounts($etablissement);
            [$exercice, $createdExercise] = $this->ensureCurrentExercise($etablissement);

            return [
                'created_accounts' => $createdAccounts,
                'created_exercise' => $createdExercise,
                'exercice' => $exercice,
            ];
        });
    }

    public function classLabel(?string $numero): string
    {
        $classe = substr((string) $numero, 0, 1);

        return self::CLASSES[$classe] ?? 'Classe non classée';
    }

    public function classNumber(?string $numero): string
    {
        $classe = substr((string) $numero, 0, 1);

        return $classe !== '' ? $classe : '-';
    }

    /**
     * Déclinaison minimale du plan SYSCOHADA utile au contexte scolaire.
     *
     * @return array<int, array<string, string|null>>
     */
    public function defaultAccounts(): array
    {
        return [
            ['numero' => '101000', 'libelle' => 'Capital / fonds de dotation', 'type' => 'passif', 'categorie' => null],
            ['numero' => '106000', 'libelle' => 'Réserves', 'type' => 'passif', 'categorie' => null],
            ['numero' => '161000', 'libelle' => 'Emprunts et dettes financières', 'type' => 'passif', 'categorie' => 'dettes'],

            ['numero' => '213000', 'libelle' => 'Logiciels et solutions numériques', 'type' => 'actif', 'categorie' => null],
            ['numero' => '244000', 'libelle' => 'Matériel et mobilier scolaires', 'type' => 'actif', 'categorie' => null],
            ['numero' => '245000', 'libelle' => 'Matériel de transport', 'type' => 'actif', 'categorie' => null],

            ['numero' => '311000', 'libelle' => 'Fournitures scolaires en stock', 'type' => 'actif', 'categorie' => 'fournitures'],

            ['numero' => '401000', 'libelle' => 'Fournisseurs d’exploitation', 'type' => 'passif', 'categorie' => 'dettes'],
            ['numero' => '411000', 'libelle' => 'Clients - parents et élèves', 'type' => 'actif', 'categorie' => 'creances'],
            ['numero' => '421000', 'libelle' => 'Personnel - rémunérations dues', 'type' => 'passif', 'categorie' => 'dettes'],
            ['numero' => '431000', 'libelle' => 'Organismes sociaux', 'type' => 'passif', 'categorie' => 'dettes'],
            ['numero' => '447000', 'libelle' => 'État, impôts et taxes', 'type' => 'passif', 'categorie' => 'dettes'],

            ['numero' => '521000', 'libelle' => 'Banques locales', 'type' => 'tresorerie', 'categorie' => 'banque'],
            ['numero' => '533000', 'libelle' => 'Comptes mobile money', 'type' => 'tresorerie', 'categorie' => 'mobile_money'],
            ['numero' => '571000', 'libelle' => 'Caisse', 'type' => 'tresorerie', 'categorie' => 'caisse'],

            ['numero' => '604000', 'libelle' => 'Achats de fournitures', 'type' => 'charge', 'categorie' => 'fournitures'],
            ['numero' => '611000', 'libelle' => 'Transports sur achats et services', 'type' => 'charge', 'categorie' => 'transport_charge'],
            ['numero' => '622000', 'libelle' => 'Locations et charges locatives', 'type' => 'charge', 'categorie' => 'loyer'],
            ['numero' => '624000', 'libelle' => 'Entretien, réparations et maintenance', 'type' => 'charge', 'categorie' => 'maintenance'],
            ['numero' => '625000', 'libelle' => 'Assurances', 'type' => 'charge', 'categorie' => 'assurances'],
            ['numero' => '628000', 'libelle' => 'Télécommunications', 'type' => 'charge', 'categorie' => 'telecom'],
            ['numero' => '631000', 'libelle' => 'Impôts et taxes', 'type' => 'charge', 'categorie' => 'impots'],
            ['numero' => '641000', 'libelle' => 'Rémunérations du personnel', 'type' => 'charge', 'categorie' => 'salaires'],
            ['numero' => '646000', 'libelle' => 'Charges sociales', 'type' => 'charge', 'categorie' => 'charges_sociales'],
            ['numero' => '681000', 'libelle' => 'Dotations aux amortissements', 'type' => 'charge', 'categorie' => 'amortissements'],

            ['numero' => '706100', 'libelle' => 'Services vendus - scolarité', 'type' => 'produit', 'categorie' => 'scolarite'],
            ['numero' => '706200', 'libelle' => 'Services vendus - inscription', 'type' => 'produit', 'categorie' => 'inscription'],
            ['numero' => '706300', 'libelle' => 'Services vendus - cantine', 'type' => 'produit', 'categorie' => 'cantine'],
            ['numero' => '706400', 'libelle' => 'Services vendus - transport scolaire', 'type' => 'produit', 'categorie' => 'transport'],
            ['numero' => '718000', 'libelle' => 'Autres produits d’exploitation', 'type' => 'produit', 'categorie' => 'autres_revenus'],
            ['numero' => '741000', 'libelle' => 'Subventions d’exploitation', 'type' => 'produit', 'categorie' => 'subventions'],
        ];
    }

    private function ensureAccounts(Etablissement $etablissement): int
    {
        $created = 0;

        foreach ($this->defaultAccounts() as $account) {
            $compte = CompteComptable::firstOrCreate(
                [
                    'etablissement_id' => $etablissement->id,
                    'numero' => $account['numero'],
                ],
                [
                    'libelle' => $account['libelle'],
                    'type' => $account['type'],
                    'categorie' => $account['categorie'],
                    'parent_numero' => substr($account['numero'], 0, 1),
                    'solde_initial' => 0,
                    'solde_actuel' => 0,
                    'actif' => true,
                    'systeme' => true,
                ]
            );

            if ($compte->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return array{0: ExerciceComptable|null, 1: bool}
     */
    private function ensureCurrentExercise(Etablissement $etablissement): array
    {
        $annee = $this->currentSchoolYear($etablissement);
        if (! $annee) {
            return [null, false];
        }

        $exercice = ExerciceComptable::firstOrNew([
            'etablissement_id' => $etablissement->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        $created = ! $exercice->exists;

        $exercice->fill([
            'libelle' => 'Exercice '.$annee->libelle,
            'date_debut' => $annee->date_debut,
            'date_fin' => $annee->date_fin,
            'en_cours' => true,
            'cloture' => false,
            'solde_ouverture' => $exercice->solde_ouverture ?? 0,
        ]);

        if ($created || ! $exercice->en_cours) {
            ExerciceComptable::where('etablissement_id', $etablissement->id)
                ->where('en_cours', true)
                ->update(['en_cours' => false]);
        }

        $exercice->save();

        return [$exercice, $created];
    }

    private function currentSchoolYear(Etablissement $etablissement): ?AnneeScolaire
    {
        return $etablissement->anneesScolaires()
            ->where('en_cours', true)
            ->where('cloturee', false)
            ->first();
    }
}
