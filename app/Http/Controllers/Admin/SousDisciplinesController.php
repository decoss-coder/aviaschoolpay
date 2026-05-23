<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use Illuminate\Http\Request;

/**
 * Gestion des matières avec sous-disciplines (système ivoirien).
 *
 * Exemple : Français au premier cycle a 3 sous-disciplines :
 *   FR (matière principale)
 *     ├── CF  (Composition française, poids 3)
 *     ├── OG  (Orthographe & grammaire,  poids 1)
 *     └── EO  (Expression orale,          poids 1)
 *
 * Chaque sous-discipline a sa propre moyenne, calculées séparément.
 * La moyenne FR = Σ(moy_sous × poids_sous) / Σ(poids_sous)
 */
class SousDisciplinesController extends Controller
{
    private function etabId(Request $request): int
    {
        return (int) $request->user()->etablissement_id;
    }

    public function index(Request $request)
    {
        $etabId = $this->etabId($request);

        // Matières racines (pas de parent)
        $matieres = Matiere::where('etablissement_id', $etabId)
            ->whereNull('parent_matiere_id')
            ->where('active', true)
            ->with(['sousDisciplines' => fn ($q) => $q->where('active', true)])
            ->orderBy('ordre')->orderBy('code')
            ->get();

        return view('admin.rh.sous-disciplines.index', compact('matieres'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_matiere_id' => 'required|exists:matieres,id',
            'code'              => 'required|string|max:20',
            'nom'               => 'required|string|max:100',
            'poids_dans_parent' => 'required|numeric|min:0.1|max:10',
            'ordre'             => 'nullable|integer|min:0',
        ]);

        $etabId = $this->etabId($request);
        $parent = Matiere::where('etablissement_id', $etabId)->findOrFail($data['parent_matiere_id']);

        // Le parent ne doit pas être lui-même une sous-discipline
        abort_if($parent->parent_matiere_id, 422, 'Impossible : la matière parente est déjà une sous-discipline.');

        // Unicité du code dans l'établissement
        if (Matiere::where('etablissement_id', $etabId)->where('code', $data['code'])->exists()) {
            return back()->withErrors(['code' => 'Ce code existe déjà.'])->withInput();
        }

        Matiere::create([
            'etablissement_id'   => $etabId,
            'parent_matiere_id'  => $parent->id,
            'code'               => strtoupper($data['code']),
            'nom'                => $data['nom'],
            'coefficient_defaut' => $parent->coefficient_defaut,
            'poids_dans_parent'  => $data['poids_dans_parent'],
            'ordre'              => $data['ordre'] ?? 0,
            'active'             => true,
        ]);

        return back()->with('success', "Sous-discipline « {$data['code']} » créée.");
    }

    public function update(Request $request, Matiere $matiere)
    {
        abort_unless($matiere->etablissement_id === $this->etabId($request), 404);
        abort_if(!$matiere->parent_matiere_id, 422, 'Ce n\'est pas une sous-discipline.');

        $data = $request->validate([
            'code'              => 'required|string|max:20',
            'nom'               => 'required|string|max:100',
            'poids_dans_parent' => 'required|numeric|min:0.1|max:10',
            'ordre'             => 'nullable|integer|min:0',
        ]);

        $matiere->update([
            'code'              => strtoupper($data['code']),
            'nom'               => $data['nom'],
            'poids_dans_parent' => $data['poids_dans_parent'],
            'ordre'             => $data['ordre'] ?? 0,
        ]);

        return back()->with('success', 'Sous-discipline mise à jour.');
    }

    public function destroy(Request $request, Matiere $matiere)
    {
        abort_unless($matiere->etablissement_id === $this->etabId($request), 404);
        abort_if(!$matiere->parent_matiere_id, 422);

        $matiere->update(['active' => false]); // soft désactivation
        return back()->with('success', 'Sous-discipline désactivée.');
    }

    /**
     * Pré-remplit le standard ivoirien pour Français premier cycle (6e-3e) :
     * CF / OG / EO.
     */
    public function presetFrancais(Request $request)
    {
        $etabId = $this->etabId($request);
        $fr = Matiere::where('etablissement_id', $etabId)
            ->whereIn('code', ['FR', 'FRAN', 'FRA', 'FRANC'])
            ->whereNull('parent_matiere_id')
            ->first();

        if (!$fr) {
            return back()->withErrors(['preset' => 'Matière "Français" (code FR/FRAN/FRA) introuvable. Créez-la d\'abord.']);
        }

        $presets = [
            ['code' => 'CF', 'nom' => 'Composition française',     'poids' => 3, 'ordre' => 1],
            ['code' => 'OG', 'nom' => 'Orthographe et grammaire',  'poids' => 1, 'ordre' => 2],
            ['code' => 'EO', 'nom' => 'Expression orale',          'poids' => 1, 'ordre' => 3],
        ];

        $created = 0;
        foreach ($presets as $p) {
            $exists = Matiere::where('etablissement_id', $etabId)
                ->where('code', $p['code'])->exists();
            if ($exists) continue;
            Matiere::create([
                'etablissement_id'  => $etabId,
                'parent_matiere_id' => $fr->id,
                'code'              => $p['code'],
                'nom'               => $p['nom'],
                'coefficient_defaut'=> $fr->coefficient_defaut,
                'poids_dans_parent' => $p['poids'],
                'ordre'             => $p['ordre'],
                'active'            => true,
            ]);
            $created++;
        }

        return back()->with('success', "{$created} sous-discipline(s) Français créée(s) (CF×3, OG×1, EO×1).");
    }
}
