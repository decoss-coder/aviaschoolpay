<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Niveau;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Finance\PaiementService;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;

class FinancePointPostesController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        abort_unless($annee, 422, 'Aucune année scolaire en cours.');

        $filters = [
            'statut_eleve' => strtoupper(trim((string) $request->input('statut_eleve', ''))),
            'niveau_id' => $request->input('niveau_id'),
            'classe_id' => $request->input('classe_id'),
            'q' => trim((string) $request->input('q', '')),
        ];

        $niveaux = Niveau::where('etablissement_id', $etab->id)->orderBy('ordre')->orderBy('libelle')->get();
        $classes = Classe::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau')
            ->orderBy('niveau_id')
            ->orderBy('nom')
            ->get();

        $query = Eleve::query()
            ->where('etablissement_id', $etab->id)
            ->where('actif', true)
            ->whereIn('statut_eleve', ['AFF', 'NAFF'])
            ->with([
                'classe.niveau',
                'inscriptionEnCours.classe.niveau',
                'paiements' => fn ($q) => $q->where('statut', 'confirme'),
            ]);

        if (in_array($filters['statut_eleve'], ['AFF', 'NAFF'], true)) {
            $query->where('statut_eleve', $filters['statut_eleve']);
        }

        if ($filters['classe_id']) {
            $query->where('classe_id', (int) $filters['classe_id']);
        }

        if ($filters['niveau_id']) {
            $query->whereHas('classe', fn ($q) => $q->where('niveau_id', (int) $filters['niveau_id']));
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('prenom', 'like', "%{$q}%")
                    ->orWhere('matricule_interne', 'like', "%{$q}%")
                    ->orWhere('matricule_desps', 'like', "%{$q}%");
            });
        }

        $lignes = $query->get()->map(function (Eleve $eleve) use ($annee) {
            $resume = EleveScolariteService::resumePourEleve($eleve, $annee->id);
            $grille = PaiementService::grilleDepuisResume($resume);
            $classe = $eleve->classe ?? $eleve->inscriptionEnCours?->classe;

            return [
                'eleve' => $eleve,
                'classe' => $classe,
                'niveau' => $classe?->niveau,
                'grille' => $grille,
                'statut_label' => $resume['statut_eleve_libelle'] ?? $eleve->statut_eleve,
            ];
        })->sortBy([
            fn ($a, $b) => ($a['niveau']?->ordre ?? 999) <=> ($b['niveau']?->ordre ?? 999),
            fn ($a, $b) => strcmp($a['classe']?->nom ?? '', $b['classe']?->nom ?? ''),
            fn ($a, $b) => strcmp($a['eleve']->nom ?? '', $b['eleve']->nom ?? ''),
        ])->values();

        $totaux = $this->totaux($lignes);

        $parNiveau = $lignes->groupBy(fn ($row) => $row['niveau']?->libelle ?? 'Sans niveau')
            ->map(fn ($rows) => $this->totaux($rows));

        $parClasse = $lignes->groupBy(fn ($row) => $row['classe']?->nom ?? 'Sans classe')
            ->map(function ($rows) {
                $data = $this->totaux($rows);
                $data['niveau'] = $rows->first()['niveau']?->libelle ?? '—';
                return $data;
            });

        return view('finances.point-postes', compact('etab', 'annee', 'niveaux', 'classes', 'filters', 'lignes', 'totaux', 'parNiveau', 'parClasse'));
    }

    private function totaux($rows): array
    {
        return [
            'effectif' => $rows->count(),
            'inscription_total' => $rows->sum(fn ($r) => (int) $r['grille']['inscription']['montant']),
            'inscription_paye' => $rows->sum(fn ($r) => (int) $r['grille']['inscription']['paye']),
            'inscription_reste' => $rows->sum(fn ($r) => (int) $r['grille']['inscription']['reste']),
            'scolarite_total' => $rows->sum(fn ($r) => (int) $r['grille']['scolarite']['montant']),
            'scolarite_paye' => $rows->sum(fn ($r) => (int) $r['grille']['scolarite']['paye']),
            'scolarite_reste' => $rows->sum(fn ($r) => (int) $r['grille']['scolarite']['reste']),
            'total_du' => $rows->sum(fn ($r) => (int) $r['grille']['total']['montant']),
            'total_paye' => $rows->sum(fn ($r) => (int) $r['grille']['total']['paye']),
            'total_reste' => $rows->sum(fn ($r) => (int) $r['grille']['total']['reste']),
        ];
    }
}
