<?php

/**
 * API v1 — base mobile (étape 1).
 * Préfixe Laravel : /api + préfixe fichier v1 → URLs /api/v1/...
 */

use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContextController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Director\DirectorDashboardApiController;
use App\Http\Controllers\Api\V1\Director\DirectorElevesApiController;
use App\Http\Controllers\Api\V1\Director\DirectorEnseignantsApiController;
use App\Http\Controllers\Api\V1\Director\DirectorBulletinsApiController;
use App\Http\Controllers\Api\V1\Director\DirectorPointageApiController;
use App\Http\Controllers\Api\V1\Parent\ParentPortalApiController;
use App\Http\Controllers\Api\V1\Student\StudentInscriptionApiController;
use App\Http\Controllers\Api\V1\Student\StudentPortalApiController;
use App\Http\Controllers\Api\V1\Teacher\TeacherClassController;
use App\Http\Controllers\Api\V1\Teacher\TeacherDashboardController;
use App\Http\Controllers\Api\V1\Teacher\TeacherDevoirController;
use App\Http\Controllers\Api\V1\Teacher\TeacherEvaluationController;
use App\Http\Controllers\Api\V1\Teacher\TeacherNoteController;
use App\Http\Controllers\Api\V1\Teacher\TeacherPointageController;
use App\Http\Controllers\Api\V1\Teacher\TeacherPresenceController;
use App\Http\Controllers\Api\V1\Teacher\TeacherGrilleNotesController;
use App\Http\Controllers\Api\V1\Teacher\TeacherMoyennesOcrController;
use App\Http\Controllers\Api\V1\Teacher\TeacherPdfController;
use App\Http\Controllers\Api\V1\Teacher\TeacherScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Webhook PayDunya (format brut conservé pour compatibilité prestataire)
    Route::post('paiements/callback/paydunya', [PaiementController::class, 'callbackPayDunya'])
        ->name('api.paiements.callback.paydunya');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1');

    // Inscription élève (pas d'authentification requise)
    Route::post('auth/eleve/verifier-matricule', [StudentInscriptionApiController::class, 'verifierMatricule'])
        ->middleware('throttle:10,1');
    Route::post('auth/eleve/creer-compte', [StudentInscriptionApiController::class, 'creerCompte'])
        ->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'annee.courante', 'etab.access', 'annee.readonly'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/change-password', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'updateApi']);

        Route::get('me', [MeController::class, 'show']);

        // Téléchargement du sujet d'un devoir (auth interne dans le contrôleur)
        Route::get('devoirs/{devoir}/sujet', [TeacherDevoirController::class, 'downloadSujet']);
        Route::get('evaluations/{evaluation}/sujet', [TeacherEvaluationController::class, 'downloadSujet']);

        Route::get('context/annee-scolaire', [ContextController::class, 'anneeScolaire']);

        Route::put('context/etablissement', [ContextController::class, 'setEtablissement'])
            ->middleware('role:enseignant');

        Route::middleware(['role:enseignant', 'api.teacher-school'])->prefix('teacher')->group(function () {
            Route::get('dashboard', [TeacherDashboardController::class, 'show']);

            Route::get('classes', [TeacherClassController::class, 'index']);
            Route::get('classes/{classe}', [TeacherClassController::class, 'show']);
            Route::get('classes/{classe}/students', [TeacherClassController::class, 'students']);
            Route::get('classes/{classe}/presences', [TeacherPresenceController::class, 'index']);
            Route::get('classes/{classe}/presences/day-creneaux', [TeacherPresenceController::class, 'dayCreneaux']);
            Route::post('classes/{classe}/presences', [TeacherPresenceController::class, 'storeBulk']);
            Route::get('classes/{classe}/feuille-de-note', [TeacherPdfController::class, 'feuilleIndex']);
            Route::get('classes/{classe}/feuille-de-note/pdf', [TeacherPdfController::class, 'feuillePdf']);
            Route::get('classes/{classe}/feuille-de-note/excel', [TeacherPdfController::class, 'feuilleExcel']);

            // Fiche classe (liste élèves PDF + Excel)
            Route::get('classes/{classe}/fiche-classe/pdf', [TeacherPdfController::class, 'ficheClassePdf']);
            Route::get('classes/{classe}/fiche-classe/excel', [TeacherPdfController::class, 'ficheClasseExcel']);

            // Grille de notes (matrice élèves × évaluations)
            Route::get('classes/{classe}/grille-notes', [TeacherGrilleNotesController::class, 'show']);
            Route::post('classes/{classe}/grille-notes/note', [TeacherGrilleNotesController::class, 'saveNote']);
            Route::get('classes/{classe}/grille-notes/pdf', [TeacherGrilleNotesController::class, 'gridPdf']);

            // Moyennes par matière (saisie directe)
            Route::get('classes/{classe}/moyennes', [TeacherGrilleNotesController::class, 'moyennes']);
            Route::post('classes/{classe}/moyennes', [TeacherGrilleNotesController::class, 'saveMoyennes']);
            Route::get('classes/{classe}/moyennes/pdf', [TeacherGrilleNotesController::class, 'moyennesPdf']);

            // OCR import moyennes (photo / image / PDF de fiche)
            Route::post('classes/{classe}/moyennes/ocr-preview', [TeacherMoyennesOcrController::class, 'preview']);
            Route::post('classes/{classe}/moyennes/ocr-confirm', [TeacherMoyennesOcrController::class, 'confirm']);

            Route::get('emploi-du-temps/today', [TeacherScheduleController::class, 'today']);
            Route::get('emploi-du-temps/grid', [TeacherPdfController::class, 'scheduleGrid']);
            Route::get('emploi-du-temps/pdf', [TeacherPdfController::class, 'schedulePdf']);
            Route::get('emploi-du-temps', [TeacherScheduleController::class, 'index']);

            Route::get('pointage/today', [TeacherPointageController::class, 'today']);
            Route::post('pointage/scan-qr', [TeacherPointageController::class, 'scanQr']);
            Route::post('pointage/{pointage}/cahier-texte/differe', [TeacherPointageController::class, 'deferCahier']);
            Route::post('pointage/{pointage}/cahier-texte', [TeacherPointageController::class, 'validateCahierTexte']);

            Route::get('evaluations', [TeacherEvaluationController::class, 'index']);
            Route::post('evaluations', [TeacherEvaluationController::class, 'store']);
            Route::get('evaluations/{evaluation}', [TeacherEvaluationController::class, 'show']);
            Route::put('evaluations/{evaluation}', [TeacherEvaluationController::class, 'update']);
            Route::delete('evaluations/{evaluation}', [TeacherEvaluationController::class, 'destroy']);

            Route::get('evaluations/{evaluation}/notes', [TeacherNoteController::class, 'index']);
            Route::post('evaluations/{evaluation}/notes', [TeacherNoteController::class, 'storeBulk']);
            Route::put('notes/{note}', [TeacherNoteController::class, 'update']);

            Route::get('devoirs', [TeacherDevoirController::class, 'index']);
            Route::post('devoirs', [TeacherDevoirController::class, 'store']);
            Route::get('devoirs/{devoir}', [TeacherDevoirController::class, 'show']);
            Route::put('devoirs/{devoir}', [TeacherDevoirController::class, 'update']);
            Route::delete('devoirs/{devoir}', [TeacherDevoirController::class, 'destroy']);

            // Fournitures par classe (1 liste par classe/année)
            Route::get('fournitures/mes-classes', [\App\Http\Controllers\Api\V1\Teacher\TeacherFournituresController::class, 'mesClasses']);
            Route::get('fournitures/classe/{classeId}', [\App\Http\Controllers\Api\V1\Teacher\TeacherFournituresController::class, 'indexListe']);
            Route::post('fournitures/classe/{classeId}', [\App\Http\Controllers\Api\V1\Teacher\TeacherFournituresController::class, 'creerOuMaj']);
            Route::post('fournitures/{listeId}/items', [\App\Http\Controllers\Api\V1\Teacher\TeacherFournituresController::class, 'ajouterItem']);
            Route::delete('fournitures/{listeId}/items/{itemId}', [\App\Http\Controllers\Api\V1\Teacher\TeacherFournituresController::class, 'supprimerItem']);
            Route::post('fournitures/{listeId}/publier', [\App\Http\Controllers\Api\V1\Teacher\TeacherFournituresController::class, 'publier']);
        });

        Route::middleware('role:eleve')->prefix('student')->group(function () {
            Route::get('dashboard', [StudentPortalApiController::class, 'dashboard']);
            Route::get('notes', [StudentPortalApiController::class, 'notes']);
            Route::get('devoirs', [StudentPortalApiController::class, 'devoirs']);
            Route::get('emploi-du-temps', [StudentPortalApiController::class, 'schedule']);
            Route::get('presences', [StudentPortalApiController::class, 'presences']);
            Route::get('paiements', [StudentPortalApiController::class, 'paiements']);
        });

        // ── Direction (directeur, directeur_adjoint, gestionnaire, censeur, secretaire, comptable) ──
        Route::middleware('role:directeur,directeur_adjoint,gestionnaire,censeur,secretaire,comptable,super_admin')
            ->prefix('director')
            ->group(function () {
                Route::get('dashboard/overview', [DirectorDashboardApiController::class, 'overview']);
                Route::get('dashboard/pointage-jour', [DirectorDashboardApiController::class, 'pointageJour']);
                Route::get('dashboard/finances-graphiques', [DirectorDashboardApiController::class, 'financesGraphiques']);
                Route::get('dashboard/activite-recente', [DirectorDashboardApiController::class, 'activiteRecente']);

                // Élèves
                Route::get('eleves', [DirectorElevesApiController::class, 'index']);
                Route::get('eleves/filtres', [DirectorElevesApiController::class, 'filtres']);
                Route::get('eleves/{eleve}', [DirectorElevesApiController::class, 'show']);

                // Enseignants
                Route::get('enseignants', [DirectorEnseignantsApiController::class, 'index']);
                Route::get('enseignants/{enseignant}', [DirectorEnseignantsApiController::class, 'show']);
                Route::get('enseignants/{enseignant}/pointages', [DirectorEnseignantsApiController::class, 'pointages']);

                // Pointage (supervision)
                Route::get('pointage/aujourdhui', [DirectorPointageApiController::class, 'aujourdhui']);
                Route::get('pointage/liste', [DirectorPointageApiController::class, 'liste']);
                Route::get('pointage/{pointage}', [DirectorPointageApiController::class, 'show']);
                Route::get('pointage/alertes/liste', [DirectorPointageApiController::class, 'alertes']);
                Route::patch('pointage/alertes/{alerte}/lire', [DirectorPointageApiController::class, 'lireAlerte']);
                Route::patch('pointage/alertes/{alerte}/traiter', [DirectorPointageApiController::class, 'traiterAlerte']);
                Route::get('pointage/qr-codes/liste', [DirectorPointageApiController::class, 'qrCodes']);

                // Notes / Bulletins / Moyennes
                Route::get('bulletins/overview', [DirectorBulletinsApiController::class, 'overview']);
                Route::get('bulletins/classes/{classe}/moyennes', [DirectorBulletinsApiController::class, 'moyennesClasse']);
                Route::get('bulletins/eleves/{eleve}/trimestre/{trimestre}/pdf', [DirectorBulletinsApiController::class, 'bulletinPdf']);
            });

        // Alias mobile (conventions documentées)
        Route::middleware('role:parent')->prefix('mobile')->group(function () {
            Route::get('annee-scolaire-active', [ContextController::class, 'anneeScolaire']);
            Route::get('eleves/{eleve}/paiements', [ParentPortalApiController::class, 'paiements']);
            Route::get('eleves/{eleve}/solde', [ParentPortalApiController::class, 'paiements']);
            Route::post('eleves/{eleve}/paiements/wave/initier', [ParentPortalApiController::class, 'genererLienWave']);
            Route::get('recus/{paiement}', [ParentPortalApiController::class, 'recuPaiement']);
        });

        Route::middleware('role:parent')->prefix('parent')->group(function () {
            Route::get('dashboard', [ParentPortalApiController::class, 'dashboard']);
            Route::get('children', [ParentPortalApiController::class, 'children']);
            Route::get('children/{eleve}/notes', [ParentPortalApiController::class, 'notes']);
            Route::get('children/{eleve}/paiements', [ParentPortalApiController::class, 'paiements']);
            Route::get('children/{eleve}/paiements/historique', [ParentPortalApiController::class, 'paiementsList']);
            Route::get('children/{eleve}/presences', [ParentPortalApiController::class, 'presences']);
            Route::post('children/{eleve}/paiements/initier', [ParentPortalApiController::class, 'initierPaiement']);
            Route::post('children/{eleve}/paiements/wave', [ParentPortalApiController::class, 'genererLienWave']);
            Route::get('paiements/{paiement}/recu', [ParentPortalApiController::class, 'recuPaiement']);
            Route::get('paiements/{paiement}/pre-recu', [ParentPortalApiController::class, 'preRecuPaiement']);
        });

        require __DIR__.'/routes_financieres.php';
    });
});
