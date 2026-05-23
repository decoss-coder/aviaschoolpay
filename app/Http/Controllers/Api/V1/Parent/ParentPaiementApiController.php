<?php

namespace App\Http\Controllers\Api\V1\Parent;

use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paiement mobile PayDunya — réservé aux parents, inscription liée à un enfant du parent.
 */
class ParentPaiementApiController extends Controller
{
    public function initierMobile(Request $request, PaiementController $paiement): JsonResponse
    {
        $parent = $request->user()->parentTuteur;
        abort_if(!$parent, 403);

        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'montant' => 'required|integer|min:100',
            'mode' => 'required|in:orange_money,mtn_money,moov_money,wave,carte_bancaire',
        ]);

        $inscription = Inscription::with('eleve')->findOrFail($request->inscription_id);
        abort_unless(
            $parent->eleves()->where('eleve_id', $inscription->eleve_id)->exists(),
            403,
            'Cette inscription ne correspond pas à vos enfants.'
        );

        return $paiement->initierPaiementMobileForParent($request, $inscription);
    }
}
