<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Api\PointageController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\EmploiDuTemps;
use App\Models\Pointage;
use App\Models\Salle;
use App\Services\Pointage\CahierTexteOcrService;
use App\Services\Pointage\PointageScanService;
use App\Support\ApiEnvelope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherPointageController extends Controller
{
    use ResolvesTeacherContext;

    public function today(Request $request): JsonResponse
    {
        $ens = $this->enseignant($request);

        $rows = Pointage::where('enseignant_id', $ens->id)
            ->where('date', today())
            ->with([
                'salle:id,nom',
                'qrCode:id,code_unique',
                'emploiDuTemps.creneau',
                'emploiDuTemps.matiere:id,nom',
                'emploiDuTemps.classe:id,nom',
            ])
            ->orderBy('heure_scan')
            ->get();

        $enAttente = $rows->where('cahier_texte_status', Pointage::CAHIER_EN_ATTENTE)->values();

        return ApiEnvelope::success([
            'pointages' => $rows,
            'cahier_en_attente' => $enAttente,
        ], 'Pointages du jour.');
    }

    public function scanQr(Request $request, PointageController $legacy, PointageScanService $scanService): JsonResponse
    {
        $this->enseignant($request);

        $request->validate([
            'qr_code' => 'required_without:code_qr|string',
            'code_qr' => 'required_without:qr_code|string',
            'latitude' => 'required_without:gps_latitude|numeric|between:-90,90',
            'longitude' => 'required_without:gps_longitude|numeric|between:-180,180',
            'gps_latitude' => 'sometimes|numeric|between:-90,90',
            'gps_longitude' => 'sometimes|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'type' => 'required_without:type_scan|in:arrivee,sortie',
            'type_scan' => 'sometimes|in:arrivee,depart',
        ]);

        $typeApi = $request->input('type');
        $typeScan = $request->input('type_scan');
        if ($typeScan === null) {
            $typeScan = ($typeApi === 'sortie') ? 'depart' : 'arrivee';
        }

        $request->merge([
            'code_qr' => $request->input('qr_code', $request->input('code_qr')),
            'gps_latitude' => $request->input('latitude', $request->input('gps_latitude')),
            'gps_longitude' => $request->input('longitude', $request->input('gps_longitude')),
            'gps_precision' => $request->input('accuracy', $request->input('gps_precision')),
            'type_scan' => $typeScan,
        ]);

        $legacyResponse = $legacy->scannerQrCode($request, $scanService);
        $status = $legacyResponse->getStatusCode();
        $data = $legacyResponse->getData(true);
        if (! is_array($data)) {
            $data = [];
        }

        if (isset($data['error'])) {
            $code = str_contains((string) ($data['error'] ?? ''), 'QR') ? 422 : $status;

            return ApiEnvelope::fail((string) $data['error'], [], $code >= 400 ? $code : 422);
        }

        $pointageArr = $data['pointage'] ?? null;
        $pointageStatut = is_array($pointageArr)
            ? ($pointageArr['statut'] ?? null)
            : (is_object($pointageArr) ? ($pointageArr->statut ?? null) : null);

        if ($status === 422 && $pointageStatut === 'hors_zone') {
            return ApiEnvelope::fail('Vous êtes hors du périmètre autorisé.', [], 403);
        }

        if (($data['success'] ?? false) !== true || $status >= 400) {
            $msg = $data['message'] ?? 'Pointage refusé.';

            return ApiEnvelope::fail(is_string($msg) ? $msg : 'Pointage refusé.', [], $status >= 400 ? $status : 422);
        }

        $pid = is_array($pointageArr) ? ($pointageArr['id'] ?? null) : (is_object($pointageArr) ? ($pointageArr->id ?? null) : null);
        $pointage = $pid ? Pointage::with('salle')->find($pid) : null;
        $ts = is_array($pointageArr) ? ($pointageArr['type_scan'] ?? 'arrivee') : ($pointage?->type_scan ?? 'arrivee');

        return ApiEnvelope::success([
            'pointage_id' => $pointage?->id ?? (is_array($pointageArr) ? ($pointageArr['id'] ?? null) : null),
            'statut' => $pointage?->statut ?? (string) $pointageStatut,
            'type' => $ts === 'depart' ? 'sortie' : 'arrivee',
            'heure' => $pointage?->heure_scan ?? (is_array($pointageArr) ? ($pointageArr['heure_scan'] ?? null) : null),
            'salle' => $pointage?->salle?->only(['id', 'nom']) ?? new \stdClass,
            'distance_m' => (int) round((float) ($data['distance_metres'] ?? $pointage?->distance_ecole_metres ?? 0)),
            'conforme_emploi_temps' => $data['conforme_emploi_temps'] ?? null,
            'edt' => $data['edt'] ?? null,
            'validation_finale' => $pointage?->validation_finale,
            'cahier_texte_status' => $pointage?->cahier_texte_status,
            'cahier_texte_deadline_at' => $pointage?->cahier_texte_deadline_at?->toIso8601String(),
            'cahier_texte_validated' => (bool) ($pointage?->cahier_texte_validated ?? false),
            'requires_cahier_texte' => $pointage?->cahier_texte_status === Pointage::CAHIER_EN_ATTENTE,
            'can_defer_cahier' => $pointage && $pointage->cahier_texte_status === Pointage::CAHIER_EN_ATTENTE,
        ], is_string($data['message'] ?? null) ? $data['message'] : 'Pointage enregistré avec succès');
    }

    public function deferCahier(Request $request, Pointage $pointage): JsonResponse
    {
        $ens = $this->enseignant($request);
        abort_unless((int) $pointage->enseignant_id === (int) $ens->id, 403);

        if ($pointage->cahier_texte_status !== Pointage::CAHIER_EN_ATTENTE) {
            return ApiEnvelope::fail('Le cahier de texte ne peut plus être reporté pour ce pointage.', [], 422);
        }

        $pointage->cahier_texte_deadline_at = now()->copy()->setTime(18, 30, 0);
        $pointage->syncValidationFinale();
        $pointage->save();

        return ApiEnvelope::success([
            'pointage_id' => $pointage->id,
            'cahier_texte_status' => $pointage->cahier_texte_status,
            'cahier_texte_deadline_at' => $pointage->cahier_texte_deadline_at->toIso8601String(),
            'validation_finale' => $pointage->validation_finale,
        ], 'Vous pourrez ajouter le cahier de texte avant 18h30.');
    }

    /**
     * Valide un pointage en analysant une photo du cahier de texte.
     * Étapes :
     *   1. Upload de l'image
     *   2. OCR via OpenAI Vision (date, créneau, contenu)
     *   3. Croisement avec l'EDT du prof
     *   4. Marquage du pointage comme validé si tout concorde
     */
    public function validateCahierTexte(
        Request $request,
        Pointage $pointage,
        CahierTexteOcrService $ocr
    ): JsonResponse {
        $ens = $this->enseignant($request);
        abort_unless((int) $pointage->enseignant_id === (int) $ens->id, 403, 'Pointage non autorisé.');

        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $disk = 'public';
        $path = $request->file('image')->store(
            "cahier-texte/{$pointage->etablissement_id}/{$pointage->id}",
            $disk
        );

        try {
            $extracted = $ocr->extract($disk, $path);
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            return ApiEnvelope::fail('Échec OCR : ' . $e->getMessage(), [], 500);
        }

        // Validation : croiser avec l'EDT du prof
        $validation = $this->validateAgainstEdt($pointage, $extracted, $ens);

        // Persister
        $pointage->cahier_texte_path = $path;
        $pointage->cahier_texte_data = array_merge($extracted, ['validation' => $validation]);
        $pointage->cahier_texte_confidence = $extracted['confidence'] ?? 0;
        $pointage->cahier_texte_validated = $validation['valide'];
        $pointage->cahier_texte_validated_at = $validation['valide'] ? now() : null;
        $pointage->cahier_texte_status = $validation['valide'] ? Pointage::CAHIER_VALIDE : Pointage::CAHIER_REFUSE;
        if ($validation['valide']) {
            $pointage->conforme_emploi_temps = true;
        }
        $pointage->syncValidationFinale();
        $pointage->save();

        return ApiEnvelope::success([
            'pointage_id'           => $pointage->id,
            'extracted'             => $extracted,
            'validation'            => $validation,
            'cahier_texte_path'     => $path,
            'cahier_texte_validated' => $validation['valide'],
            'cahier_texte_status'   => $pointage->cahier_texte_status,
            'validation_finale'     => $pointage->validation_finale,
            'statut'                => $pointage->statut,
        ], $validation['valide']
            ? 'Pointage validé : le cahier de texte correspond à votre emploi du temps.'
            : 'Vérification effectuée mais validation impossible : ' . ($validation['raisons'][0] ?? 'incohérence détectée.'));
    }

    /**
     * Croise les données OCR du cahier de texte avec l'EDT du prof.
     *
     * @return array{
     *   valide: bool,
     *   raisons: array<string>,
     *   edt_match: ?array,
     *   score: int
     * }
     */
    private function validateAgainstEdt(Pointage $pointage, array $extracted, \App\Models\Enseignant $ens): array
    {
        $raisons = [];
        $score = 0;
        $edtMatch = null;

        // 1. Date doit correspondre au pointage (même jour)
        $datePointage = Carbon::parse($pointage->date)->toDateString();
        if (! empty($extracted['date'])) {
            if ($extracted['date'] === $datePointage) {
                $score += 25;
            } else {
                $raisons[] = "Date du cahier ({$extracted['date']}) ≠ date du pointage ({$datePointage}).";
            }
        } else {
            $raisons[] = 'Date illisible sur le cahier de texte.';
        }

        // 2. Créneau extrait correspond au cours pointé ou à l'EDT du jour
        $hd = $extracted['creneau']['heure_debut'] ?? null;
        $hf = $extracted['creneau']['heure_fin'] ?? null;
        if ($hd && $hf) {
            $jourFr = PointageScanService::jourFrancais(Carbon::parse($datePointage));
            $edtQuery = EmploiDuTemps::where('enseignant_id', $ens->id)
                ->where('jour', $jourFr)
                ->where('actif', true)
                ->with(['creneau', 'matiere:id,nom,code', 'classe:id,nom']);

            if ($pointage->emploi_du_temps_id) {
                $edtPrioritaire = (clone $edtQuery)->where('id', $pointage->emploi_du_temps_id)->first();
            } else {
                $edtPrioritaire = null;
            }

            $edt = $edtQuery->get();

            $match = $edtPrioritaire && $this->creneauOcrCorrespond($edtPrioritaire, $hd, $hf)
                ? $edtPrioritaire
                : $edt->first(function ($e) use ($hd, $hf) {
                $c = $e->creneau;
                if (! $c) return false;
                $cHd = substr((string) $c->heure_debut, 0, 5);
                $cHf = substr((string) $c->heure_fin, 0, 5);
                // tolérance 10 min
                return $this->timeNear($cHd, $hd, 10) && $this->timeNear($cHf, $hf, 10);
            });

            if ($match) {
                $score += 40;
                $edtMatch = [
                    'creneau'    => $match->creneau ? [
                        'id'          => $match->creneau->id,
                        'libelle'     => $match->creneau->libelle,
                        'heure_debut' => substr($match->creneau->heure_debut, 0, 5),
                        'heure_fin'   => substr($match->creneau->heure_fin, 0, 5),
                    ] : null,
                    'matiere'    => $match->matiere?->only(['id', 'nom', 'code']),
                    'classe'     => $match->classe?->only(['id', 'nom']),
                ];

                // 3. Matière dans le cahier correspond à la matière prévue ?
                if (! empty($extracted['matiere']) && $match->matiere) {
                    $ocrMat = strtolower(\Illuminate\Support\Str::ascii($extracted['matiere']));
                    $edtMat = strtolower(\Illuminate\Support\Str::ascii($match->matiere->nom));
                    $edtCode = strtolower(\Illuminate\Support\Str::ascii($match->matiere->code ?? ''));
                    if (str_contains($ocrMat, $edtMat) || str_contains($edtMat, $ocrMat)
                        || ($edtCode && str_contains($ocrMat, $edtCode))) {
                        $score += 20;
                    } else {
                        $raisons[] = "Matière du cahier ({$extracted['matiere']}) ≠ matière prévue ({$match->matiere->nom}).";
                    }
                }

                // 4. Classe correspond ?
                if (! empty($extracted['classe']) && $match->classe) {
                    $ocrClasse = strtolower(\Illuminate\Support\Str::ascii($extracted['classe']));
                    $edtClasse = strtolower(\Illuminate\Support\Str::ascii($match->classe->nom));
                    if (str_contains($ocrClasse, $edtClasse) || str_contains($edtClasse, $ocrClasse)) {
                        $score += 15;
                    } else {
                        $raisons[] = "Classe du cahier ({$extracted['classe']}) ≠ classe prévue ({$match->classe->nom}).";
                    }
                }
            } else {
                $raisons[] = "Aucun cours dans votre EDT à {$hd}-{$hf} ce jour.";
            }
        } else {
            $raisons[] = 'Créneau illisible sur le cahier de texte.';
        }

        // 5. Confiance OCR minimale 60% pour valider
        $confidence = $extracted['confidence'] ?? 0;
        if ($confidence < 50) {
            $raisons[] = "Qualité de l'image insuffisante (confiance OCR : {$confidence}%).";
        }

        // Valide si score >= 65 ET pas de raison bloquante majeure
        $valide = $score >= 65 && $confidence >= 50;

        return [
            'valide'    => $valide,
            'raisons'   => $raisons,
            'edt_match' => $edtMatch,
            'score'     => $score,
        ];
    }

    private function creneauOcrCorrespond(EmploiDuTemps $edt, string $hd, string $hf): bool
    {
        $c = $edt->creneau;
        if (! $c) {
            return false;
        }

        $cHd = substr((string) $c->heure_debut, 0, 5);
        $cHf = substr((string) $c->heure_fin, 0, 5);

        return $this->timeNear($cHd, $hd, 10) && $this->timeNear($cHf, $hf, 10);
    }

    private function timeNear(string $a, string $b, int $toleranceMin = 10): bool
    {
        try {
            $ta = Carbon::parse($a);
            $tb = Carbon::parse($b);
            return abs($ta->diffInMinutes($tb)) <= $toleranceMin;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
