<?php
// ══════════════════════════════════════════════════════════════
// routes/web.php — AviaSchoolPay v2.0 (nettoyé)
// ══════════════════════════════════════════════════════════════

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EleveImportController;
use App\Http\Controllers\EleveImportExcelController;
use App\Http\Controllers\EleveImportSaisieController;
use App\Http\Controllers\EleveImportPdfController;
use App\Http\Controllers\EleveImportPhotoController;

use App\Http\Controllers\Admin\RhDashboardController;
use App\Http\Controllers\Admin\AffectationAdminController;
use App\Http\Controllers\Admin\PointageAdminController;
use App\Http\Controllers\Admin\AlertePointageAdminController;
use App\Http\Controllers\Admin\QrCodeAdminController;
use App\Http\Controllers\Admin\PresenceBilanController;
use App\Http\Controllers\Admin\PresenceEleveAdminController;

use App\Http\Controllers\EmploiDuTempsWebController;
use App\Http\Controllers\EdtScenarioController;
use App\Http\Controllers\EdtGenerationController;
use App\Http\Controllers\ScenarioConstraintController;
use App\Http\Controllers\VacataireImportController;
use App\Http\Controllers\EdtParametreController;
use App\Http\Controllers\EmploiDuTempsAIAssistantController;
use App\Http\Controllers\EnseignantHoraireExterneController;
use App\Http\Controllers\CreneauWebController;
use App\Http\Controllers\EnseignantPortalController;
use App\Http\Controllers\FeuilleDeNoteController;
use App\Http\Controllers\EcoleSwitcherController;
use App\Http\Controllers\MonPointageController;
use App\Http\Controllers\FicheClasseController;
use App\Http\Controllers\CahierAppelController;
use App\Http\Controllers\ElevePortalController;
use App\Http\Controllers\Admin\EvaluationSystemController;
use App\Http\Controllers\Admin\BulletinAdminController;
use App\Http\Controllers\Admin\MoyennesGrilleAdminController;
use App\Http\Controllers\Admin\DisciplinesController;
use App\Http\Controllers\Admin\SousDisciplinesController;
use App\Http\Controllers\GrilleNotesController;
use App\Http\Controllers\PointageWebController;
use App\Http\Controllers\AlertePointageWebController;

/*
|--------------------------------------------------------------------------
| Auth invité
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', 'App\Http\Controllers\Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'App\Http\Controllers\Auth\LoginController@login');

    // ── Activation compte élève via matricule DESPS ──
    Route::get('inscription-eleve',
        [\App\Http\Controllers\Auth\EleveAuthController::class, 'showCheckForm'])
        ->name('inscription.eleve.check');
    Route::post('inscription-eleve',
        [\App\Http\Controllers\Auth\EleveAuthController::class, 'check']);
    Route::get('inscription-eleve/{token}/password',
        [\App\Http\Controllers\Auth\EleveAuthController::class, 'showPasswordForm'])
        ->name('inscription.eleve.password');
    Route::post('inscription-eleve/{token}/password',
        [\App\Http\Controllers\Auth\EleveAuthController::class, 'createAccount'])
        ->name('inscription.eleve.create');
});

/*
|--------------------------------------------------------------------------
| Auth connecté
|--------------------------------------------------------------------------
*/
Route::post('logout', 'App\Http\Controllers\Auth\LoginController@logout')
    ->name('logout')
    ->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('premiere-connexion/mot-de-passe', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'show'])
        ->name('password.premiere');
    Route::post('premiere-connexion/mot-de-passe', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'update'])
        ->name('password.premiere.update');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    // Recherche globale (navbar)
    Route::get('search', 'App\Http\Controllers\SearchController@search')->name('search');


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/', function () {
        if (auth()->user()?->isSuperAdmin() && ! session('super_admin_impersonate_etab_id')) {
            return redirect()->route('admin.platform.dashboard');
        }

        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', 'App\Http\Controllers\DashboardWebController@index')->name('dashboard');
    Route::view('/acces-suspendu', 'auth.acces-suspendu')->name('acces.suspendu');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationWebController::class, 'index'])->name('index');
        Route::get('/feed', [\App\Http\Controllers\NotificationWebController::class, 'feed'])->name('feed');
        Route::post('/read-all', [\App\Http\Controllers\NotificationWebController::class, 'markAllRead'])->name('read-all');
        Route::post('/{notification}/read', [\App\Http\Controllers\NotificationWebController::class, 'markRead'])->name('read');
    });

    /*
    |--------------------------------------------------------------------------
    | M2 - Élèves
    |--------------------------------------------------------------------------
    */
    Route::resource('eleves', 'App\Http\Controllers\EleveWebController')
        ->parameters(['eleves' => 'eleve'])
        ->where(['eleve' => '[0-9]+']);
    Route::delete('/eleves/{id}', [App\Http\Controllers\EleveWebController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('eleves.destroy');
    Route::get('eleves-export', 'App\Http\Controllers\EleveWebController@export')->name('eleves.export');

    /*
    |--------------------------------------------------------------------------
    | M3 - Enseignants
    |--------------------------------------------------------------------------
    */
    Route::get('enseignants-export', [App\Http\Controllers\EnseignantWebController::class, 'export'])->name('enseignants.export');
    Route::resource('enseignants', 'App\Http\Controllers\EnseignantWebController');
    Route::get('/enseignants/{id}/photo', [App\Http\Controllers\EnseignantWebController::class, 'photo'])
        ->name('enseignants.photo');

    /*
    |--------------------------------------------------------------------------
    | M4 - Pointage
    |--------------------------------------------------------------------------
    */
    Route::get('pointage', [PointageWebController::class, 'index'])->name('pointage.index');
    Route::get('pointage/rapport', [PointageWebController::class, 'rapport'])->name('pointage.rapport');
    Route::get('pointage/parametres', [\App\Http\Controllers\PointageParametreWebController::class, 'edit'])->name('pointage.parametres.edit');
    Route::put('pointage/parametres', [\App\Http\Controllers\PointageParametreWebController::class, 'update'])->name('pointage.parametres.update');

    Route::redirect('pointages', '/pointage')->name('pointages.index');
    Route::get('pointages/{pointage}', [PointageWebController::class, 'show'])
        ->whereNumber('pointage')
        ->name('pointages.show');
    Route::get('pointages/{pointage}/selfie', [PointageWebController::class, 'selfie'])
        ->whereNumber('pointage')
        ->name('pointages.selfie');
    Route::get('pointages/{pointage}/cahier-texte', [PointageWebController::class, 'cahierTexte'])
        ->whereNumber('pointage')
        ->name('pointages.cahier-texte');
    Route::patch('pointages/alertes/{alerte}', [PointageWebController::class, 'traiterAlerte'])
        ->whereNumber('alerte')
        ->name('pointages.alertes.traiter');

    Route::prefix('alertes-pointage')->name('alertes-pointage.')->group(function () {
        Route::get('/', [AlertePointageWebController::class, 'index'])->name('index');
        Route::get('/{alerte}', [AlertePointageWebController::class, 'show'])
            ->whereNumber('alerte')
            ->name('show');
        Route::patch('/{alerte}/lire', [AlertePointageWebController::class, 'marquerLue'])
            ->whereNumber('alerte')
            ->name('lire');
        Route::patch('/{alerte}/traiter', [AlertePointageWebController::class, 'traiter'])
            ->whereNumber('alerte')
            ->name('traiter');
    });

    /*
    |--------------------------------------------------------------------------
    | M5 - Notes
    |--------------------------------------------------------------------------
    */
    Route::get('notes', 'App\Http\Controllers\NoteWebController@index')->name('notes.index');
    Route::get('notes/saisie/{classe}/{trimestre}', 'App\Http\Controllers\NoteWebController@saisie')->name('notes.saisie');
    Route::get('notes/bulletins/{classe}/{trimestre}', 'App\Http\Controllers\NoteWebController@bulletins')->name('notes.bulletins');

    /*
    |--------------------------------------------------------------------------
    | Classes
    |--------------------------------------------------------------------------
    */
    Route::get('classes', [App\Http\Controllers\ClasseWebController::class, 'index'])->name('classes.index');
    Route::get('classes/{classe}', [App\Http\Controllers\ClasseWebController::class, 'show'])->name('classes.show');

    Route::middleware('role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur')->group(function () {
        Route::get('classes/create/form', [App\Http\Controllers\ClasseWebController::class, 'create'])->name('classes.create');
        Route::post('classes', [App\Http\Controllers\ClasseWebController::class, 'store'])->name('classes.store');
        Route::get('classes/{classe}/edit', [App\Http\Controllers\ClasseWebController::class, 'edit'])->name('classes.edit');
        Route::put('classes/{classe}', [App\Http\Controllers\ClasseWebController::class, 'update'])->name('classes.update');
        Route::delete('classes/{classe}', [App\Http\Controllers\ClasseWebController::class, 'destroy'])->name('classes.destroy');
        Route::post('classes/{classe}/duplicate', [App\Http\Controllers\ClasseWebController::class, 'duplicate'])->name('classes.duplicate');
        Route::post('/classes/quick-create', [App\Http\Controllers\ClasseWebController::class, 'quickCreate'])->name('classes.quickCreate');
        Route::post('/classes/tarifs/ajuster', [App\Http\Controllers\ClasseWebController::class, 'ajusterTarifs'])
            ->name('classes.tarifs.ajuster');
    });

    /*
    |--------------------------------------------------------------------------
    | M7 - EMPLOI DU TEMPS
    |--------------------------------------------------------------------------
    | Lecture commune
    |--------------------------------------------------------------------------
    */
    Route::prefix('emploi-du-temps')->name('emploi-du-temps.')->group(function () {
        Route::get('/', [EmploiDuTempsWebController::class, 'index'])->name('index');
        Route::get('/professeur/{enseignant}', [EmploiDuTempsWebController::class, 'professeur'])->name('professeur');
        Route::get('/grille', [EmploiDuTempsWebController::class, 'grille'])->name('grille');
    });

    /*
    |--------------------------------------------------------------------------
    | M7 - EMPLOI DU TEMPS (admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur')
        ->prefix('emploi-du-temps')
        ->name('emploi-du-temps.')
        ->group(function () {

            // Paramètres IA
            Route::get('/parametres', [EdtParametreController::class, 'edit'])->name('parametres.edit');
            Route::put('/parametres', [EdtParametreController::class, 'update'])->name('parametres.update');
            Route::get('/parametres/plages', [EdtParametreController::class, 'editPlages'])->name('parametres.plages');
            Route::put('/parametres/plages', [EdtParametreController::class, 'updatePlages'])->name('parametres.plages.update');

            // Création / manuel
            Route::get('/create', [EmploiDuTempsWebController::class, 'create'])->name('create');
            Route::get('/conflits', [EmploiDuTempsWebController::class, 'conflits'])->name('conflits');

            Route::post('/', [EmploiDuTempsWebController::class, 'store'])->name('store');
            Route::post('/{emploi}/toggle', [EmploiDuTempsWebController::class, 'toggle'])->name('toggle');
            Route::get('/{emploi}/edit', [EmploiDuTempsWebController::class, 'edit'])->name('edit');
            Route::put('/{emploi}', [EmploiDuTempsWebController::class, 'update'])->name('update');
            Route::delete('/{emploi}', [EmploiDuTempsWebController::class, 'destroy'])->name('destroy');

            // PDF
            Route::get('/grille/pdf/classe', [EmploiDuTempsWebController::class, 'pdfClasse'])->name('grille.pdf.classe');
            Route::get('/grille/pdf/classes', [EmploiDuTempsWebController::class, 'pdfClasses'])->name('grille.pdf.classes');
            Route::post('/grille/pdf/classes/custom', [EmploiDuTempsWebController::class, 'pdfClassesCustom'])->name('grille.pdf.classes.custom');

            Route::get('/grille/pdf/professeur', [EmploiDuTempsWebController::class, 'pdfProfesseur'])->name('grille.pdf.professeur');
            Route::get('/grille/pdf/professeurs', [EmploiDuTempsWebController::class, 'pdfProfesseurs'])->name('grille.pdf.professeurs');
            Route::post('/grille/pdf/professeurs/custom', [EmploiDuTempsWebController::class, 'pdfProfesseursCustom'])->name('grille.pdf.professeurs.custom');

            // Legacy IA
            Route::post('/ia/suggestions', [EmploiDuTempsWebController::class, 'iaSuggestions'])->name('ia.suggestions');
            Route::post('/ia/generer-classe', [EmploiDuTempsWebController::class, 'genererClasse'])->name('ia.generer-classe');
            Route::post('/ia/generer-global', [EmploiDuTempsWebController::class, 'genererGlobal'])->name('ia.generer-global');
            
            Route::post('/ia/runs/{run}/proposals', [EmploiDuTempsAIAssistantController::class, 'saveProposals'])
             ->name('ia.proposals.save');

            // IA unifiée
            Route::get('/ia', [EmploiDuTempsAIAssistantController::class, 'index'])->name('ia.index');
            Route::post('/ia/generate', [EmploiDuTempsAIAssistantController::class, 'generate'])->name('ia.generate');
            Route::get('/ia/runs/{run}', [EmploiDuTempsAIAssistantController::class, 'report'])->name('ia.report');
            Route::post('/ia/runs/{run}/apply', [EmploiDuTempsAIAssistantController::class, 'apply'])->name('ia.apply');

            // OCR / imports vacataires pour IA unifiée
            Route::post('/ia/vacataires/import', [VacataireImportController::class, 'store'])
                ->name('ia.vacataires.import');
            Route::post('/ia/vacataires/imports/{import}/ocr', [VacataireImportController::class, 'parse'])
                ->name('ia.vacataires.ocr');
            Route::post('/ia/vacataires/imports/{import}/validate', [VacataireImportController::class, 'validateImport'])
                ->name('ia.vacataires.validate');

            // Assistant avancé / legacy
            Route::get('/assistant', [EdtScenarioController::class, 'index'])->name('assistant.index');
            Route::post('/assistant/scenarios', [EdtScenarioController::class, 'store'])->name('assistant.scenarios.store');
            Route::get('/assistant/scenarios/{scenario}', [EdtScenarioController::class, 'show'])->name('assistant.scenarios.show');

            Route::post('/assistant/scenarios/{scenario}/vacataires/import', [VacataireImportController::class, 'store'])
                ->name('assistant.vacataires.import');
            Route::post('/assistant/imports/{import}/parse', [VacataireImportController::class, 'parse'])
                ->name('assistant.vacataires.parse');
            Route::post('/assistant/imports/{import}/validate', [VacataireImportController::class, 'validateImport'])
                ->name('assistant.vacataires.validate');

            Route::post('/assistant/scenarios/{scenario}/constraints', [ScenarioConstraintController::class, 'save'])
                ->name('assistant.constraints.save');

            Route::post('/assistant/scenarios/{scenario}/generate', [EdtGenerationController::class, 'generate'])
                ->name('assistant.generate');
            Route::get('/assistant/runs/{run}', [EdtGenerationController::class, 'report'])
                ->name('assistant.runs.report');
            Route::post('/assistant/runs/{run}/apply', [EdtGenerationController::class, 'apply'])
                ->name('assistant.runs.apply');

            // ── Horaires externes des enseignants (autres écoles) ──
            Route::prefix('enseignants/{enseignant}/horaires-externes')
                ->name('horaires-externes.')
                ->group(function () {
                    Route::get('/', [EnseignantHoraireExterneController::class, 'index'])
                        ->name('index');
                    Route::post('/upload', [EnseignantHoraireExterneController::class, 'upload'])
                        ->name('upload');
                    Route::post('/imports/{import}/analyser', [EnseignantHoraireExterneController::class, 'analyser'])
                        ->name('analyser');
                    Route::post('/imports/{import}/valider', [EnseignantHoraireExterneController::class, 'valider'])
                        ->name('valider');
                    Route::delete('/imports/{import}', [EnseignantHoraireExterneController::class, 'destroyImport'])
                        ->name('destroy-import');
                    Route::patch('/slots/{slot}/toggle', [EnseignantHoraireExterneController::class, 'toggleValide'])
                        ->name('toggle');
                    Route::delete('/slots/{slot}', [EnseignantHoraireExterneController::class, 'destroy'])
                        ->name('destroy');
                });
        });

    /*
    |--------------------------------------------------------------------------
    | M6 - Paiements
    |--------------------------------------------------------------------------
    */
    Route::prefix('finances')->name('finances.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FinanceWebController::class, 'index'])->name('index');
        Route::get('/wave', [\App\Http\Controllers\FinanceWebController::class, 'wave'])->name('wave');
        Route::post('/wave/toggle', [\App\Http\Controllers\FinanceWebController::class, 'toggleWave'])->name('wave.toggle');
        Route::post('/wave/config', [\App\Http\Controllers\FinanceWebController::class, 'updateWaveConfig'])->name('wave.config');
        Route::post('/paiements-manuels/toggle', [\App\Http\Controllers\FinanceWebController::class, 'toggleManualPayments'])->name('paiements-manuels.toggle');
        Route::get('/tarifs', [\App\Http\Controllers\FinanceWebController::class, 'tarifs'])->name('tarifs');
        Route::post('/tarifs/college', [\App\Http\Controllers\FinanceWebController::class, 'updateTarifsCollege'])->name('tarifs.college');
        Route::post('/tarifs/lycee', [\App\Http\Controllers\FinanceWebController::class, 'updateTarifsLycee'])->name('tarifs.lycee');
        Route::post('/tarifs/niveau/{niveau}', [\App\Http\Controllers\FinanceWebController::class, 'updateTarifsNiveau'])->name('tarifs.niveau');
        Route::post('/synchroniser', [\App\Http\Controllers\FinanceWebController::class, 'synchroniser'])->name('synchroniser');
        Route::get('/eleves/{eleve}', [\App\Http\Controllers\FinanceWebController::class, 'eleve'])->name('eleve');
        Route::post('/eleves/{eleve}/lien-wave', [\App\Http\Controllers\FinanceWebController::class, 'genererLienWave'])->name('eleve.lien-wave');
    });

    Route::middleware('role:super_admin,directeur,directeur_adjoint,gestionnaire')->prefix('admin/annees-scolaires')->name('admin.annees.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'store'])->name('store');
        Route::put('/{annee}', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'update'])->name('update');
        Route::post('/{annee}/activer', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'activer'])->name('activer');
        Route::post('/{annee}/cloturer', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'cloturer'])->name('cloturer');
        Route::post('/{annee}/demander-restauration', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'demanderRestauration'])->name('demander-restauration');
        Route::get('/{annee}/consulter', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'consulter'])->name('consulter');
        Route::post('/{annee}/restaurer', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'restaurer'])->name('restaurer');
        Route::post('/{annee}/resynchroniser', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'resynchroniser'])->name('resynchroniser');
        Route::post('/{annee}/reimporter-edt', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'reimporterEdt'])->name('reimporter-edt');
        Route::get('/diagnostic-bd', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'diagnosticBd'])->name('diagnostic-bd');
        Route::post('/restaurer-fichier', [\App\Http\Controllers\Admin\AnneeScolaireWebController::class, 'restaurerFichier'])->name('restaurer-fichier');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin', fn () => redirect()->route('admin.platform.dashboard'))->name('admin.home');
        Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\SuperAdminDashboardController::class, 'index'])->name('admin.platform.dashboard');

        Route::prefix('admin/etablissements')->name('admin.etablissements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'store'])->name('store');
            Route::get('/{etablissement}', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'show'])->name('show');
            Route::get('/{etablissement}/edit', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'edit'])->name('edit');
            Route::put('/{etablissement}', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'update'])->name('update');
            Route::delete('/{etablissement}', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'destroy'])->name('destroy');
            Route::post('/{etablissement}/toggle-access', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'toggleAccess'])->name('toggle-access');
            Route::post('/{etablissement}/users', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'storeUser'])->name('users.store');
            Route::post('/{etablissement}/users/{user}/toggle', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'toggleUser'])->name('users.toggle');
            Route::post('/{etablissement}/users/{user}/reset-password', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'resetUserPassword'])->name('users.reset-password');
            Route::post('/{etablissement}/ouvrir', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'ouvrirEspace'])->name('ouvrir');
        });

        Route::post('/admin/quitter-espace', [\App\Http\Controllers\Admin\EtablissementAdminController::class, 'quitterEspace'])->name('admin.quitter-espace');
    });

    Route::middleware('role:super_admin')->prefix('admin/platform')->name('admin.platform.')->group(function () {
        Route::get('/parametres', [\App\Http\Controllers\Admin\PlatformAdminController::class, 'index'])->name('parametres');
        Route::put('/wave', [\App\Http\Controllers\Admin\PlatformAdminController::class, 'updateWave'])->name('wave');
        Route::post('/restaurations/{demande}/livrer-cle', [\App\Http\Controllers\Admin\PlatformAdminController::class, 'livrerCle'])->name('livrer-cle');
        Route::get('/archives/{annee}/cle', [\App\Http\Controllers\Admin\PlatformAdminController::class, 'voirCleArchive'])->name('archive-cle');
    });

    Route::middleware('role:super_admin')->prefix('admin/sms')->name('admin.sms.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SmsAdminController::class, 'index'])->name('index');
        Route::post('/{id}/payer', [\App\Http\Controllers\Admin\SmsAdminController::class, 'marquerPaye'])->name('payer');
        Route::post('/{id}/crediter', [\App\Http\Controllers\Admin\SmsAdminController::class, 'crediter'])->name('crediter');
        Route::post('/{id}/annuler', [\App\Http\Controllers\Admin\SmsAdminController::class, 'annuler'])->name('annuler');
    });

    Route::middleware('role:super_admin')->prefix('admin/wave')->name('admin.wave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\WavePaymentAdminController::class, 'index'])->name('index');
        Route::put('/etablissements/{etablissement}', [\App\Http\Controllers\Admin\WavePaymentAdminController::class, 'update'])->name('update');
        Route::post('/etablissements/{etablissement}/test', [\App\Http\Controllers\Admin\WavePaymentAdminController::class, 'test'])->name('test');
    });

    Route::prefix('paiements')->name('paiements.')->group(function () {
        Route::get('/',                [\App\Http\Controllers\PaiementWebController::class, 'index'])  ->name('index');
        Route::post('/find-by-reference', [\App\Http\Controllers\PaiementWebController::class, 'findByReference'])->name('find-by-reference');
        Route::get('/create',          [\App\Http\Controllers\PaiementWebController::class, 'create']) ->name('create');
        Route::post('/',               [\App\Http\Controllers\PaiementWebController::class, 'store'])  ->name('store');
        Route::get('/export',          [\App\Http\Controllers\PaiementWebController::class, 'export']) ->name('export');
        Route::get('/{paiement}',      [\App\Http\Controllers\PaiementWebController::class, 'show'])   ->whereNumber('paiement')->name('show');
        Route::get('/{paiement}/recu', [\App\Http\Controllers\PaiementWebController::class, 'recu'])   ->whereNumber('paiement')->name('recu');
        Route::post('/{paiement}/confirmer', [\App\Http\Controllers\PaiementWebController::class, 'confirmer'])->whereNumber('paiement')->name('confirmer');
        Route::post('/{paiement}/annuler',   [\App\Http\Controllers\PaiementWebController::class, 'annuler'])  ->whereNumber('paiement')->name('annuler');
    });

    /*
    |--------------------------------------------------------------------------
    | M11 - SIGFNE
    |--------------------------------------------------------------------------
    */
    Route::get('sigfne', 'App\Http\Controllers\SigfneWebController@index')->name('sigfne.index');
    Route::post('sigfne/preparer', 'App\Http\Controllers\SigfneWebController@preparer')->name('sigfne.preparer');
    Route::post('sigfne/executer', 'App\Http\Controllers\SigfneWebController@executer')->name('sigfne.executer');
    Route::get('sigfne/remontees/{id}/fichier', 'App\Http\Controllers\SigfneWebController@telechargerFichier')->name('sigfne.fichier');
    Route::post('sigfne/parametrer', 'App\Http\Controllers\SigfneWebController@parametrer')->name('sigfne.parametrer');
    Route::get('sigfne/dfa', 'App\Http\Controllers\SigfneWebController@dfa')->name('sigfne.dfa');

    /*
    |--------------------------------------------------------------------------
    | M8 - Communication
    |--------------------------------------------------------------------------
    */
    Route::get('communication', 'App\Http\Controllers\CommunicationWebController@index')->name('communication.index');
    Route::post('communication/annonces', 'App\Http\Controllers\CommunicationWebController@storeAnnonce')->name('communication.annonces.store');
    Route::post('communication/annonces/{id}/publier', 'App\Http\Controllers\CommunicationWebController@publierAnnonce')->name('communication.annonces.publier');
    Route::delete('communication/annonces/{id}', 'App\Http\Controllers\CommunicationWebController@destroyAnnonce')->name('communication.annonces.destroy');

    /*
    |--------------------------------------------------------------------------
    | M9 - IA
    |--------------------------------------------------------------------------
    */
    Route::get('ia', 'App\Http\Controllers\IAWebController@index')->name('ia.index');

    /*
    |--------------------------------------------------------------------------
    | Modules financiers (12-17)
    |--------------------------------------------------------------------------
    */
    Route::get('comptabilite', 'App\Http\Controllers\ComptabiliteWebController@index')->name('comptabilite.index');
    Route::post('comptabilite/initialiser', 'App\Http\Controllers\ComptabiliteWebController@initialiser')->name('comptabilite.initialiser');
    Route::post('comptabilite/synchroniser-paiements', 'App\Http\Controllers\ComptabiliteWebController@synchroniserPaiements')->name('comptabilite.synchroniser-paiements');
    Route::get('comptabilite/journal', 'App\Http\Controllers\ComptabiliteWebController@journal')->name('comptabilite.journal');
    Route::get('comptabilite/grand-livre', 'App\Http\Controllers\ComptabiliteWebController@grandLivre')->name('comptabilite.grand-livre');
    Route::get('comptabilite/bilan', 'App\Http\Controllers\ComptabiliteWebController@bilan')->name('comptabilite.bilan');
    Route::get('comptabilite/resultat', 'App\Http\Controllers\ComptabiliteWebController@resultat')->name('comptabilite.resultat');

    Route::get('depenses', 'App\Http\Controllers\DepenseWebController@index')->name('depenses.index');
    Route::get('depenses/create', 'App\Http\Controllers\DepenseWebController@create')->name('depenses.create');
    Route::post('depenses', 'App\Http\Controllers\DepenseWebController@store')->name('depenses.store');
    Route::get('depenses/categories', 'App\Http\Controllers\DepenseWebController@categories')->name('depenses.categories');
    Route::post('depenses/categories', 'App\Http\Controllers\DepenseWebController@categoriesStore')->name('depenses.categories.store');
    Route::delete('depenses/categories/{id}', 'App\Http\Controllers\DepenseWebController@categoriesDestroy')->name('depenses.categories.destroy');
    Route::get('depenses/{depense}', 'App\Http\Controllers\DepenseWebController@show')->name('depenses.show');
    Route::post('depenses/{depense}/approuver', 'App\Http\Controllers\DepenseWebController@approuver')->name('depenses.approuver');
    Route::post('depenses/{depense}/rejeter', 'App\Http\Controllers\DepenseWebController@rejeter')->name('depenses.rejeter');

    Route::get('tresorerie', 'App\Http\Controllers\TresorerieWebController@index')->name('tresorerie.index');
    Route::post('tresorerie', 'App\Http\Controllers\TresorerieWebController@store')->name('tresorerie.store');
    Route::get('tresorerie/mouvements', 'App\Http\Controllers\TresorerieWebController@mouvements')->name('tresorerie.mouvements');
    Route::post('tresorerie/virement', 'App\Http\Controllers\TresorerieWebController@virement')->name('tresorerie.virement');

    Route::get('budgets', 'App\Http\Controllers\BudgetWebController@index')->name('budgets.index');
    Route::get('budgets/create', 'App\Http\Controllers\BudgetWebController@create')->name('budgets.create');
    Route::post('budgets', 'App\Http\Controllers\BudgetWebController@store')->name('budgets.store');
    Route::get('budgets/{budget}', 'App\Http\Controllers\BudgetWebController@show')->name('budgets.show');
    Route::post('budgets/{budget}/valider', 'App\Http\Controllers\BudgetWebController@valider')->name('budgets.valider');
    Route::post('budgets/{budget}/cloturer', 'App\Http\Controllers\BudgetWebController@cloturer')->name('budgets.cloturer');
    Route::post('budgets/{budget}/recalculer', 'App\Http\Controllers\BudgetWebController@recalculer')->name('budgets.recalculer');
    Route::post('budgets/{budget}/lignes', 'App\Http\Controllers\BudgetWebController@ajouterLigne')->name('budgets.lignes.store');
    Route::delete('budgets/{budget}/lignes/{ligne}', 'App\Http\Controllers\BudgetWebController@supprimerLigne')->name('budgets.lignes.destroy');

    Route::get('rentabilite', 'App\Http\Controllers\RentabiliteWebController@index')->name('rentabilite.index');
    Route::get('rentabilite/classes', 'App\Http\Controllers\RentabiliteWebController@parClasse')->name('rentabilite.classes');
    Route::get('rentabilite/services', 'App\Http\Controllers\RentabiliteWebController@parService')->name('rentabilite.services');

    Route::get('simulations', 'App\Http\Controllers\SimulationWebController@index')->name('simulations.index');
    Route::get('simulations/create', 'App\Http\Controllers\SimulationWebController@create')->name('simulations.create');
    Route::post('simulations', 'App\Http\Controllers\SimulationWebController@store')->name('simulations.store');
    Route::get('simulations/{simulation}', 'App\Http\Controllers\SimulationWebController@show')->name('simulations.show');
    Route::delete('simulations/{simulation}', 'App\Http\Controllers\SimulationWebController@destroy')->name('simulations.destroy');
    Route::post('simulations/{simulation}/favori', 'App\Http\Controllers\SimulationWebController@favori')->name('simulations.favori');

    Route::get('cockpit', 'App\Http\Controllers\CockpitWebController@index')->name('cockpit.index');
    Route::get('cockpit/score', 'App\Http\Controllers\CockpitWebController@score')->name('cockpit.score');
    Route::get('cockpit/alertes', 'App\Http\Controllers\CockpitWebController@alertes')->name('cockpit.alertes');

    // ─── Fiches de paie ───
    Route::get('fiches-paie', 'App\Http\Controllers\FichePaieController@index')->name('fiches-paie.index');
    Route::post('fiches-paie', 'App\Http\Controllers\FichePaieController@generer')->name('fiches-paie.generer');
    Route::post('fiches-paie/generer-pour-tous', 'App\Http\Controllers\FichePaieController@genererPourTous')->name('fiches-paie.generer-tous');
    Route::get('fiches-paie/previsualiser/{enseignant}', 'App\Http\Controllers\FichePaieController@previsualiser')->name('fiches-paie.previsualiser');
    Route::get('fiches-paie/{id}', 'App\Http\Controllers\FichePaieController@show')->name('fiches-paie.show');
    Route::get('fiches-paie/{id}/pdf', 'App\Http\Controllers\FichePaieController@pdf')->name('fiches-paie.pdf');
    Route::post('fiches-paie/{id}/valider', 'App\Http\Controllers\FichePaieController@valider')->name('fiches-paie.valider');
    Route::post('fiches-paie/{id}/payer', 'App\Http\Controllers\FichePaieController@marquerPayee')->name('fiches-paie.payer');
    Route::delete('fiches-paie/{id}', 'App\Http\Controllers\FichePaieController@destroy')->name('fiches-paie.destroy');
    Route::post('enseignants/{id}/parametrer-remuneration', 'App\Http\Controllers\FichePaieController@parametrerRemuneration')->name('enseignants.parametrer-remuneration');

    // ─── SMS Direction ───
    Route::get('sms', 'App\Http\Controllers\SmsController@index')->name('sms.index');
    Route::post('sms/recharger', 'App\Http\Controllers\SmsController@rechargerStore')->name('sms.recharger');
    Route::post('sms/recharges/{id}/annuler', 'App\Http\Controllers\SmsController@annulerRecharge')->name('sms.recharges.annuler');
    Route::get('sms/relance-impayes/apercu', 'App\Http\Controllers\SmsController@relanceImpayesApercu')->name('sms.relance.apercu');
    Route::post('sms/relance-impayes', 'App\Http\Controllers\SmsController@relanceImpayesEnvoyer')->name('sms.relance.envoyer');
    Route::post('sms/envoyer', 'App\Http\Controllers\SmsController@envoyerManuel')->name('sms.envoyer');

    // ─── Centre de documents Direction ───
    Route::get('documents', 'App\Http\Controllers\DocumentsCenterController@index')->name('documents.index');
    Route::get('documents/eleves.pdf', 'App\Http\Controllers\DocumentsCenterController@listeElevesPdf')->name('documents.eleves.pdf');
    Route::get('documents/eleves-non-soldes.pdf', 'App\Http\Controllers\DocumentsCenterController@elevesNonSoldesPdf')->name('documents.non-soldes.pdf');
    Route::get('documents/annuaire-parents.pdf', 'App\Http\Controllers\DocumentsCenterController@annuaireParentsPdf')->name('documents.annuaire.pdf');
    Route::get('documents/nappe-moyennes.pdf', 'App\Http\Controllers\DocumentsCenterController@nappeMoyennesPdf')->name('documents.nappe.pdf');
    Route::get('documents/synthese-niveau.pdf', 'App\Http\Controllers\DocumentsCenterController@syntheseNiveauPdf')->name('documents.synthese-niveau.pdf');
    Route::get('documents/enseignants.pdf', 'App\Http\Controllers\DocumentsCenterController@listeEnseignantsPdf')->name('documents.enseignants.pdf');
    Route::get('documents/recap-paie.pdf', 'App\Http\Controllers\DocumentsCenterController@recapPaiePdf')->name('documents.recap-paie.pdf');
    Route::get('documents/bulletin.pdf', 'App\Http\Controllers\DocumentsCenterController@bulletinElevePdf')->name('documents.bulletin.pdf');
    Route::get('documents/tableau-honneur.pdf', 'App\Http\Controllers\DocumentsCenterController@tableauHonneurPdf')->name('documents.honneur.pdf');
    Route::get('documents/eleves-difficulte.pdf', 'App\Http\Controllers\DocumentsCenterController@elevesDifficultePdf')->name('documents.difficulte.pdf');
    Route::get('documents/carnet-presence.pdf', 'App\Http\Controllers\DocumentsCenterController@carnetPresencePdf')->name('documents.carnet.pdf');
    Route::get('documents/certificat-scolarite.pdf', 'App\Http\Controllers\DocumentsCenterController@certificatScolaritePdf')->name('documents.certificat.pdf');
    Route::get('documents/eleves.csv', 'App\Http\Controllers\DocumentsCenterController@listeElevesCsv')->name('documents.eleves.csv');
    Route::get('documents/eleves-non-soldes.csv', 'App\Http\Controllers\DocumentsCenterController@elevesNonSoldesCsv')->name('documents.non-soldes.csv');
    Route::get('documents/recap-paie.csv', 'App\Http\Controllers\DocumentsCenterController@recapPaieCsv')->name('documents.recap-paie.csv');
    Route::get('documents/bulletins-classe.pdf', 'App\Http\Controllers\DocumentsCenterController@bulletinsClassePdf')->name('documents.bulletins-classe.pdf');
    Route::get('documents/attestation-paiement.pdf', 'App\Http\Controllers\DocumentsCenterController@attestationPaiementPdf')->name('documents.attestation.pdf');
    Route::get('documents/recap-annuel.pdf', 'App\Http\Controllers\DocumentsCenterController@recapAnnuelPdf')->name('documents.recap-annuel.pdf');
    Route::get('documents/cartes-eleves.pdf', 'App\Http\Controllers\DocumentsCenterController@cartesElevesPdf')->name('documents.cartes.pdf');
    Route::get('documents/calendrier-annuel.pdf', 'App\Http\Controllers\DocumentsCenterController@calendrierAnnuelPdf')->name('documents.calendrier.pdf');
    Route::get('documents/convocation-conseil.pdf', 'App\Http\Controllers\DocumentsCenterController@convocationConseilPdf')->name('documents.convocation.pdf');

    // Listes de fournitures par classe
    Route::get('fournitures', 'App\Http\Controllers\FournituresController@index')->name('fournitures.index');
    Route::post('fournitures', 'App\Http\Controllers\FournituresController@store')->name('fournitures.store');
    Route::get('fournitures/{id}/pdf', 'App\Http\Controllers\FournituresController@pdf')->name('fournitures.pdf');
    Route::post('fournitures/{id}/items', 'App\Http\Controllers\FournituresController@ajouterItem')->name('fournitures.items.store');
    Route::delete('fournitures/{id}/items/{itemId}', 'App\Http\Controllers\FournituresController@supprimerItem')->name('fournitures.items.destroy');
    Route::post('fournitures/{id}/publier', 'App\Http\Controllers\FournituresController@publier')->name('fournitures.publier');
    Route::delete('fournitures/{id}', 'App\Http\Controllers\FournituresController@destroy')->name('fournitures.destroy');
    Route::get('fournitures/{id}', 'App\Http\Controllers\FournituresController@show')->name('fournitures.show');

    // CRUD conseils de classe + événements scolaires
    Route::get('conseils-classe', 'App\Http\Controllers\ConseilClasseController@index')->name('conseils-classe.index');
    Route::post('conseils-classe', 'App\Http\Controllers\ConseilClasseController@store')->name('conseils-classe.store');
    Route::delete('conseils-classe/{id}', 'App\Http\Controllers\ConseilClasseController@destroy')->name('conseils-classe.destroy');

    Route::get('evenements-scolaires', 'App\Http\Controllers\EvenementScolaireController@index')->name('evenements.index');
    Route::post('evenements-scolaires', 'App\Http\Controllers\EvenementScolaireController@store')->name('evenements.store');
    Route::post('evenements-scolaires/{id}/publier', 'App\Http\Controllers\EvenementScolaireController@publier')->name('evenements.publier');
    Route::delete('evenements-scolaires/{id}', 'App\Http\Controllers\EvenementScolaireController@destroy')->name('evenements.destroy');

    // ─── Rapports financiers PDF ───
    Route::get('rapports', 'App\Http\Controllers\RapportFinancierController@index')->name('rapports.index');
    Route::get('rapports/paiements.pdf', 'App\Http\Controllers\RapportFinancierController@paiementsPdf')->name('rapports.paiements.pdf');
    Route::get('rapports/bilan-scolarite.pdf', 'App\Http\Controllers\RapportFinancierController@bilanScolaritePdf')->name('rapports.bilan-scolarite.pdf');
    Route::get('rapports/mensuel.pdf', 'App\Http\Controllers\RapportFinancierController@mensuelPdf')->name('rapports.mensuel.pdf');
    Route::get('rapports/trimestriel.pdf', 'App\Http\Controllers\RapportFinancierController@trimestrielPdf')->name('rapports.trimestriel.pdf');
    Route::post('cockpit/score/recalculer', 'App\Http\Controllers\CockpitWebController@recalculerScore')->name('cockpit.score.recalculer');
    Route::post('cockpit/alertes/{id}/traiter', 'App\Http\Controllers\CockpitWebController@traiterAlerte')->name('cockpit.alertes.traiter');

    /*
    |--------------------------------------------------------------------------
    | Créneaux horaires (paramétrable par école)
    |--------------------------------------------------------------------------
    */
    Route::prefix('emploi-du-temps/creneaux')->name('emploi-du-temps.creneaux.')->group(function () {
        Route::get('/',          [CreneauWebController::class, 'index'])  ->name('index');
        Route::post('/',         [CreneauWebController::class, 'store'])  ->name('store');
        Route::put('/{creneau}', [CreneauWebController::class, 'update']) ->name('update');
        Route::delete('/{creneau}', [CreneauWebController::class, 'destroy'])->name('destroy');
        Route::post('/reorder',  [CreneauWebController::class, 'reorder'])->name('reorder');
    });

    /*
    |--------------------------------------------------------------------------
    | Portail Enseignant — Mon espace
    |--------------------------------------------------------------------------
    */
    // Sélecteur d'école (avant le middleware ecole.active pour éviter la boucle)
    Route::prefix('ecole-switcher')->name('ecole.switcher.')->group(function () {
        Route::get('/',       [EcoleSwitcherController::class, 'index'])  ->name('index');
        Route::post('/select',[EcoleSwitcherController::class, 'select']) ->name('select');
    });

    Route::prefix('mon-espace')->name('mon-espace.')->middleware('ecole.active')->group(function () {
        Route::get('/',                                   [EnseignantPortalController::class, 'dashboard'])       ->name('dashboard');
        Route::get('/classes',                            [EnseignantPortalController::class, 'classes'])         ->name('classes');
        Route::get('/classes/{classe}/eleves',            [EnseignantPortalController::class, 'eleves'])          ->name('eleves');
        Route::get('/classes/{classe}/evaluations',       [EnseignantPortalController::class, 'evaluations'])     ->name('evaluations');
        Route::post('/classes/{classe}/evaluations',      [EnseignantPortalController::class, 'storeEvaluation']) ->name('evaluations.store');
        Route::get('/evaluations/{evaluation}/notes',     [EnseignantPortalController::class, 'notes'])           ->name('notes');
        Route::post('/evaluations/{evaluation}/notes',    [EnseignantPortalController::class, 'storeNotes'])      ->name('notes.store');
        Route::get('/classes/{classe}/devoirs',           [EnseignantPortalController::class, 'devoirs'])         ->name('devoirs');
        Route::post('/classes/{classe}/devoirs',          [EnseignantPortalController::class, 'storeDevoir'])     ->name('devoirs.store');
        Route::delete('/devoirs/{devoir}',                [EnseignantPortalController::class, 'destroyDevoir'])   ->name('devoirs.destroy');

        // ── Saisie directe des moyennes ──
        Route::get('/classes/{classe}/moyennes',          [EnseignantPortalController::class, 'moyennes'])      ->name('moyennes');
        Route::post('/classes/{classe}/moyennes',         [EnseignantPortalController::class, 'storeMoyennes']) ->name('moyennes.store');

        // ── Notes & Devoirs (hub centralisé) ──
        Route::get('/notes-hub', [EnseignantPortalController::class, 'notesHub'])->name('notes-hub');

        // ── Grille de notes spreadsheet ──
        Route::prefix('classes/{classe}/grille-notes')->name('grille-notes.')->group(function () {
            Route::get('/',                   [\App\Http\Controllers\GrilleNotesController::class, 'index'])     ->name('index');
            Route::post('/columns',           [\App\Http\Controllers\GrilleNotesController::class, 'addColumn']) ->name('add-col');
            Route::post('/publish',           [\App\Http\Controllers\GrilleNotesController::class, 'publish'])   ->name('publish');
            Route::post('/unpublish',         [\App\Http\Controllers\GrilleNotesController::class, 'unpublish']) ->name('unpublish');
        });
        Route::patch('/grille-notes/evaluations/{evaluation}',  [\App\Http\Controllers\GrilleNotesController::class, 'updateColumn'])->name('grille-notes.update-col');
        Route::delete('/grille-notes/evaluations/{evaluation}', [\App\Http\Controllers\GrilleNotesController::class, 'deleteColumn'])->name('grille-notes.delete-col');
        Route::post('/grille-notes/evaluations/{evaluation}/notes', [\App\Http\Controllers\GrilleNotesController::class, 'saveNote'])->name('grille-notes.save-note');

        // ── Mon pointage QR (scanner caméra) ──
        Route::get('/pointage',       [MonPointageController::class, 'index']) ->name('pointage');
        Route::post('/pointage/scan', [MonPointageController::class, 'scan'])  ->name('pointage.scan');

        // ── Fiche de classe (imprimable) ──
        Route::prefix('classes/{classe}/fiche-classe')->name('fiche-classe.')->group(function () {
            Route::get('/pdf',   [FicheClasseController::class, 'pdf'])  ->name('pdf');
            Route::get('/excel', [FicheClasseController::class, 'excel'])->name('excel');
        });

        // ── Cahier d'appel ──
        Route::prefix('classes/{classe}/cahier-appel')->name('cahier-appel.')->group(function () {
            Route::get('/',                 [CahierAppelController::class, 'index'])         ->name('index');
            Route::get('/jour',             [CahierAppelController::class, 'appelJour'])     ->name('appel-jour');
            Route::post('/jour/mark',       [CahierAppelController::class, 'appelJourMark']) ->name('appel-jour.mark');
            Route::get('/pdf',              [CahierAppelController::class, 'pdf'])           ->name('pdf');
            Route::get('/excel',            [CahierAppelController::class, 'excel'])         ->name('excel');
            Route::post('/',                [CahierAppelController::class, 'storeDirect'])   ->name('store');
            Route::post('/import-excel',    [CahierAppelController::class, 'importExcel'])   ->name('import-excel');
            Route::get('/import-ocr',       [CahierAppelController::class, 'importOcrForm']) ->name('import-ocr.form');
            Route::post('/import-ocr',     [CahierAppelController::class, 'importOcrPreview'])->name('import-ocr.preview');
            Route::post('/import-ocr/confirm', [CahierAppelController::class, 'importOcrConfirm'])->name('import-ocr.confirm');
        });

        // ── Feuille de note (PDF / Excel / OCR) ──
        Route::prefix('classes/{classe}/feuille-de-note')->name('feuille-de-note.')->group(function () {
            Route::get('/',               [FeuilleDeNoteController::class, 'index'])           ->name('index');
            Route::get('/pdf',            [FeuilleDeNoteController::class, 'pdf'])             ->name('pdf');
            Route::get('/excel',          [FeuilleDeNoteController::class, 'excel'])           ->name('excel');
            Route::get('/import-excel',   [FeuilleDeNoteController::class, 'importExcelForm']) ->name('import-excel.form');
            Route::post('/import-excel',  [FeuilleDeNoteController::class, 'importExcel'])     ->name('import-excel');
            Route::get('/import-ocr',     [FeuilleDeNoteController::class, 'importOcrForm'])   ->name('import-ocr.form');
            Route::post('/import-ocr',    [FeuilleDeNoteController::class, 'importOcrPreview'])->name('import-ocr.preview');
            Route::post('/import-ocr/confirm', [FeuilleDeNoteController::class, 'importOcrConfirm'])->name('import-ocr.confirm');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Portail Élève — Mon espace élève
    |--------------------------------------------------------------------------
    */
    Route::prefix('mon-espace-eleve')->name('mon-espace-eleve.')->group(function () {
        Route::get('/',                              [ElevePortalController::class, 'dashboard']) ->name('dashboard');
        Route::get('/notes',                         [ElevePortalController::class, 'notes'])     ->name('notes');
        Route::get('/devoirs',                       [ElevePortalController::class, 'devoirs'])   ->name('devoirs');
        Route::get('/evaluations',                   [ElevePortalController::class, 'evaluations'])->name('evaluations');
        Route::get('/devoirs/{devoir}/sujet',        [ElevePortalController::class, 'downloadDevoirSujet'])  ->name('devoirs.sujet');
        Route::get('/devoirs/{devoir}/corrige',      [ElevePortalController::class, 'downloadDevoirCorrige'])->name('devoirs.corrige');
        Route::get('/evaluations/{evaluation}/sujet',[ElevePortalController::class, 'downloadEvalSujet'])    ->name('evaluation.sujet');
        Route::get('/evaluations/{evaluation}/corrige',[ElevePortalController::class, 'downloadEvalCorrige'])->name('evaluation.corrige');
    });

    // ── Portail Parent ──────────────────────────────────────────────────────
    Route::prefix('mon-espace-parent')->name('mon-espace-parent.')->group(function () {
        Route::get('/',                                   [\App\Http\Controllers\ParentPortalController::class, 'dashboard'])  ->name('dashboard');
        Route::get('/enfants/{eleve}/notes',              [\App\Http\Controllers\ParentPortalController::class, 'notes'])      ->name('notes');
        Route::get('/enfants/{eleve}/paiements',          [\App\Http\Controllers\ParentPortalController::class, 'paiements'])  ->name('paiements');
        Route::post('/enfants/{eleve}/paiements/wave',   [\App\Http\Controllers\ParentPortalController::class, 'genererLienWave'])->name('paiements.wave');
        Route::get('/enfants/{eleve}/presences',          [\App\Http\Controllers\ParentPortalController::class, 'presences'])  ->name('presences');
    });
});

/*
|--------------------------------------------------------------------------
| Imports élèves (dédupliqué)
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth',
    'role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur'
])->prefix('eleves/import')->name('eleves.import.')->group(function () {

    Route::get('/', [EleveImportController::class, 'index'])->name('index');
    Route::get('/template', [EleveImportController::class, 'telechargerTemplate'])->name('template');

    Route::get('/excel', [EleveImportExcelController::class, 'showForm'])->name('excel.form');
    Route::post('/excel', [EleveImportExcelController::class, 'upload'])->name('excel.upload');

    Route::get('/saisie-rapide', [EleveImportSaisieController::class, 'showForm'])->name('saisie.form');
    Route::post('/saisie-rapide', [EleveImportSaisieController::class, 'submit'])->name('saisie.submit');

    Route::get('/pdf', [EleveImportPdfController::class, 'showForm'])->name('pdf.form');
    Route::post('/pdf', [EleveImportPdfController::class, 'upload'])->name('pdf.upload');

    // IMPORTANT : route spécifique avant /photo et avant /{job}/preview
    Route::get('/photo/diagnostic', [EleveImportPhotoController::class, 'diagnostic'])->name('photo.diagnostic');
    Route::get('/photo', [EleveImportPhotoController::class, 'showForm'])->name('photo.form');
    Route::post('/photo', [EleveImportPhotoController::class, 'upload'])->name('photo.upload');

    Route::get('/{job}/preview', [EleveImportController::class, 'preview'])->name('preview');
    Route::put('/{job}/preview', [EleveImportController::class, 'updatePreview'])->name('preview.update');

    Route::post('/{job}/confirmer', [EleveImportController::class, 'confirmer'])->name('confirmer');
    Route::post('/{job}/annuler', [EleveImportController::class, 'annuler'])->name('annuler');
});

/*
|--------------------------------------------------------------------------
| RH Admin
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth',
    'role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur'
])->prefix('admin/rh')->name('admin.rh.')->group(function () {

    Route::get('/dashboard', [RhDashboardController::class, 'index'])->name('dashboard');

    Route::get('/enseignants', fn () => redirect()->route('enseignants.index'))
        ->name('enseignants.index');

    Route::get('/affectations', [AffectationAdminController::class, 'index'])->name('affectations.index');
    Route::get('/affectations/create', [AffectationAdminController::class, 'create'])->name('affectations.create');
    Route::post('/affectations', [AffectationAdminController::class, 'store'])->name('affectations.store');
    Route::get('/affectations/{affectation}/edit', [AffectationAdminController::class, 'edit'])->name('affectations.edit');
    Route::put('/affectations/{affectation}', [AffectationAdminController::class, 'update'])->name('affectations.update');
    Route::delete('/affectations/{affectation}', [AffectationAdminController::class, 'destroy'])->name('affectations.destroy');

    Route::get('/pointages', [PointageAdminController::class, 'index'])->name('pointages.index');
    Route::get('/pointages/{pointage}', [PointageAdminController::class, 'show'])->name('pointages.show');
    Route::get('/pointages/{pointage}/selfie', [PointageAdminController::class, 'selfie'])->name('pointages.selfie');
    Route::get('/pointages/{pointage}/cahier-texte', [PointageAdminController::class, 'cahierTexte'])->name('pointages.cahier-texte');

    // ── QR Codes de pointage ──
    Route::get('/qr-codes',                          [QrCodeAdminController::class, 'index'])      ->name('qr-codes.index');
    Route::get('/qr-codes/pdf-poster',               [QrCodeAdminController::class, 'pdfPoster']) ->name('qr-codes.pdf-poster');
    Route::get('/qr-codes/{qrCode}/image',           [QrCodeAdminController::class, 'image'])     ->name('qr-codes.image');
    Route::post('/qr-codes/generate-all',            [QrCodeAdminController::class, 'generateAll'])->name('qr-codes.generate-all');
    Route::post('/qr-codes/salles/{salle}/generate', [QrCodeAdminController::class, 'generate'])  ->name('qr-codes.regenerate');
    Route::post('/qr-codes/{qrCode}/deactivate',     [QrCodeAdminController::class, 'deactivate'])->name('qr-codes.deactivate');

    // ── Grille des moyennes (lecture seule) ──
    Route::get('/moyennes-grille', [MoyennesGrilleAdminController::class, 'index'])->name('moyennes-grille.index');

    // ── Bulletins (direction) ──
    Route::get('/bulletins',                    [BulletinAdminController::class, 'index'])    ->name('bulletins.index');
    Route::post('/bulletins/calculer',          [BulletinAdminController::class, 'calculer']) ->name('bulletins.calculer');
    Route::get('/bulletins/{eleve}/{trimestre}',[BulletinAdminController::class, 'pdf'])      ->name('bulletins.pdf');
    Route::post('/bulletins/pdf-classe',        [BulletinAdminController::class, 'pdfClasse'])->name('bulletins.pdf-classe');
    Route::post('/bulletins/pdf-masse',         [BulletinAdminController::class, 'pdfMasse']) ->name('bulletins.pdf-masse');

    // ── Système d'évaluation (trimestre/semestre/quadrimestre) ──
    Route::get('/evaluation-system',           [EvaluationSystemController::class, 'index'])      ->name('evaluation-system.index');
    Route::post('/evaluation-system',          [EvaluationSystemController::class, 'update'])     ->name('evaluation-system.update');
    Route::post('/evaluation-system/coefs',    [EvaluationSystemController::class, 'updateCoefs'])->name('evaluation-system.update-coefs');
    Route::post('/evaluation-system/regenerer',[EvaluationSystemController::class, 'regenerer']) ->name('evaluation-system.regenerer');

    // ── Disciplines (matières racines) ──
    Route::get('/disciplines',                          [DisciplinesController::class, 'index'])    ->name('disciplines.index');
    Route::post('/disciplines',                         [DisciplinesController::class, 'store'])    ->name('disciplines.store');
    Route::patch('/disciplines/{matiere}',              [DisciplinesController::class, 'update'])   ->name('disciplines.update');
    Route::post('/disciplines/{matiere}/toggle',        [DisciplinesController::class, 'toggle'])   ->name('disciplines.toggle');
    Route::delete('/disciplines/{matiere}',             [DisciplinesController::class, 'destroy'])  ->name('disciplines.destroy');

    // ── Sous-disciplines (système ivoirien : CF/OG/EO pour Français, etc.) ──
    Route::get('/sous-disciplines',                     [SousDisciplinesController::class, 'index'])         ->name('sous-disciplines.index');
    Route::post('/sous-disciplines',                    [SousDisciplinesController::class, 'store'])         ->name('sous-disciplines.store');
    Route::patch('/sous-disciplines/{matiere}',         [SousDisciplinesController::class, 'update'])        ->name('sous-disciplines.update');
    Route::delete('/sous-disciplines/{matiere}',        [SousDisciplinesController::class, 'destroy'])       ->name('sous-disciplines.destroy');
    Route::post('/sous-disciplines/preset-francais',    [SousDisciplinesController::class, 'presetFrancais'])->name('sous-disciplines.preset-francais');

    // ── Présences élèves (direction / éducateur) ──
    Route::get('/presences',                       [PresenceEleveAdminController::class, 'dashboard'])  ->name('presences.dashboard');
    Route::get('/presences/liste',                 [PresenceEleveAdminController::class, 'index'])      ->name('presences.index');
    Route::get('/presences/eleves/{eleve}',        [PresenceEleveAdminController::class, 'showEleve'])  ->name('presences.eleve');
    Route::patch('/presences/{presence}/justifier',[PresenceEleveAdminController::class, 'justifier']) ->name('presences.justifier');
    Route::patch('/presences/{presence}/traiter',  [PresenceEleveAdminController::class, 'traiter'])   ->name('presences.traiter');

    // ── Bilans présences (trimestre/année) ──
    Route::get('/presences/bilan',                       [PresenceBilanController::class, 'dashboard'])     ->name('presences.bilan');
    Route::get('/presences/bilan/classe/{classe}',       [PresenceBilanController::class, 'bilanClasse'])   ->name('presences.bilan.classe');
    Route::get('/presences/bilan/eleve/{eleve}',         [PresenceBilanController::class, 'bilanEleve'])    ->name('presences.bilan.eleve');
    Route::get('/presences/bilan/api/classe/{classe}',   [PresenceBilanController::class, 'apiBilanClasse'])->name('presences.bilan.api.classe');
    Route::get('/presences/bilan/api/eleve/{eleve}',     [PresenceBilanController::class, 'apiBilanEleve']) ->name('presences.bilan.api.eleve');
    Route::get('/presences/bilan/api/etablissement',     [PresenceBilanController::class, 'apiBilanEtablissement'])->name('presences.bilan.api.etablissement');

    Route::get('/alertes', [AlertePointageAdminController::class, 'index'])->name('alertes.index');
    Route::get('/alertes/{alerte}', [AlertePointageAdminController::class, 'show'])->name('alertes.show');
    Route::patch('/alertes/{alerte}/lire', [AlertePointageAdminController::class, 'marquerLue'])->name('alertes.lire');
    Route::patch('/alertes/{alerte}/traiter', [AlertePointageAdminController::class, 'traiter'])->name('alertes.traiter');
});
