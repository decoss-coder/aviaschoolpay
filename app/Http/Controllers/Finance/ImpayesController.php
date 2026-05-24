<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ImpayesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $allowedRoles = ['super_admin', 'fondateur', 'directeur', 'directeur_adjoint', 'gestionnaire', 'secretaire', 'comptable', 'censeur'];
        abort_unless(in_array($user->role, $allowedRoles, true), 403);

        $etabId = (int) $user->ecoleActiveId();
        abort_unless($etabId, 403, 'Aucun établissement associé.');

        $annee = AnneeScolaireContext::courantePourEtablissement($etabId);
        abort_unless($annee, 422, 'Aucune année scolaire en cours.');

        $poste = $request->input('poste', 'tous');
        if (! in_array($poste, ['tous', 'inscription', 'scolarite'], true)) {
            $poste = 'tous';
        }

        $niveauId = $request->integer('niveau_id') ?: null;
        $classeId = $request->integer('classe_id') ?: null;
        $q = trim((string) $request->input('q', ''));

        $inscriptions = Inscription::query()
            ->where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->with([
                'eleve:id,matricule_interne,matricule_desps,nom,prenom,sexe,contact_urgence_tel',
                'classe:id,nom,niveau_id',
                'classe.niveau:id,code,libelle,ordre',
                'paiements' => fn ($query) => $query->where('statut', 'confirme'),
            ])
            ->when($classeId, fn ($query) => $query->where('classe_id', $classeId))
            ->when($niveauId, fn ($query) => $query->whereHas('classe', fn ($sub) => $sub->where('niveau_id', $niveauId)))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('eleve', function ($sub) use ($q) {
                    $sub->where('nom', 'like', "%{$q}%")
                        ->orWhere('prenom', 'like', "%{$q}%")
                        ->orWhere('matricule_interne', 'like', "%{$q}%")
                        ->orWhere('matricule_desps', 'like', "%{$q}%");
                });
            })
            ->get();

        $rows = $inscriptions
            ->map(fn (Inscription $inscription) => $this->buildRow($inscription))
            ->filter(function (array $row) use ($poste) {
                return match ($poste) {
                    'inscription' => $row['reste_inscription'] > 0,
                    'scolarite' => $row['reste_scolarite'] > 0,
                    default => $row['reste_total'] > 0,
                };
            })
            ->sortBy([
                fn ($a, $b) => ($a['niveau_ordre'] <=> $b['niveau_ordre']),
                fn ($a, $b) => strcmp($a['classe_nom'], $b['classe_nom']),
                fn ($a, $b) => strcmp($a['eleve_nom'], $b['eleve_nom']),
            ])
            ->values();

        $totaux = [
            'reste_inscription' => $rows->sum('reste_inscription'),
            'reste_scolarite' => $rows->sum('reste_scolarite'),
            'reste_total' => $rows->sum('reste_total'),
            'effectif' => $rows->count(),
        ];

        $totauxParNiveau = $this->aggregate($rows, 'niveau_libelle');
        $totauxParClasse = $this->aggregate($rows, 'classe_nom');

        $niveaux = Niveau::where('etablissement_id', $etabId)
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        $classes = Classe::where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau:id,libelle,ordre')
            ->orderBy('niveau_id')
            ->orderBy('nom')
            ->get(['id', 'nom', 'niveau_id']);

        return view('finances.impayes.index', compact(
            'annee',
            'rows',
            'totaux',
            'totauxParNiveau',
            'totauxParClasse',
            'niveaux',
            'classes',
            'poste',
            'niveauId',
            'classeId',
            'q'
        ));
    }

    private function buildRow(Inscription $inscription): array
    {
        $montantInscription = max(0, (int) $inscription->montant_inscription);
        $montantNet = max(0, (int) $inscription->montant_net);
        $montantScolarite = max(0, $montantNet - $montantInscription);

        $totalPaye = 0;
        $payeInscriptionDeclare = 0;
        $payeScolariteDeclare = 0;
        $payeInscriptionCible = 0;
        $payeScolariteCible = 0;

        foreach ($inscription->paiements as $paiement) {
            $montant = (int) $paiement->montant;
            $totalPaye += $montant;

            $mi = (int) ($paiement->montant_inscription ?? 0);
            $ms = (int) ($paiement->montant_scolarite ?? 0);
            $payeInscriptionDeclare += $mi;
            $payeScolariteDeclare += $ms;

            if ($mi === 0 && $ms === 0) {
                if ($paiement->poste_cible === 'inscription') {
                    $payeInscriptionCible += $montant;
                } elseif ($paiement->poste_cible === 'scolarite') {
                    $payeScolariteCible += $montant;
                }
            }
        }

        $payeInscriptionConnu = min($montantInscription, $payeInscriptionDeclare + $payeInscriptionCible);
        $payeScolariteConnu = min($montantScolarite, $payeScolariteDeclare + $payeScolariteCible);
        $resteTotal = max(0, $montantNet - $totalPaye);

        $payeNonAffecte = max(0, $totalPaye - $payeInscriptionConnu - $payeScolariteConnu);
        $resteInscriptionAvantNonAffecte = max(0, $montantInscription - $payeInscriptionConnu);
        $affecteInscription = min($resteInscriptionAvantNonAffecte, $payeNonAffecte);
        $resteInscription = max(0, $resteInscriptionAvantNonAffecte - $affecteInscription);

        $payeNonAffecte -= $affecteInscription;
        $resteScolariteAvantNonAffecte = max(0, $montantScolarite - $payeScolariteConnu);
        $resteScolarite = max(0, $resteScolariteAvantNonAffecte - $payeNonAffecte);

        // Sécurité de cohérence : la somme par poste ne doit pas dépasser le reste global.
        $sommePostes = $resteInscription + $resteScolarite;
        if ($sommePostes > $resteTotal && $sommePostes > 0) {
            $ratio = $resteTotal / $sommePostes;
            $resteInscription = (int) round($resteInscription * $ratio);
            $resteScolarite = max(0, $resteTotal - $resteInscription);
        }

        $classe = $inscription->classe;
        $niveau = $classe?->niveau;
        $eleve = $inscription->eleve;

        return [
            'inscription_id' => $inscription->id,
            'eleve_id' => $eleve?->id,
            'matricule' => $eleve?->matricule_interne ?: $eleve?->matricule_desps ?: '—',
            'eleve_nom' => trim(($eleve?->nom ?? '').' '.($eleve?->prenom ?? '')) ?: '—',
            'telephone' => $eleve?->contact_urgence_tel,
            'niveau_libelle' => $niveau?->libelle ?? 'Sans niveau',
            'niveau_ordre' => (int) ($niveau?->ordre ?? 9999),
            'classe_nom' => $classe?->nom ?? 'Sans classe',
            'montant_inscription' => $montantInscription,
            'montant_scolarite' => $montantScolarite,
            'montant_net' => $montantNet,
            'total_paye' => min($totalPaye, $montantNet),
            'reste_inscription' => $resteInscription,
            'reste_scolarite' => $resteScolarite,
            'reste_total' => $resteTotal,
            'taux_paiement' => $montantNet > 0 ? round((min($totalPaye, $montantNet) / $montantNet) * 100, 1) : 0,
        ];
    }

    private function aggregate(Collection $rows, string $key): Collection
    {
        return $rows->groupBy($key)
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'effectif' => $items->count(),
                'reste_inscription' => $items->sum('reste_inscription'),
                'reste_scolarite' => $items->sum('reste_scolarite'),
                'reste_total' => $items->sum('reste_total'),
            ])
            ->sortBy('label')
            ->values();
    }
}
