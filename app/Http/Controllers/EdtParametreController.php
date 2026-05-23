<?php

namespace App\Http\Controllers;

use App\Http\Requests\EdtParametreRequest;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EdtClassePlageHoraire;
use App\Models\EdtParametre;
use App\Models\EdtPolicy;
use App\Models\Salle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EdtParametreController extends Controller
{
    public function edit(Request $request): View
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = $request->integer('annee_scolaire_id');

        $annees = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->orderByDesc('date_debut')
            ->get();

        $anneeActive = $annees->firstWhere('id', $anneeId) ?? $annees->first();

        $parametre = EdtParametre::query()
            ->where('etablissement_id', $etabId)
            ->where('annee_scolaire_id', $anneeActive?->id)
            ->first();

        if (!$parametre) {
            $parametre = new EdtParametre([
                'mode_generation_defaut' => 'prive_equilibre',
                'jours_autorises_json' => ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'],
                'attendre_horaires_vacataires' => true,
                'bloquer_si_vacataire_sans_horaire' => true,
                'respecter_imports_vacataires' => true,
                'regrouper_heures_vacataires' => true,
                'autoriser_reduction_heures' => false,
                'max_reduction_minutes_par_classe' => 0,
                'max_reduction_minutes_par_matiere' => 0,
                'autoriser_matieres_facultatives' => true,
                'prioriser_classes_examen' => false,
                'prioriser_permanents' => true,
                'equilibrer_journees_classes' => true,
                'equilibrer_journees_profs' => true,
                'respecter_tp_consecutifs' => true,
                'eviter_eps_heures_chaudes' => true,
                'limiter_niveaux_prof' => true,
                'max_niveaux_par_prof' => 3,
                'limiter_heures_creuses' => true,
                'max_heures_creuses_prof' => 2,
                'autoriser_trous' => false,
                'tolerer_surcharge_legere' => false,
                'activer_apprentissage_ajustements' => true,
                'verrouiller_ajustements_manuels_par_defaut' => true,
                'actif' => true,
            ]);
        }

        return view('emploi-du-temps.parametres.edit', [
            'annees' => $annees,
            'anneeActive' => $anneeActive,
            'parametre' => $parametre,
            'policies' => class_exists(EdtPolicy::class)
                ? EdtPolicy::query()
                    ->where('etablissement_id', $etabId)
                    ->where('actif', true)
                    ->orderBy('nom')
                    ->get()
                : collect(),
            'creneaux' => Creneau::query()
                ->where('etablissement_id', $etabId)
                ->orderBy('ordre')
                ->get(),
            'salles' => Salle::query()
                ->where('etablissement_id', $etabId)
                ->where('active', true)
                ->orderBy('nom')
                ->get(),
            'jours' => ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
        ]);
    }

    public function update(EdtParametreRequest $request): RedirectResponse
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = $request->input('annee_scolaire_id');

        $payload = $request->validated();

        foreach ([
            'attendre_horaires_vacataires',
            'bloquer_si_vacataire_sans_horaire',
            'respecter_imports_vacataires',
            'regrouper_heures_vacataires',
            'autoriser_reduction_heures',
            'autoriser_matieres_facultatives',
            'prioriser_classes_examen',
            'prioriser_permanents',
            'equilibrer_journees_classes',
            'equilibrer_journees_profs',
            'respecter_tp_consecutifs',
            'eviter_eps_heures_chaudes',
            'limiter_niveaux_prof',
            'limiter_heures_creuses',
            'autoriser_trous',
            'tolerer_surcharge_legere',
            'activer_apprentissage_ajustements',
            'verrouiller_ajustements_manuels_par_defaut',
            'actif',
        ] as $boolField) {
            $payload[$boolField] = (bool) ($payload[$boolField] ?? false);
        }

        $payload['etablissement_id'] = $etabId;
        $payload['updated_by'] = $request->user()->id;

        $parametre = EdtParametre::query()->updateOrCreate(
            [
                'etablissement_id' => $etabId,
                'annee_scolaire_id' => $anneeId,
            ],
            $payload + [
                'created_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('emploi-du-temps.parametres.edit', ['annee_scolaire_id' => $parametre->annee_scolaire_id])
            ->with('success', 'Paramètres IA enregistrés avec succès.');
    }

    // ── Plages horaires par classe ─────────────────────────────────────

    public function editPlages(Request $request): View
    {
        $etabId  = $request->user()->etablissement_id;
        $anneeId = $request->integer('annee_scolaire_id');

        $annees = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->orderByDesc('date_debut')
            ->get();
        $anneeActive = $annees->firstWhere('id', $anneeId) ?? $annees->first();

        $classes = Classe::query()
            ->where('etablissement_id', $etabId)
            ->where('active', true)
            ->with('niveau')
            ->orderBy('nom')
            ->get();

        // Charger les restrictions existantes
        $restrictions = EdtClassePlageHoraire::query()
            ->whereIn('classe_id', $classes->pluck('id'))
            ->where(function ($q) use ($anneeActive) {
                $q->whereNull('annee_scolaire_id');
                if ($anneeActive) {
                    $q->orWhere('annee_scolaire_id', $anneeActive->id);
                }
            })
            ->get()
            ->groupBy('classe_id');

        // Convertir en mode lisible pour le formulaire
        $modesActuels = [];
        $joursActuels = [];
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];

        foreach ($classes as $classe) {
            $regsClasse = $restrictions->get($classe->id, collect());

            // Mode global (jour = null)
            $global = $regsClasse->filter(fn ($r) => is_null($r->jour));
            $blocMatin   = $global->where('plage', 'matin')->where('autorise', false)->isNotEmpty();
            $blocApm     = $global->where('plage', 'apres_midi')->where('autorise', false)->isNotEmpty();

            $modesActuels[$classe->id] = match (true) {
                $blocMatin && $blocApm => 'aucun',
                $blocApm              => 'matin',
                $blocMatin            => 'apres_midi',
                default               => 'libre',
            };

            // Overrides par jour
            foreach ($jours as $jour) {
                $regsJour = $regsClasse->filter(fn ($r) => $r->jour === $jour);
                $jBlocMatin = $regsJour->where('plage', 'matin')->where('autorise', false)->isNotEmpty();
                $jBlocApm   = $regsJour->where('plage', 'apres_midi')->where('autorise', false)->isNotEmpty();
                $jForceMatin = $regsJour->where('plage', 'matin')->where('autorise', true)->isNotEmpty();
                $jForceApm   = $regsJour->where('plage', 'apres_midi')->where('autorise', true)->isNotEmpty();

                $joursActuels[$classe->id][$jour] = match (true) {
                    $jBlocMatin && $jBlocApm   => 'aucun',
                    $jBlocApm                  => 'matin',
                    $jBlocMatin                => 'apres_midi',
                    $jForceMatin && $jForceApm => 'libre',
                    default                    => 'defaut',
                };
            }
        }

        return view('emploi-du-temps.parametres.plages', [
            'annees'      => $annees,
            'anneeActive' => $anneeActive,
            'classes'     => $classes->groupBy(fn ($c) => $c->niveau?->libelle ?? 'Sans niveau'),
            'jours'       => $jours,
            'modesActuels' => $modesActuels,
            'joursActuels' => $joursActuels,
        ]);
    }

    public function updatePlages(Request $request): RedirectResponse
    {
        $etabId  = $request->user()->etablissement_id;
        $anneeId = $request->integer('annee_scolaire_id') ?: null;

        $classeIds = Classe::where('etablissement_id', $etabId)
            ->where('active', true)
            ->pluck('id');

        $plages = $request->input('plages', []);
        $jours  = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];

        DB::transaction(function () use ($classeIds, $plages, $jours, $etabId, $anneeId) {
            // Supprimer toutes les restrictions existantes pour cet étab/année
            EdtClassePlageHoraire::query()
                ->whereIn('classe_id', $classeIds)
                ->where(function ($q) use ($anneeId) {
                    $q->whereNull('annee_scolaire_id');
                    if ($anneeId) {
                        $q->orWhere('annee_scolaire_id', $anneeId);
                    }
                })
                ->delete();

            foreach ($plages as $classeId => $config) {
                if (!$classeIds->contains((int) $classeId)) {
                    continue;
                }

                $base = [
                    'etablissement_id' => $etabId,
                    'annee_scolaire_id' => $anneeId,
                    'classe_id' => (int) $classeId,
                ];

                // Mode global (tous les jours)
                $mode = $config['defaut'] ?? 'libre';
                foreach ($this->modeToRecords($mode) as [$plage, $autorise]) {
                    EdtClassePlageHoraire::create($base + [
                        'jour' => null, 'plage' => $plage, 'autorise' => $autorise,
                    ]);
                }

                // Overrides par jour
                foreach ($jours as $jour) {
                    $jourMode = $config['jours'][$jour] ?? 'defaut';
                    if ($jourMode === 'defaut') {
                        continue;
                    }
                    foreach ($this->modeToRecords($jourMode) as [$plage, $autorise]) {
                        EdtClassePlageHoraire::create($base + [
                            'jour' => $jour, 'plage' => $plage, 'autorise' => $autorise,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('emploi-du-temps.parametres.plages', ['annee_scolaire_id' => $anneeId])
            ->with('success', 'Plages horaires enregistrées avec succès.');
    }

    /** Convertit un mode UI en paires [plage, autorise] à insérer. */
    private function modeToRecords(string $mode): array
    {
        return match ($mode) {
            'matin'    => [['apres_midi', false]],
            'apres_midi' => [['matin', false]],
            'aucun'    => [['matin', false], ['apres_midi', false]],
            'libre'    => [['matin', true], ['apres_midi', true]],
            default    => [], // 'defaut' → rien à insérer
        };
    }
}