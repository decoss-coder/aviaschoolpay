<?php
// ══════════════════════════════════════════════════════════════
// ROUTES FINANCIÈRES — À AJOUTER dans routes/api.php
// (à l'intérieur du groupe auth:sanctum)
// ══════════════════════════════════════════════════════════════

use App\Http\Controllers\Api\ComptabiliteController;
use App\Http\Controllers\Api\DepenseController;
use App\Http\Controllers\Api\CockpitFinancierController;

// ══════════════════════════════════════
// MODULE 12 — COMPTABILITÉ
// ══════════════════════════════════════
Route::prefix('comptabilite')->group(function () {
    Route::get('plan-comptable', [ComptabiliteController::class, 'planComptable']);
    Route::post('plan-comptable', [ComptabiliteController::class, 'creerCompte']);
    Route::get('journal', [ComptabiliteController::class, 'journal']);
    Route::post('ecritures', [ComptabiliteController::class, 'creerEcriture']);
    Route::get('grand-livre/{compte}', [ComptabiliteController::class, 'grandLivre']);
    Route::get('bilan', [ComptabiliteController::class, 'bilan']);
    Route::get('compte-resultat', [ComptabiliteController::class, 'compteResultat']);
    Route::get('exercices', [ComptabiliteController::class, 'exercices']);
    Route::post('exercices', [ComptabiliteController::class, 'creerExercice']);
    Route::post('exercices/{exercice}/cloturer', [ComptabiliteController::class, 'cloturerExercice']);
});

// ══════════════════════════════════════
// MODULE 13 — DÉPENSES & TRÉSORERIE
// ══════════════════════════════════════
Route::prefix('depenses')->group(function () {
    Route::get('/', [DepenseController::class, 'index']);
    Route::post('/', [DepenseController::class, 'store']);
    Route::get('{depense}', [DepenseController::class, 'show']);
    Route::put('{depense}', [DepenseController::class, 'update']);
    Route::post('{depense}/approuver', [DepenseController::class, 'approuver']);
    Route::post('{depense}/rejeter', [DepenseController::class, 'rejeter']);
    Route::get('stats/par-categorie', [DepenseController::class, 'parCategorie']);
    Route::get('stats/par-mois', [DepenseController::class, 'parMois']);
    Route::get('stats/recurrentes', [DepenseController::class, 'recurrentes']);

    // Catégories
    Route::apiResource('categories', 'App\Http\Controllers\Api\CategorieDepenseController');
});

Route::prefix('tresorerie')->group(function () {
    Route::get('comptes', 'App\Http\Controllers\Api\TresorerieController@comptes');
    Route::post('comptes', 'App\Http\Controllers\Api\TresorerieController@creerCompte');
    Route::get('comptes/{compte}/mouvements', 'App\Http\Controllers\Api\TresorerieController@mouvements');
    Route::get('soldes', 'App\Http\Controllers\Api\TresorerieController@soldesTempsReel');
    Route::post('virements', 'App\Http\Controllers\Api\TresorerieController@virement');
    Route::get('flux', 'App\Http\Controllers\Api\TresorerieController@fluxEntreesSorties');
    Route::get('previsions', 'App\Http\Controllers\Api\TresorerieController@previsions');
});

// ══════════════════════════════════════
// MODULE 14 — BUDGET & PILOTAGE
// ══════════════════════════════════════
Route::prefix('budgets')->group(function () {
    Route::get('/', 'App\Http\Controllers\Api\BudgetController@index');
    Route::post('/', 'App\Http\Controllers\Api\BudgetController@store');
    Route::get('{budget}', 'App\Http\Controllers\Api\BudgetController@show');
    Route::put('{budget}', 'App\Http\Controllers\Api\BudgetController@update');
    Route::post('{budget}/valider', 'App\Http\Controllers\Api\BudgetController@valider');
    Route::get('{budget}/suivi', [CockpitFinancierController::class, 'budgetVsReel']);
    Route::post('{budget}/lignes', 'App\Http\Controllers\Api\BudgetController@ajouterLigne');
    Route::get('analyse/masse-salariale', 'App\Http\Controllers\Api\BudgetController@masseSalariale');
});

// ══════════════════════════════════════
// MODULE 15 — RENTABILITÉ & COÛTS
// ══════════════════════════════════════
Route::prefix('rentabilite')->group(function () {
    Route::get('par-classe', [CockpitFinancierController::class, 'rentabiliteParClasse']);
    Route::get('par-service', [CockpitFinancierController::class, 'rentabiliteParService']);
    Route::get('par-eleve', 'App\Http\Controllers\Api\RentabiliteController@parEleve');
    Route::get('seuil', 'App\Http\Controllers\Api\RentabiliteController@seuilRentabilite');
    Route::get('centres-profit', 'App\Http\Controllers\Api\RentabiliteController@centresProfit');
    Route::get('couts/par-classe', 'App\Http\Controllers\Api\RentabiliteController@coutsParClasse');
    Route::get('couts/par-enseignant', 'App\Http\Controllers\Api\RentabiliteController@coutsParEnseignant');
    Route::get('marges', 'App\Http\Controllers\Api\RentabiliteController@analyseMarge');
});

// ══════════════════════════════════════
// MODULE 16 — SIMULATION FINANCIÈRE
// ══════════════════════════════════════
Route::prefix('simulations')->group(function () {
    Route::get('/', [CockpitFinancierController::class, 'simulations']);
    Route::post('/', [CockpitFinancierController::class, 'simuler']);
    Route::get('{simulation}', 'App\Http\Controllers\Api\SimulationController@show');
    Route::delete('{simulation}', 'App\Http\Controllers\Api\SimulationController@destroy');
    Route::get('projections/6-mois', 'App\Http\Controllers\Api\SimulationController@projections6Mois');
    Route::get('projections/annee', 'App\Http\Controllers\Api\SimulationController@projectionsAnnee');
});

// ══════════════════════════════════════
// MODULE 17 — COCKPIT DIRIGEANT IA
// ══════════════════════════════════════
Route::prefix('cockpit')->group(function () {
    Route::get('/', [CockpitFinancierController::class, 'cockpit360']);
    Route::get('score-sante', [CockpitFinancierController::class, 'scoreSante']);
    Route::get('alertes', [CockpitFinancierController::class, 'alertes']);
    Route::post('alertes/{alerte}/traiter', [CockpitFinancierController::class, 'traiterAlerte']);
    Route::get('snapshots', 'App\Http\Controllers\Api\CockpitController@historiqueSnapshots');
    Route::get('tendances', 'App\Http\Controllers\Api\CockpitController@tendances');
    Route::post('diagnostic-ia', 'App\Http\Controllers\Api\CockpitController@diagnosticIA');
});
