<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\EdtGenerationRun;
use App\Services\Edt\EdtParametreResolver;
use App\Services\Edt\UnifiedEdtGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmploiDuTempsAIAssistantController extends Controller
{
    public function __construct(
        private readonly EdtParametreResolver $parametreResolver,
        private readonly UnifiedEdtGenerationService $generationService,
    ) {
    }

    public function index(Request $request): View
    {
        $etabId = $request->user()->etablissement_id;
        $anneeId = $request->integer('annee_scolaire_id');

        $annees = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->orderByDesc('date_debut')
            ->get();

        $anneeActive = $annees->firstWhere('id', $anneeId) ?? $annees->first();

        $parametres = $this->parametreResolver->resolve(
            $etabId,
            $anneeActive?->id
        );

        $lastRun = EdtGenerationRun::query()
            ->where('etablissement_id', $etabId)
            ->when($anneeActive?->id, fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id))
            ->latest('id')
            ->first();

        $classes = Classe::query()
            ->where('etablissement_id', $etabId)
            ->where('active', true)
            ->orderBy('nom')
            ->get();

        return view('emploi-du-temps.ai.index', [
            'annees' => $annees,
            'anneeActive' => $anneeActive,
            'parametres' => $parametres,
            'lastRun' => $lastRun,
            'classes' => $classes,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $etabId = $user->etablissement_id;

        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'portee' => ['required', 'in:globale,classes_selectionnees'],
            'scope_classes' => ['nullable', 'array'],
            'scope_classes.*' => ['integer', 'exists:classes,id'],
            'apply_immediately' => ['nullable', 'boolean'],
            'force_generate_without_vacataires' => ['nullable', 'boolean'],
            'action_mode' => ['nullable', 'in:preview,apply'],
        ]);

        $validated['apply_immediately'] = ($validated['action_mode'] ?? 'apply') === 'apply';
        $validated['force_generate_without_vacataires'] = (bool) ($validated['force_generate_without_vacataires'] ?? false);

        if (($validated['portee'] ?? 'globale') !== 'classes_selectionnees') {
            $validated['scope_classes'] = [];
        } else {
            $validated['scope_classes'] = collect($validated['scope_classes'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (empty($validated['scope_classes'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'scope_classes' => 'Veuillez sélectionner au moins une classe pour une génération ciblée.',
                    ]);
            }

            $allowedClassIds = Classe::query()
                ->where('etablissement_id', $etabId)
                ->whereIn('id', $validated['scope_classes'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $invalidClassIds = array_values(array_diff($validated['scope_classes'], $allowedClassIds));

            if (!empty($invalidClassIds)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'scope_classes' => 'Certaines classes sélectionnées ne sont pas valides pour cet établissement.',
                    ]);
            }

            $validated['scope_classes'] = $allowedClassIds;
        }

        // Génération IA = combinaisons N×M×K (affectations × créneaux × salles).
        // Avec 320 affectations / 24 classes / 23 créneaux, l'algo peut dépasser
        // la limite PHP par défaut (30s). On la lève le temps de la requête,
        // et on augmente la mémoire de travail pour les états intermédiaires.
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        try {
            $result = $this->generationService->generateForCreateScreen(
                user: $user,
                payload: $validated
            );
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'La génération IA a échoué : ' . $e->getMessage());
        }

        /** @var \App\Models\EdtGenerationRun $run */
        $run = $result['run'];
        $applied = (bool) ($result['applied'] ?? false);
        $appliedCount = (int) ($result['applied_count'] ?? 0);
        $warning = $result['vacataire_warning'] ?? null;

        if ($applied) {
            return redirect()
                ->route('emploi-du-temps.grille', [
                    'annee_scolaire_id' => $run->annee_scolaire_id,
                ])
                ->with('success', "Proposition IA appliquée à la grille. {$appliedCount} créneau(x) enregistré(s).")
                ->with('warning', $warning);
        }

        return redirect()
            ->route('emploi-du-temps.ia.report', $run)
            ->with('success', 'Propositions IA générées. Tu peux les relire, les modifier puis les appliquer.')
            ->with('warning', $warning);
    }

    public function report(Request $request, EdtGenerationRun $run): View
    {
        abort_unless($run->etablissement_id === $request->user()->etablissement_id, 403);

        $run->loadMissing('issues');

        $summary = is_array($run->summary_json ?? null)
            ? ($run->summary_json ?? [])
            : (json_decode($run->summary_json ?? '[]', true) ?: []);

        $proposals = collect($summary['assignments_payload'] ?? [])->values();

        return view('emploi-du-temps.ai.report', [
            'run' => $run,
            'proposals' => $proposals,
        ]);
    }

    public function saveProposals(Request $request, EdtGenerationRun $run): RedirectResponse
    {
        abort_unless($run->etablissement_id === $request->user()->etablissement_id, 403);

        $validated = $request->validate([
            'proposals' => ['required', 'array', 'min:1'],
            'proposals.*.jour' => ['required', 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche'],
            'proposals.*.creneau_id' => ['required', 'integer', 'min:1'],
            'proposals.*.classe_id' => ['required', 'integer', 'min:1'],
            'proposals.*.matiere_id' => ['required', 'integer', 'min:1'],
            'proposals.*.salle_id' => ['required', 'integer', 'min:1'],
            'proposals.*.enseignant_id' => ['nullable', 'integer'],
            'proposals.*.valide_du' => ['nullable', 'date'],
            'proposals.*.valide_au' => ['nullable', 'date'],
            'proposals.*.ia_score' => ['nullable', 'numeric'],
        ]);

        $summary = is_array($run->summary_json ?? null)
            ? ($run->summary_json ?? [])
            : (json_decode($run->summary_json ?? '[]', true) ?: []);

        $summary['assignments_payload'] = array_values($validated['proposals']);
        $summary['assignments_count'] = count($validated['proposals']);
        $summary['proposals_saved_manually'] = true;
        $summary['proposals_saved_at'] = now()->toDateTimeString();

        $run->update([
            'summary_json' => $summary,
        ]);

        return back()->with('success', 'Propositions enregistrées avec succès.');
    }

    public function apply(Request $request, EdtGenerationRun $run): RedirectResponse
    {
        abort_unless($run->etablissement_id === $request->user()->etablissement_id, 403);

        try {
            $count = $this->generationService->applyRun($run, $request->user());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Impossible d’appliquer la proposition IA : ' . $e->getMessage());
        }

        return redirect()
            ->route('emploi-du-temps.grille', [
                'annee_scolaire_id' => $run->annee_scolaire_id,
            ])
            ->with('success', "Proposition IA appliquée à la grille. {$count} créneau(x) enregistré(s).");
    }
}