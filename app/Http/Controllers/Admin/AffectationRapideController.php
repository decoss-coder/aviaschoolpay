<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AffectationRapideController extends Controller
{
    public function create(Request $request)
    {
        $etabId = (int) $request->user()->etablissement_id;
        $this->syncDisciplines($etabId);

        return view('admin.rh.affectations.rapide', [
            'enseignants' => $this->enseignants($etabId),
            'classes' => $this->classes($etabId),
            'matieres' => $this->matieres($etabId),
            'annees' => $this->annees($etabId),
        ]);
    }

    public function store(Request $request)
    {
        $etabId = (int) $request->user()->etablissement_id;
        $this->syncDisciplines($etabId);

        $data = $request->validate([
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'affectations' => ['nullable', 'array'],
        ]);

        abort_unless(
            Enseignant::where('id', $data['enseignant_id'])->where('etablissement_id', $etabId)->where('actif', true)->exists()
            && AnneeScolaire::where('id', $data['annee_scolaire_id'])->where('etablissement_id', $etabId)->exists(),
            403
        );

        $rows = $request->input('affectations', []);
        $created = 0;
        $updated = 0;
        $selected = 0;

        DB::transaction(function () use ($rows, $data, $etabId, &$created, &$updated, &$selected) {
            foreach ($rows as $matiereId => $classes) {
                if (! is_array($classes)) {
                    continue;
                }

                $matiereOk = Matiere::where('id', (int) $matiereId)
                    ->where('etablissement_id', $etabId)
                    ->where('active', true)
                    ->exists();

                if (! $matiereOk) {
                    continue;
                }

                foreach ($classes as $classeId => $payload) {
                    if (empty($payload['selected'])) {
                        continue;
                    }

                    $classeOk = Classe::where('id', (int) $classeId)
                        ->where('etablissement_id', $etabId)
                        ->where('active', true)
                        ->exists();

                    if (! $classeOk) {
                        continue;
                    }

                    $selected++;
                    $volume = (float) str_replace(',', '.', (string) ($payload['volume_horaire_hebdo'] ?? 2));
                    $volume = max(0.5, min(60, $volume));

                    $keys = [
                        'enseignant_id' => (int) $data['enseignant_id'],
                        'classe_id' => (int) $classeId,
                        'matiere_id' => (int) $matiereId,
                        'annee_scolaire_id' => (int) $data['annee_scolaire_id'],
                    ];

                    $exists = Affectation::where($keys)->exists();

                    Affectation::updateOrCreate($keys, [
                        'volume_horaire_hebdo' => $volume,
                        'est_professeur_principal' => ! empty($payload['est_professeur_principal']),
                        'active' => true,
                    ]);

                    $exists ? $updated++ : $created++;
                }
            }
        });

        if ($selected === 0) {
            return back()->withInput()->withErrors([
                'affectations' => 'Veuillez cocher au moins une classe pour une discipline.',
            ]);
        }

        return redirect()
            ->route('admin.rh.affectations.index')
            ->with('success', "Affectation rapide terminée : {$created} créée(s), {$updated} mise(s) à jour.");
    }

    private function enseignants(int $etabId)
    {
        return Enseignant::where('etablissement_id', $etabId)
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    private function classes(int $etabId)
    {
        return Classe::where('etablissement_id', $etabId)
            ->where('active', true)
            ->with('niveau')
            ->orderBy('nom')
            ->get();
    }

    private function matieres(int $etabId)
    {
        return Matiere::where('etablissement_id', $etabId)
            ->where('active', true)
            ->orderBy('nom')
            ->get();
    }

    private function annees(int $etabId)
    {
        return AnneeScolaire::where('etablissement_id', $etabId)
            ->orderByDesc('date_debut')
            ->get();
    }

    private function syncDisciplines(int $etabId): void
    {
        $enseignants = Enseignant::where('etablissement_id', $etabId)
            ->where('actif', true)
            ->whereNotNull('specialite')
            ->where('specialite', '!=', '')
            ->get(['specialite']);

        foreach ($enseignants as $enseignant) {
            foreach ($this->splitDisciplines($enseignant->specialite) as $discipline) {
                $exists = Matiere::where('etablissement_id', $etabId)
                    ->whereRaw('LOWER(nom) = ?', [strtolower($discipline)])
                    ->first();

                if ($exists) {
                    if (! $exists->active) {
                        $exists->update(['active' => true]);
                    }
                    continue;
                }

                Matiere::create([
                    'etablissement_id' => $etabId,
                    'parent_matiere_id' => null,
                    'nom' => $discipline,
                    'code' => $this->codeFor($discipline, $etabId),
                    'coefficient_defaut' => 1,
                    'poids_dans_parent' => 1,
                    'ordre' => 100,
                    'groupe' => 'disciplines_enseignants',
                    'active' => true,
                ]);
            }
        }
    }

    private function splitDisciplines(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique(fn ($item) => strtolower($item))
            ->values()
            ->all();
    }

    private function codeFor(string $name, int $etabId): string
    {
        $base = strtoupper(Str::slug($name, '_')) ?: 'MATIERE';
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
