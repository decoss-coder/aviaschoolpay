<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Niveau;
use App\Services\Finance\TarificationService;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NiveauxController extends Controller
{
    private function etabId(Request $request): int
    {
        return (int) $request->user()->etablissement_id;
    }

    public function index(Request $request)
    {
        $etabId = $this->etabId($request);
        $annee = AnneeScolaireContext::courantePourEtablissement($etabId);

        $niveaux = Niveau::query()
            ->where('etablissement_id', $etabId)
            ->withCount('classes')
            ->orderBy('ordre')
            ->orderBy('code')
            ->get();

        $stats = [
            'total' => $niveaux->count(),
            'actifs' => $niveaux->where('actif', true)->count(),
            'classes' => $niveaux->sum('classes_count'),
            'college' => $niveaux->where('cycle', TarificationService::CYCLE_COLLEGE)->count(),
            'lycee' => $niveaux->where('cycle', TarificationService::CYCLE_LYCEE)->count(),
        ];

        return view('admin.rh.niveaux.index', compact('niveaux', 'stats', 'annee'));
    }

    public function store(Request $request)
    {
        $etabId = $this->etabId($request);
        $data = $this->validated($request);

        Niveau::create([
            'etablissement_id' => $etabId,
            'code' => strtoupper(trim($data['code'])),
            'libelle' => trim($data['libelle']),
            'cycle' => $data['cycle'],
            'ordre' => (int) ($data['ordre'] ?? 0),
            'frais_scolarite_defaut' => (int) ($data['frais_scolarite_defaut'] ?? 0),
            'frais_inscription_defaut' => (int) ($data['frais_inscription_defaut'] ?? 0),
            'frais_reinscription_defaut' => (int) ($data['frais_reinscription_defaut'] ?? 0),
            'actif' => true,
        ]);

        return back()->with('success', 'Niveau créé avec succès.');
    }

    public function update(Request $request, Niveau $niveau)
    {
        abort_unless($niveau->etablissement_id === $this->etabId($request), 404);

        $data = $this->validated($request, $niveau);
        $niveau->update([
            'code' => strtoupper(trim($data['code'])),
            'libelle' => trim($data['libelle']),
            'cycle' => $data['cycle'],
            'ordre' => (int) ($data['ordre'] ?? 0),
            'frais_scolarite_defaut' => (int) ($data['frais_scolarite_defaut'] ?? 0),
            'frais_inscription_defaut' => (int) ($data['frais_inscription_defaut'] ?? 0),
            'frais_reinscription_defaut' => (int) ($data['frais_reinscription_defaut'] ?? 0),
            'actif' => $request->boolean('actif', $niveau->actif),
        ]);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $niveau->etablissement_id);
        if ($annee && $request->boolean('appliquer_classes')) {
            TarificationService::appliquerNiveauSurClasses($niveau->fresh(), (int) $annee->id);
        }

        return back()->with('success', 'Niveau mis à jour avec succès.');
    }

    public function toggle(Request $request, Niveau $niveau)
    {
        abort_unless($niveau->etablissement_id === $this->etabId($request), 404);

        $niveau->update(['actif' => ! $niveau->actif]);

        return back()->with('success', $niveau->actif ? 'Niveau activé.' : 'Niveau désactivé.');
    }

    public function destroy(Request $request, Niveau $niveau)
    {
        abort_unless($niveau->etablissement_id === $this->etabId($request), 404);

        if ($niveau->classes()->exists()) {
            $niveau->update(['actif' => false]);
            return back()->with('success', 'Niveau désactivé, car des classes y sont rattachées.');
        }

        $niveau->delete();
        return back()->with('success', 'Niveau supprimé.');
    }

    private function validated(Request $request, ?Niveau $niveau = null): array
    {
        $etabId = $this->etabId($request);

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('niveaux', 'code')
                    ->where(fn ($q) => $q->where('etablissement_id', $etabId))
                    ->ignore($niveau?->id),
            ],
            'libelle' => ['required', 'string', 'max:100'],
            'cycle' => ['required', Rule::in([TarificationService::CYCLE_COLLEGE, TarificationService::CYCLE_LYCEE])],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:999'],
            'frais_scolarite_defaut' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'frais_inscription_defaut' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'frais_reinscription_defaut' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'actif' => ['nullable', 'boolean'],
            'appliquer_classes' => ['nullable', 'boolean'],
        ]);
    }
}
