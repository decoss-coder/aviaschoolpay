<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalleWebController extends Controller
{
    public function index(Request $request)
    {
        $etabId = (int) $request->user()->etablissement_id;

        $salles = Salle::query()
            ->where('etablissement_id', $etabId)
            ->withCount(['emploiDuTemps', 'pointages'])
            ->orderBy('batiment')
            ->orderBy('nom')
            ->get();

        $stats = [
            'total' => $salles->count(),
            'actives' => $salles->where('active', true)->count(),
            'capacite' => $salles->sum('capacite'),
            'utilisees_edt' => $salles->where('emploi_du_temps_count', '>', 0)->count(),
        ];

        return view('emploi-du-temps.salles.index', compact('salles', 'stats'));
    }

    public function store(Request $request)
    {
        $etabId = (int) $request->user()->etablissement_id;
        $data = $this->validated($request);

        Salle::create([
            'etablissement_id' => $etabId,
            'nom' => trim($data['nom']),
            'batiment' => $this->nullableText($data['batiment'] ?? null),
            'capacite' => (int) ($data['capacite'] ?? 0),
            'type' => $data['type'],
            'active' => true,
        ]);

        return back()->with('success', 'Salle ajoutée avec succès.');
    }

    public function update(Request $request, Salle $salle)
    {
        $this->authorizeEtab($request, $salle);
        $data = $this->validated($request, $salle);

        $salle->update([
            'nom' => trim($data['nom']),
            'batiment' => $this->nullableText($data['batiment'] ?? null),
            'capacite' => (int) ($data['capacite'] ?? 0),
            'type' => $data['type'],
            'active' => $request->boolean('active', $salle->active),
        ]);

        return back()->with('success', 'Salle mise à jour.');
    }

    public function toggle(Request $request, Salle $salle)
    {
        $this->authorizeEtab($request, $salle);

        $salle->update(['active' => ! $salle->active]);

        return back()->with('success', $salle->active ? 'Salle activée.' : 'Salle désactivée.');
    }

    public function destroy(Request $request, Salle $salle)
    {
        $this->authorizeEtab($request, $salle);

        if ($salle->emploiDuTemps()->exists() || $salle->pointages()->exists()) {
            $salle->update(['active' => false]);
            return back()->with('success', 'Salle désactivée, car elle est déjà utilisée dans l’emploi du temps ou le pointage.');
        }

        $salle->delete();

        return back()->with('success', 'Salle supprimée.');
    }

    private function validated(Request $request, ?Salle $salle = null): array
    {
        $etabId = (int) $request->user()->etablissement_id;

        return $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('salles', 'nom')
                    ->where(fn ($q) => $q->where('etablissement_id', $etabId))
                    ->ignore($salle?->id),
            ],
            'batiment' => ['nullable', 'string', 'max:100'],
            'capacite' => ['nullable', 'integer', 'min:0', 'max:500'],
            'type' => ['required', Rule::in(['classe', 'laboratoire', 'informatique', 'bibliotheque', 'bureau', 'autre'])],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeEtab(Request $request, Salle $salle): void
    {
        abort_if((int) $salle->etablissement_id !== (int) $request->user()->etablissement_id, 403);
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
