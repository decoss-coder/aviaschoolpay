<?php

namespace App\Services\Comptabilite;

use App\Models\CompteComptable;
use App\Models\EcritureComptable;
use App\Models\Etablissement;
use App\Models\ExerciceComptable;
use App\Models\Paiement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComptabilisationService
{
    public function __construct(private SyscohadaPlanService $planService)
    {
    }

    public function countPaiementsAComptabiliser(int $etablissementId): int
    {
        return Paiement::where('etablissement_id', $etablissementId)
            ->where('statut', 'confirme')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ecritures_comptables')
                    ->whereColumn('ecritures_comptables.reference_id', 'paiements.id')
                    ->where('ecritures_comptables.reference_type', 'paiement');
            })
            ->count();
    }

    public function posterPaiementsConfirmes(Etablissement $etablissement, int $userId): array
    {
        $this->planService->ensureBase($etablissement);

        $exercice = ExerciceComptable::where('etablissement_id', $etablissement->id)
            ->where('en_cours', true)
            ->where('cloture', false)
            ->first();

        if (! $exercice) {
            return ['posted' => 0, 'skipped' => 0, 'errors' => ['Aucun exercice comptable ouvert.']];
        }

        $paiements = $this->paiementsAComptabiliser($etablissement->id)->get();

        $posted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($paiements as $paiement) {
            try {
                $posted += $this->posterPaiement($paiement, $exercice, $userId);
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = $paiement->reference.': '.$e->getMessage();
            }
        }

        return compact('posted', 'skipped', 'errors');
    }

    public function comptabiliserPaiement(Paiement $paiement, int $userId): int
    {
        if (EcritureComptable::where('reference_type', 'paiement')->where('reference_id', $paiement->id)->exists()) {
            return 0;
        }

        $exercice = ExerciceComptable::where('etablissement_id', $paiement->etablissement_id)
            ->where('en_cours', true)
            ->where('cloture', false)
            ->first();

        if (! $exercice) {
            return 0;
        }

        return $this->posterPaiement($paiement, $exercice, $userId);
    }

    private function paiementsAComptabiliser(int $etablissementId)
    {
        return Paiement::where('etablissement_id', $etablissementId)
            ->where('statut', 'confirme')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ecritures_comptables')
                    ->whereColumn('ecritures_comptables.reference_id', 'paiements.id')
                    ->where('ecritures_comptables.reference_type', 'paiement');
            })
            ->orderBy('date_paiement')
            ->orderBy('id');
    }

    private function posterPaiement(Paiement $paiement, ExerciceComptable $exercice, int $userId): int
    {
        return DB::transaction(function () use ($paiement, $exercice, $userId) {
            $compteDebit = $this->compteTresoreriePourPaiement($paiement);
            $parts = $this->ventilerPaiement($paiement);

            $count = 0;
            foreach ($parts as $part) {
                if ($part['montant'] <= 0) {
                    continue;
                }

                $compteCredit = $this->compteProduit($paiement->etablissement_id, $part['numero_compte']);

                $ecriture = EcritureComptable::create([
                    'etablissement_id' => $paiement->etablissement_id,
                    'exercice_id' => $exercice->id,
                    'numero_piece' => EcritureComptable::genererNumero($paiement->etablissement_id),
                    'date_ecriture' => $paiement->date_paiement ?? now()->toDateString(),
                    'libelle' => $part['libelle'].' - '.$paiement->reference,
                    'compte_debit_id' => $compteDebit->id,
                    'compte_credit_id' => $compteCredit->id,
                    'montant' => $part['montant'],
                    'type_piece' => 'paiement_scolarite',
                    'reference_externe' => $paiement->reference,
                    'reference_type' => 'paiement',
                    'reference_id' => $paiement->id,
                    'saisie_par' => $paiement->encaisse_par ?: $userId,
                    'valide_par' => $userId,
                    'valide' => true,
                    'observations' => 'Comptabilisation automatique SYSCOHADA.',
                ]);

                $ecriture->compteDebit->recalculerSolde();
                $ecriture->compteCredit->recalculerSolde();
                $count++;
            }

            return $count;
        });
    }

    /**
     * @return Collection<int, array{numero_compte: string, libelle: string, montant: int}>
     */
    private function ventilerPaiement(Paiement $paiement): Collection
    {
        $montantInscription = (int) ($paiement->montant_inscription ?? 0);
        $montantScolarite = (int) ($paiement->montant_scolarite ?? 0);

        if ($montantInscription <= 0 && $montantScolarite <= 0) {
            $montantScolarite = (int) $paiement->montant;
        }

        return collect([
            [
                'numero_compte' => '706200',
                'libelle' => 'Frais d\'inscription',
                'montant' => $montantInscription,
            ],
            [
                'numero_compte' => '706100',
                'libelle' => 'Frais de scolarité',
                'montant' => $montantScolarite,
            ],
        ]);
    }

    private function compteTresoreriePourPaiement(Paiement $paiement): CompteComptable
    {
        $numero = match ($paiement->mode) {
            'wave', 'orange_money', 'mtn_money', 'moov_money' => '533000',
            'cheque', 'virement', 'carte_bancaire' => '521000',
            default => '571000',
        };

        return $this->compte($paiement->etablissement_id, $numero);
    }

    private function compteProduit(int $etablissementId, string $numero): CompteComptable
    {
        return $this->compte($etablissementId, $numero);
    }

    private function compte(int $etablissementId, string $numero): CompteComptable
    {
        return CompteComptable::where('etablissement_id', $etablissementId)
            ->where('numero', $numero)
            ->firstOrFail();
    }
}
