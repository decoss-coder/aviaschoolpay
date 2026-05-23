<?php
// ══════════════════════════════════════════════════════════════
// routes/api.php — AviaSchoolPay API Routes (Laravel 11)
// ══════════════════════════════════════════════════════════════

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    DashboardController,
    EleveController,
    EnseignantController,
    PointageController,
    NoteController,
    PaiementController,
    SigfneController,
    ClasseController,
    EmploiDuTempsController,
    CommunicationController,
    EtablissementController,
};

// ══════════════════════════════════════════
// AUTHENTIFICATION (publique)
// ══════════════════════════════════════════
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login/telephone', [AuthController::class, 'loginTelephone']);
    Route::post('mot-de-passe/oublie', [AuthController::class, 'motDePasseOublie']);
    Route::post('mot-de-passe/reinitialiser', [AuthController::class, 'reinitialiserMotDePasse']);
});

// ══════════════════════════════════════════
// WEBHOOK PAYDUNYA (publique, signée)
// ══════════════════════════════════════════
Route::post('paiements/callback/paydunya', [PaiementController::class, 'callbackPayDunya'])
    ->name('api.paiements.callback.paydunya');

// ══════════════════════════════════════════
// ROUTES PROTÉGÉES (Sanctum)
// ══════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profil', [AuthController::class, 'profil']);
    Route::put('auth/profil', [AuthController::class, 'updateProfil']);
    Route::put('auth/mot-de-passe', [AuthController::class, 'changerMotDePasse']);

    // ══════════════════════════════════════
    // MODULE 1 — TABLEAU DE BORD
    // ══════════════════════════════════════
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/stats-financieres', [DashboardController::class, 'statsFinancieres']);
    Route::get('dashboard/stats-pedagogiques', [DashboardController::class, 'statsPedagogiques']);

    // ══════════════════════════════════════
    // MODULE 2 — GESTION DES ÉLÈVES
    // ══════════════════════════════════════
    Route::apiResource('eleves', EleveController::class);
    Route::get('eleves/{eleve}/fiche-complete', [EleveController::class, 'ficheComplete']);
    Route::get('eleves/{eleve}/solde', [EleveController::class, 'soldeScolarite']);
    Route::get('eleves/{eleve}/bulletins', [EleveController::class, 'bulletins']);
    Route::get('eleves/{eleve}/presences', [EleveController::class, 'presences']);
    Route::post('eleves/recherche-desps', [EleveController::class, 'rechercheDESPS']);
    Route::post('eleves/{eleve}/documents', [EleveController::class, 'uploaderDocument']);

    // ── Parents ──
    Route::apiResource('parents', 'App\Http\Controllers\Api\ParentController');
    Route::get('parents/{parent}/enfants', 'App\Http\Controllers\Api\ParentController@enfants');

    // ── Inscriptions ──
    Route::apiResource('inscriptions', 'App\Http\Controllers\Api\InscriptionController');
    Route::post('inscriptions/{inscription}/valider', 'App\Http\Controllers\Api\InscriptionController@valider');

    // ── Présences élèves ──
    Route::post('presences/saisir', 'App\Http\Controllers\Api\PresenceController@saisir');
    Route::get('presences/classe/{classe}/date/{date}', 'App\Http\Controllers\Api\PresenceController@parClasseDate');

    // ══════════════════════════════════════
    // MODULE 3 — GESTION DES ENSEIGNANTS
    // ══════════════════════════════════════
    Route::apiResource('enseignants', EnseignantController::class);
    Route::get('enseignants/{enseignant}/classes', [EnseignantController::class, 'classes']);
    Route::get('enseignants/{enseignant}/pointages', [PointageController::class, 'historique']);
    Route::get('enseignants/{enseignant}/paie', [EnseignantController::class, 'fichePaie']);

    // ── Affectations ──
    Route::apiResource('affectations', 'App\Http\Controllers\Api\AffectationController');

    // ── Congés / Permissions ──
    Route::apiResource('conges', 'App\Http\Controllers\Api\CongeController');
    Route::post('conges/{conge}/approuver', 'App\Http\Controllers\Api\CongeController@approuver');
    Route::post('conges/{conge}/refuser', 'App\Http\Controllers\Api\CongeController@refuser');

    // ══════════════════════════════════════
    // MODULE 4 — POINTAGE QR CODE + GPS
    // ══════════════════════════════════════
    Route::prefix('pointage')->group(function () {
        Route::post('scanner-qr', [PointageController::class, 'scannerQrCode']);
        Route::post('scanner-pin', [PointageController::class, 'scannerPin']);
        Route::get('aujourdhui', [PointageController::class, 'pointagesDuJour']);
        Route::get('historique/{enseignant}', [PointageController::class, 'historique']);
        Route::get('alertes', [PointageController::class, 'alertes']);
        Route::post('alertes/{alerte}/traiter', [PointageController::class, 'traiterAlerte']);
        Route::post('generer-qr-codes', [PointageController::class, 'genererQrCodes']);
        Route::post('generer-pin', [PointageController::class, 'genererPinJournalier']);
        Route::get('rapport-mensuel', [PointageController::class, 'rapportMensuel']);
    });

    // ══════════════════════════════════════
    // MODULE 5 — NOTES ET BULLETINS
    // ══════════════════════════════════════
    Route::prefix('notes')->group(function () {
        Route::get('classe/{classe}', [NoteController::class, 'evaluationsParClasse']);
        Route::post('evaluations', [NoteController::class, 'creerEvaluation']);
        Route::put('evaluations/{evaluation}', [NoteController::class, 'updateEvaluation']);
        Route::post('evaluations/{evaluation}/saisir', [NoteController::class, 'saisirNotes']);
        Route::post('evaluations/{evaluation}/cloturer', [NoteController::class, 'cloturerEvaluation']);
        Route::post('moyennes/calculer/{classe}', [NoteController::class, 'calculerMoyennes']);
        Route::get('bulletin/{eleve}', [NoteController::class, 'bulletinEleve']);
        Route::post('bulletin/{eleve}/generer-pdf', [NoteController::class, 'genererBulletinPdf']);
    });

    // ══════════════════════════════════════
    // MODULE 6 — PAIEMENTS PAYDUNYA
    // ══════════════════════════════════════
    Route::prefix('paiements')->group(function () {
        Route::get('/', [PaiementController::class, 'index']);
        Route::post('mobile', [PaiementController::class, 'initierPaiementMobile']);
        Route::post('especes', [PaiementController::class, 'enregistrerEspeces']);
        Route::get('recouvrement', [PaiementController::class, 'recouvrement']);
        Route::get('{paiement}', [PaiementController::class, 'show']);
        Route::get('{paiement}/recu', [PaiementController::class, 'telechargerRecu']);
        Route::post('relance/{inscription}', [PaiementController::class, 'envoyerRelance']);
        Route::get('relances/historique', [PaiementController::class, 'historiqueRelances']);

        // Échéances
        Route::get('echeances/{inscription}', [PaiementController::class, 'echeancesEleve']);
        Route::post('echeances/generer/{inscription}', [PaiementController::class, 'genererEcheances']);
    });

    // ══════════════════════════════════════
    // MODULE 7 — EMPLOI DU TEMPS
    // ══════════════════════════════════════
    Route::prefix('emploi-du-temps')->group(function () {
        Route::get('classe/{classe}', [EmploiDuTempsController::class, 'parClasse']);
        Route::get('enseignant/{enseignant}', [EmploiDuTempsController::class, 'parEnseignant']);
        Route::get('salle/{salle}', [EmploiDuTempsController::class, 'parSalle']);
        Route::post('/', [EmploiDuTempsController::class, 'store']);
        Route::put('{emploiDuTemps}', [EmploiDuTempsController::class, 'update']);
        Route::delete('{emploiDuTemps}', [EmploiDuTempsController::class, 'destroy']);
        Route::get('conflits', [EmploiDuTempsController::class, 'detecterConflits']);
    });

    // ── Créneaux ──
    Route::apiResource('creneaux', 'App\Http\Controllers\Api\CreneauController');

    // ══════════════════════════════════════
    // MODULE 8 — COMMUNICATION
    // ══════════════════════════════════════
    Route::prefix('communication')->group(function () {
        Route::apiResource('messages', CommunicationController::class);
        Route::get('messages/non-lus/count', [CommunicationController::class, 'countNonLus']);
        Route::post('messages/{message}/lire', [CommunicationController::class, 'marquerLu']);
        Route::apiResource('annonces', 'App\Http\Controllers\Api\AnnonceController');
        Route::post('annonces/{annonce}/publier', 'App\Http\Controllers\Api\AnnonceController@publier');
        Route::get('notifications', [CommunicationController::class, 'notifications']);
        Route::post('notifications/lire-tout', [CommunicationController::class, 'lireToutNotifications']);
        Route::post('sms/envoyer', [CommunicationController::class, 'envoyerSms']);
    });

    // ══════════════════════════════════════
    // MODULE 9 — IA AIDE À LA DÉCISION
    // ══════════════════════════════════════
    Route::prefix('ia')->group(function () {
        Route::get('predictions', 'App\Http\Controllers\Api\IAController@predictions');
        Route::get('score-sante', 'App\Http\Controllers\Api\IAController@scoreSante');
        Route::post('chatbot', 'App\Http\Controllers\Api\IAController@chatbot');
        Route::get('eleves-a-risque', 'App\Http\Controllers\Api\IAController@elevesARisque');
        Route::get('prevision-recouvrement', 'App\Http\Controllers\Api\IAController@previsionRecouvrement');
        Route::get('prevision-examens', 'App\Http\Controllers\Api\IAController@previsionExamens');
    });

    // ══════════════════════════════════════
    // MODULE 10 — MULTI-ÉTABLISSEMENTS
    // ══════════════════════════════════════
    Route::prefix('groupe')->middleware('role:super_admin')->group(function () {
        Route::get('etablissements', [EtablissementController::class, 'index']);
        Route::get('consolidation-financiere', [EtablissementController::class, 'consolidationFinanciere']);
        Route::get('comparaison-performances', [EtablissementController::class, 'comparaisonPerformances']);
    });

    // ══════════════════════════════════════
    // MODULE 11 — CONFORMITÉ SIGFNE / DESPS
    // ══════════════════════════════════════
    Route::prefix('sigfne')->group(function () {
        Route::post('preparer-remontee', [SigfneController::class, 'preparerRemontee']);
        Route::post('executer-remontee', [SigfneController::class, 'executerRemontee']);
        Route::get('exporter-csv', [SigfneController::class, 'exporterCSV']);
        Route::get('historique-remontees', [SigfneController::class, 'historiqueRemontees']);
        Route::get('remontees/{remontee}', [SigfneController::class, 'detailRemontee']);
        Route::post('dfa/generer', [SigfneController::class, 'genererDFA']);
        Route::post('dfa/{dfa}/valider-pp', [SigfneController::class, 'validerDfaPP']);
        Route::post('dfa/{dfa}/valider-directeur', [SigfneController::class, 'validerDfaDirecteur']);
        Route::post('dfa/soumettre-sigfne', [SigfneController::class, 'soumettreDfaSigfne']);
        Route::get('calendrier', [SigfneController::class, 'calendrierRemontees']);
    });

    // ══════════════════════════════════════
    // STRUCTURE PÉDAGOGIQUE (transversal)
    // ══════════════════════════════════════
    Route::apiResource('classes', ClasseController::class);
    Route::apiResource('niveaux', 'App\Http\Controllers\Api\NiveauController');
    Route::apiResource('matieres', 'App\Http\Controllers\Api\MatiereController');
    Route::apiResource('salles', 'App\Http\Controllers\Api\SalleController');
    Route::apiResource('annees-scolaires', 'App\Http\Controllers\Api\AnneeScolaireController');
    Route::apiResource('trimestres', 'App\Http\Controllers\Api\TrimestreController');

    // ══════════════════════════════════════
    // PARAMÈTRES & CONFIGURATION
    // ══════════════════════════════════════
    Route::prefix('config')->middleware('role:directeur,super_admin')->group(function () {
        Route::get('etablissement', [EtablissementController::class, 'show']);
        Route::put('etablissement', [EtablissementController::class, 'update']);
        Route::put('etablissement/gps', [EtablissementController::class, 'updateGps']);
        Route::get('parametres', [EtablissementController::class, 'parametres']);
        Route::put('parametres', [EtablissementController::class, 'updateParametres']);
        Route::get('types-evaluation', 'App\Http\Controllers\Api\TypeEvaluationController@index');
        Route::post('types-evaluation', 'App\Http\Controllers\Api\TypeEvaluationController@store');
        Route::get('plans-paiement', 'App\Http\Controllers\Api\PlanPaiementController@index');
        Route::post('plans-paiement', 'App\Http\Controllers\Api\PlanPaiementController@store');
    });
});
