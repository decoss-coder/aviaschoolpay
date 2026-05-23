<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieDepense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategorieDepenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = CategorieDepense::where('etablissement_id', $request->user()->etablissement_id)
            ->where('active', true)
            ->orderBy('nom')
            ->get();
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'     => 'required|string|max:150',
            'code'    => 'nullable|string|max:20',
            'type'    => 'required|in:fonctionnement,investissement,personnel,autre',
            'couleur' => 'nullable|string|max:20',
            'compte_comptable_numero' => 'nullable|string|max:20',
        ]);

        $categorie = CategorieDepense::create([
            'etablissement_id' => $request->user()->etablissement_id,
            ...$validated,
            'active' => true,
        ]);

        return response()->json($categorie, 201);
    }

    public function show(Request $request, CategorieDepense $categorieDepense): JsonResponse
    {
        abort_unless($categorieDepense->etablissement_id === $request->user()->etablissement_id, 403);
        return response()->json($categorieDepense);
    }

    public function update(Request $request, CategorieDepense $categorieDepense): JsonResponse
    {
        abort_unless($categorieDepense->etablissement_id === $request->user()->etablissement_id, 403);

        $validated = $request->validate([
            'nom'     => 'sometimes|required|string|max:150',
            'code'    => 'nullable|string|max:20',
            'type'    => 'sometimes|required|in:fonctionnement,investissement,personnel,autre',
            'couleur' => 'nullable|string|max:20',
            'active'  => 'sometimes|boolean',
            'compte_comptable_numero' => 'nullable|string|max:20',
        ]);

        $categorieDepense->update($validated);
        return response()->json($categorieDepense);
    }

    public function destroy(Request $request, CategorieDepense $categorieDepense): JsonResponse
    {
        abort_unless($categorieDepense->etablissement_id === $request->user()->etablissement_id, 403);
        $categorieDepense->update(['active' => false]);
        return response()->json(['message' => 'Catégorie désactivée.']);
    }
}
