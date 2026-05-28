<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AffectationRequest;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AffectationAdminController extends Controller
{
    public function index(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $this->synchroniserDisciplinesEnseignantsCommeMatieres($etabId);

        $annees = AnneeScolaire::where('etablissement_id', $etabId)->orderByDesc('date_debut')->get();
        $anneeCourante = $annees->firstWhere('en_cours', true) ?? $annees->first();

        $query = Affectation::query()
            ->with(['enseignant', 'classe.niveau', 'matiere', 'anneeScolaire'])
            ->whereHas('enseignant', fn ($q) => $q->where('etablissement_id', $etabId))
            ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etabId))
            ->whereHas('matiere', fn ($q) => $q->where('etablissement_id', $etabId));

        if ($request->filled('search')) {
            $s = trim((string) $request->search);

            $query->where(function ($q) use ($s) {
                $q->whereHas('enseignant', function ($sub) use ($s) {
                    $sub->where('nom', 'like', "%{$s}%")
                        ->orWhere('prenom', 'like', "%{$s}%");
                })->orWhereHas('classe', function ($sub) use ($s) {
                    $sub->where('nom', 'like', "%{$s}%");
                })->orWhereHas('matiere', function ($sub) use ($s) {
                    $sub->where('nom', 'like', "%{$s}%");
                });
            });
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->integer('annee_scolaire_id'));
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $affectations = $query
            ->orderByDesc('active')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.rh.affectations.index', [
            'affectations' => $affectations,
            'annees' => $annees,
            'anneeCourante' => $anneeCourante,
            'enseignants' => Enseignant::where('etablissement_id', $etabId)->where('actif', true)->orderBy('nom')->orderBy('prenom')->get(),
            'classes' => Classe::where('etablissement_id', $etabId)->where('active', true)->with('niveau')->orderBy('niveau_id')->orderBy('nom')->get(),
            'matieres' => $this->matieresDisponiblesPourAffectation($etabId),
        ]);
    }

    public function create(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $this->synchroniserDisciplinesEnseignantsCommeMatieres($etabId);

        return view('admin.rh.affectations.create', [
            'enseignants' => Enseignant::where('etablissement_id', $etabId)->where('actif', true)->orderBy('nom')->orderBy('prenom')->get(),
            'classes' => Classe::where('etablissement_id', $etabId)->where('active', true)->orderBy('nom')->get(),
            'matieres' => $this->matieresDisponiblesPourAffectation($etabId),
            'annees' => AnneeScolaire::where('etablissement_id', $etabId)->orderByDesc('date_debut')->get(),
        ]);
    }

    public function store(AffectationRequest $request)
    {
        $etabId = $request->user()->etablissement_id;
        $this->synchroniserDisciplinesEnseignantsCommeMatieres($etabId);
        $data = $request->validated();

        $this->assertScopedIdsBelongToEtab($data, $etabId);

        $exists = Affectation::query()
            ->where('enseignant_id', $data['enseignant_id'])
            ->where('classe_id', $data['classe_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->where('annee_scolaire_id', $data['annee_scolaire_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['enseignant_id' => 'Cette affectation existe déjà pour cette année scolaire.']);
        }

        Affectation::create($data);

        return redirect()
            ->route('admin.rh.affectations.index')
            ->with('success', 'Affectation créée avec succès.');
    }

    public function bulkStore(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $this->synchroniserDisciplinesEnseignantsCommeMatieres($etabId);

        $data = $request->validate([
            'enseignant_ids' => ['required', 'array', 'min:1'],
            'enseignant_ids.*' => ['integer', Rule::exists('enseignants', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId)->where('actif', true))],
            'classe_ids' => ['required', 'array', 'min:1'],
            'classe_ids.*' => ['integer', Rule::exists('classes', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId)->where('active', true))],
            'matiere_id' => ['required', 'integer', Rule::exists('matieres', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId)->where('active', true))],
            'annee_scolaire_id' => ['required', 'integer', Rule::exists('annees_scolaires', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId))],
            'volume_horaire_hebdo' => ['required', 'numeric', 'min:0.5', 'max:20'],
            'est_professeur_principal' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ], [
            'enseignant_ids.required' => 'Sélectionne au moins un enseignant.',
            'classe_ids.required' => 'Sélectionne au moins une classe.',
            'matiere_id.required' => 'Sélectionne une matière.',
        ]);

        $enseignantIds = collect($data['enseignant_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $classeIds = collect($data['classe_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $matiereId = (int) $data['matiere_id'];
        $anneeId = (int) $data['annee_scolaire_id'];
        $volume = (float) $data['volume_horaire_hebdo'];
        $isPp = $request->boolean('est_professeur_principal');
        $active = $request->boolean('active', true);

        $stats = DB::transaction(function () use ($enseignantIds, $classeIds, $matiereId, $anneeId, $volume, $isPp, $active) {
            $created = 0;
            $updated = 0;

            foreach ($enseignantIds as $enseignantId) {
                foreach ($classeIds as $classeId) {
                    $keys = [
                        'enseignant_id' => $enseignantId,
                        'classe_id' => $classeId,
                        'matiere_id' => $matiereId,
                        'annee_scolaire_id' => $anneeId,
                    ];

                    $existing = Affectation::where($keys)->first();

                    $payload = [
                        'volume_horaire_hebdo' => $volume,
                        'est_professeur_principal' => $isPp,
                        'active' => $active,
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        Affectation::create($keys + $payload);
                        $created++;
                    }
                }
            }

            return ['created' => $created, 'updated' => $updated];
        });

        return redirect()
            ->route('admin.rh.affectations.index', ['annee_scolaire_id' => $anneeId, 'active' => '1'])
            ->with('success', "Affectation groupée terminée : {$stats['created']} créée(s), {$stats['updated']} mise(s) à jour.");
    }

    public function clear(Request $request)
    {
        $etabId = $request->user()->etablissement_id;

        $data = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', Rule::exists('annees_scolaires', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId))],
            'confirm_clear' => ['required', 'in:VIDER-AFFECTATIONS'],
        ], [
            'confirm_clear.in' => 'Tape exactement VIDER-AFFECTATIONS pour confirmer.',
        ]);

        $query = Affectation::query()
            ->whereHas('enseignant', fn ($q) => $q->where('etablissement_id', $etabId))
            ->whereHas('classe', fn ($q) => $q->where('etablissement_id', $etabId))
            ->whereHas('matiere', fn ($q) => $q->where('etablissement_id', $etabId));

        if (!empty($data['annee_scolaire_id'])) {
            $query->where('annee_scolaire_id', (int) $data['annee_scolaire_id']);
        }

        $count = $query->count();
        $query->delete();

        return redirect()
            ->route('admin.rh.affectations.index')
            ->with('success', "Affectations vidées : {$count} ligne(s) supprimée(s).");
    }

    public function edit(Request $request, Affectation $affectation)
    {
        $etabId = $request->user()->etablissement_id;
        $this->ensureScopedAffectation($affectation, $etabId);
        $this->synchroniserDisciplinesEnseignantsCommeMatieres($etabId);

        return view('admin.rh.affectations.edit', [
            'affectation' => $affectation,
            'enseignants' => Enseignant::where('etablissement_id', $etabId)->where('actif', true)->orderBy('nom')->orderBy('prenom')->get(),
            'classes' => Classe::where('etablissement_id', $etabId)->where('active', true)->orderBy('nom')->get(),
            'matieres' => $this->matieresDisponiblesPourAffectation($etabId),
            'annees' => AnneeScolaire::where('etablissement_id', $etabId)->orderByDesc('date_debut')->get(),
        ]);
    }

    public function update(AffectationRequest $request, Affectation $affectation)
    {
        $etabId = $request->user()->etablissement_id;
        $this->ensureScopedAffectation($affectation, $etabId);
        $this->synchroniserDisciplinesEnseignantsCommeMatieres($etabId);

        $data = $request->validated();
        $this->assertScopedIdsBelongToEtab($data, $etabId);

        $exists = Affectation::query()
            ->where('enseignant_id', $data['enseignant_id'])
            ->where('classe_id', $data['classe_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->where('annee_scolaire_id', $data['annee_scolaire_id'])
            ->whereKeyNot($affectation->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['enseignant_id' => 'Une affectation identique existe déjà.']);
        }

        $affectation->update($data);

        return redirect()
            ->route('admin.rh.affectations.index')
            ->with('success', 'Affectation mise à jour.');
    }

    public function destroy(Request $request, Affectation $affectation)
    {
        $etabId = $request->user()->etablissement_id;
        $this->ensureScopedAffectation($affectation, $etabId);

        $affectation->delete();

        return redirect()
            ->route('admin.rh.affectations.index')
            ->with('success', 'Affectation supprimée.');
    }

    private function ensureScopedAffectation(Affectation $affectation, int $etabId): void
    {
        abort_unless(
            $affectation->enseignant?->etablissement_id === $etabId
            && $affectation->classe?->etablissement_id === $etabId
            && $affectation->matiere?->etablissement_id === $etabId,
            404
        );
    }

    private function assertScopedIdsBelongToEtab(array $data, int $etabId): void
    {
        $enseignantOk = Enseignant::where('id', $data['enseignant_id'])->where('etablissement_id', $etabId)->exists();
        $classeOk = Classe::where('id', $data['classe_id'])->where('etablissement_id', $etabId)->exists();
        $matiereOk = Matiere::where('id', $data['matiere_id'])->where('etablissement_id', $etabId)->exists();
        $anneeOk = AnneeScolaire::where('id', $data['annee_scolaire_id'])->where('etablissement_id', $etabId)->exists();

        abort_unless($enseignantOk && $classeOk && $matiereOk && $anneeOk, 403);
    }

    private function matieresDisponiblesPourAffectation(int $etabId)
    {
        return Matiere::where('etablissement_id', $etabId)
            ->where('active', true)
            ->whereNull('parent_matiere_id')
            ->orderBy('ordre')
            ->orderBy('nom')
            ->get();
    }

    private function synchroniserDisciplinesEnseignantsCommeMatieres(int $etabId): void
    {
        $enseignants = Enseignant::query()
            ->where('etablissement_id', $etabId)
            ->where('actif', true)
            ->whereNotNull('specialite')
            ->where('specialite', '!=', '')
            ->get(['id', 'specialite']);

        foreach ($enseignants as $enseignant) {
            foreach ($this->extraireDisciplines($enseignant->specialite) as $discipline) {
                $matiere = Matiere::query()
                    ->where('etablissement_id', $etabId)
                    ->whereRaw('LOWER(nom) = ?', [strtolower($discipline)])
                    ->first();

                if ($matiere) {
                    if (! $matiere->active) {
                        $matiere->update(['active' => true]);
                    }
                    continue;
                }

                Matiere::create([
                    'etablissement_id' => $etabId,
                    'parent_matiere_id' => null,
                    'nom' => $discipline,
                    'code' => $this->genererCodeMatiere($discipline, $etabId),
                    'coefficient_defaut' => 1,
                    'poids_dans_parent' => 1,
                    'ordre' => 100,
                    'groupe' => 'disciplines_enseignants',
                    'active' => true,
                ]);
            }
        }
    }

    private function extraireDisciplines(?string $value): array
    {
        return collect(preg_split('/[,;\/]+/', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique(fn ($item) => strtolower($item))
            ->values()
            ->all();
    }

    private function genererCodeMatiere(string $discipline, int $etabId): string
    {
        $base = strtoupper(Str::slug($discipline, '_')) ?: 'MATIERE';
        $base = Str::limit($base, 24, '');
        $candidate = $base;
        $counter = 1;

        while (Matiere::where('etablissement_id', $etabId)->where('code', $candidate)->exists()) {
            $candidate = Str::limit($base, 20, '') . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
