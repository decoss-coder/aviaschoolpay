<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use Illuminate\Http\Request;

/**
 * Gestion des disciplines (matières racines) de l'établissement.
 * Les sous-disciplines (CF/OG/EO, etc.) sont gérées par SousDisciplinesController.
 */
class DisciplinesController extends Controller
{
    private function etabId(Request $request): int
    {
        return (int) $request->user()->etablissement_id;
    }

    private function normalizeHeuresHebdo(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    public function index(Request $request)
    {
        $etabId = $this->etabId($request);

        $matieres = Matiere::where('etablissement_id', $etabId)
            ->whereNull('parent_matiere_id')
            ->withCount(['sousDisciplines as sous_count', 'affectations as affectations_count'])
            ->orderBy('ordre')->orderBy('nom')
            ->get();

        $stats = [
            'total'    => $matieres->count(),
            'actives'  => $matieres->where('active', true)->count(),
            'avec_sous' => $matieres->where('sous_count', '>', 0)->count(),
            'affectees' => $matieres->where('affectations_count', '>', 0)->count(),
        ];

        return view('admin.rh.disciplines.index', compact('matieres', 'stats'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'heures_hebdo_defaut' => $request->filled('heures_hebdo_defaut')
                ? str_replace(',', '.', $request->input('heures_hebdo_defaut'))
                : null,
        ]);

        $data = $request->validate([
            'code'                 => 'required|string|max:20',
            'nom'                  => 'required|string|max:100',
            'coefficient_defaut'   => 'required|numeric|min:0.1|max:20',
            'heures_hebdo_defaut'  => 'nullable|numeric|min:0|max:60',
            'groupe'               => 'nullable|string|in:Littéraire,Scientifique,Artistique,Sportive,Autres',
            'ordre'                => 'nullable|integer|min:0',
        ]);

        $etabId = $this->etabId($request);

        // Unicité du code dans l'établissement
        if (Matiere::where('etablissement_id', $etabId)->where('code', strtoupper($data['code']))->exists()) {
            return back()->withErrors(['code' => 'Ce code existe déjà pour une autre matière.'])->withInput();
        }

        Matiere::create([
            'etablissement_id'      => $etabId,
            'parent_matiere_id'     => null,
            'code'                  => strtoupper($data['code']),
            'nom'                   => $data['nom'],
            'coefficient_defaut'    => $data['coefficient_defaut'],
            'heures_hebdo_defaut'   => $this->normalizeHeuresHebdo($data['heures_hebdo_defaut'] ?? null),
            'poids_dans_parent'     => 1,
            'groupe'                => $data['groupe'] ?? null,
            'ordre'                 => $data['ordre'] ?? 0,
            'active'                => true,
        ]);

        return back()->with('success', "Discipline « {$data['nom']} » créée.");
    }

    public function update(Request $request, Matiere $matiere)
    {
        abort_unless($matiere->etablissement_id === $this->etabId($request), 404);
        abort_if($matiere->parent_matiere_id, 422, 'Cette matière est une sous-discipline (gérée séparément).');

        $request->merge([
            'heures_hebdo_defaut' => $request->filled('heures_hebdo_defaut')
                ? str_replace(',', '.', $request->input('heures_hebdo_defaut'))
                : null,
        ]);

        $data = $request->validate([
            'code'                 => 'required|string|max:20',
            'nom'                  => 'required|string|max:100',
            'coefficient_defaut'   => 'required|numeric|min:0.1|max:20',
            'heures_hebdo_defaut'  => 'nullable|numeric|min:0|max:60',
            'groupe'               => 'nullable|string|in:Littéraire,Scientifique,Artistique,Sportive,Autres',
            'ordre'                => 'nullable|integer|min:0',
            'active'               => 'nullable|boolean',
        ]);

        // Unicité du code (sauf elle-même)
        $exists = Matiere::where('etablissement_id', $matiere->etablissement_id)
            ->where('code', strtoupper($data['code']))
            ->where('id', '!=', $matiere->id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Ce code existe déjà pour une autre matière.'])->withInput();
        }

        $matiere->update([
            'code'                  => strtoupper($data['code']),
            'nom'                   => $data['nom'],
            'coefficient_defaut'    => $data['coefficient_defaut'],
            'heures_hebdo_defaut'   => $this->normalizeHeuresHebdo($data['heures_hebdo_defaut'] ?? null),
            'groupe'                => $data['groupe'] ?? null,
            'ordre'                 => $data['ordre'] ?? 0,
            'active'                => (bool) ($data['active'] ?? $matiere->active),
        ]);

        return back()->with('success', 'Discipline mise à jour.');
    }

    public function destroy(Request $request, Matiere $matiere)
    {
        abort_unless($matiere->etablissement_id === $this->etabId($request), 404);
        abort_if($matiere->parent_matiere_id, 422, 'Cette matière est une sous-discipline (gérée séparément).');

        // Si la matière est affectée ou utilisée → désactivation seulement
        $hasAffectations = $matiere->affectations()->exists();
        $hasEvaluations  = $matiere->evaluations()->exists();
        $hasMoyennes     = $matiere->moyennesMatieres()->exists();
        $hasSous         = $matiere->sousDisciplines()->exists();

        if ($hasAffectations || $hasEvaluations || $hasMoyennes || $hasSous) {
            $matiere->update(['active' => false]);

            // Désactiver aussi les sous-disciplines
            if ($hasSous) {
                $matiere->sousDisciplines()->update(['active' => false]);
            }

            return back()->with('success', 'Discipline désactivée (données existantes préservées).');
        }

        $matiere->delete();
        return back()->with('success', 'Discipline supprimée.');
    }

    public function toggle(Request $request, Matiere $matiere)
    {
        abort_unless($matiere->etablissement_id === $this->etabId($request), 404);
        abort_if($matiere->parent_matiere_id, 422);

        $matiere->update(['active' => ! $matiere->active]);

        return back()->with('success', $matiere->active ? 'Discipline activée.' : 'Discipline désactivée.');
    }
}
