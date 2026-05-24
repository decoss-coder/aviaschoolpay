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
use App\Http\Controllers\Admin\AffectationRapideController;
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

require __DIR__.'/web-main-body.php';
