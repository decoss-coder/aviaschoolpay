<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Pointage, QrCode, Enseignant, Etablissement, Salle, AlertePointage, EmploiDuTemps, CodePinJournalier};
use App\Services\Pointage\PointageScanService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Str;

class PointageController extends Controller
{
    /**
     * SCANNER UN QR CODE (Option A)
     */
    public function scannerQrCode(Request $request, PointageScanService $scanService): JsonResponse
    {
        $request->validate([
            'code_qr' => 'required|string',
            'gps_latitude' => 'required|numeric|between:-90,90',
            'gps_longitude' => 'required|numeric|between:-180,180',
            'gps_precision' => 'nullable|numeric',
            'type_scan' => 'required|in:arrivee,depart',
        ]);

        $enseignant = $request->user()->enseignantActif();
        if (! $enseignant) {
            return response()->json(['error' => 'Profil enseignant non trouvé.'], 403);
        }

        $contenuQr = json_decode($request->code_qr, true);
        if (! $contenuQr || ($contenuQr['app'] ?? '') !== 'aviaschoolpay') {
            return response()->json(['error' => 'QR Code invalide.'], 422);
        }

        $qrCode = QrCode::where('code_unique', $contenuQr['code'] ?? '')
            ->where('actif', true)->first();
        if (! $qrCode) {
            return response()->json(['error' => 'QR Code non reconnu ou désactivé.'], 404);
        }

        $result = $scanService->scannerQr(
            $enseignant,
            $enseignant->etablissement,
            $qrCode,
            $qrCode->salle,
            [
                'latitude' => (float) $request->gps_latitude,
                'longitude' => (float) $request->gps_longitude,
                'precision' => $request->gps_precision,
            ],
            $request->type_scan
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'],
                'message' => $result['message'],
            ], $result['http_code']);
        }

        $pointage = $result['pointage'];

        return response()->json([
            'success' => true,
            'pointage' => $pointage,
            'message' => $result['message'],
            'salle' => $qrCode->salle->nom,
            'distance_metres' => $result['distance_metres'],
            'conforme_emploi_temps' => $result['conforme_emploi_temps'],
            'edt' => $result['edt'],
            'validation_finale' => $pointage->validation_finale,
            'cahier_texte_status' => $pointage->cahier_texte_status,
            'cahier_texte_deadline_at' => $pointage->cahier_texte_deadline_at?->toIso8601String(),
        ], 200);
    }

    /**
     * POINTAGE PAR CODE PIN (Option B)
     */
    public function scannerPin(Request $request): JsonResponse
    {
        $request->validate([
            'code_pin' => 'required|string|size:4',
            'gps_latitude' => 'required|numeric',
            'gps_longitude' => 'required|numeric',
        ]);

        $enseignant = $request->user()->enseignantActif();
        $etab = $enseignant->etablissement;

        $pinValide = CodePinJournalier::where('etablissement_id', $etab->id)
            ->where('date', today())
            ->where('code_pin', $request->code_pin)
            ->where('heure_expiration', '>=', now()->format('H:i:s'))
            ->exists();

        if (!$pinValide) {
            return response()->json(['error' => 'Code PIN invalide ou expiré.'], 422);
        }

        $distance = null;
        $gpsValide = true;
        if ($etab->gps_latitude && $etab->gps_longitude) {
            $distance = Pointage::calculerDistance(
                (float) $request->gps_latitude,
                (float) $request->gps_longitude,
                (float) $etab->gps_latitude,
                (float) $etab->gps_longitude
            );
            $rayon = $etab->gps_rayon_metres ?? 100;
            $gpsValide = $distance <= $rayon;
        }

        if (!$gpsValide) {
            return response()->json(['error' => 'Hors périmètre.', 'distance' => round($distance)], 422);
        }

        $heureScan = now()->format('H:i');
        $statut = $heureScan <= '07:45' ? 'present' : 'retard';

        $pointage = Pointage::create([
            'enseignant_id' => $enseignant->id,
            'etablissement_id' => $etab->id,
            'date' => today(),
            'type_scan' => 'arrivee',
            'heure_scan' => now()->format('H:i:s'),
            'methode' => 'pin_gps',
            'statut' => $statut,
            'gps_latitude' => $request->gps_latitude,
            'gps_longitude' => $request->gps_longitude,
            'distance_ecole_metres' => $distance !== null ? round($distance, 1) : null,
            'gps_valide' => true,
            'token_valide' => true,
        ]);

        return response()->json(['success' => true, 'pointage' => $pointage, 'statut' => $statut]);
    }

    /**
     * LISTE DES POINTAGES DU JOUR (pour le directeur)
     */
    public function pointagesDuJour(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $pointages = Pointage::where('etablissement_id', $etab)
            ->where('date', today())
            ->where('type_scan', 'arrivee')
            ->with(['enseignant:id,nom,prenom,photo_path', 'salle:id,nom'])
            ->orderBy('heure_scan')
            ->get();

        $totalEnseignants = Enseignant::where('etablissement_id', $etab)->actif()->count();
        $presents = $pointages->where('statut', 'present')->count();
        $retards = $pointages->where('statut', 'retard')->count();
        $horsZone = $pointages->where('statut', 'hors_zone')->count();

        // Enseignants absents (pas de pointage)
        $enseignantsPointes = $pointages->pluck('enseignant_id');
        $absents = Enseignant::where('etablissement_id', $etab)
            ->actif()->whereNotIn('id', $enseignantsPointes)
            ->select('id', 'nom', 'prenom', 'photo_path', 'telephone')
            ->get();

        return response()->json([
            'date' => today()->format('d/m/Y'),
            'resume' => [
                'total' => $totalEnseignants,
                'presents' => $presents,
                'retards' => $retards,
                'absents' => $absents->count(),
                'hors_zone' => $horsZone,
                'taux_presence' => $totalEnseignants > 0 ? round((($presents + $retards) / $totalEnseignants) * 100, 1) : 0,
            ],
            'pointages' => $pointages,
            'absents' => $absents,
        ]);
    }

    /**
     * HISTORIQUE DE PONCTUALITÉ D'UN ENSEIGNANT
     */
    public function historique(Request $request, Enseignant $enseignant): JsonResponse
    {
        $mois = $request->get('mois', now()->format('Y-m'));

        $pointages = Pointage::where('enseignant_id', $enseignant->id)
            ->where('date', 'like', "$mois%")
            ->where('type_scan', 'arrivee')
            ->orderBy('date')
            ->get();

        return response()->json([
            'enseignant' => $enseignant->only('id', 'nom', 'prenom', 'score_ponctualite'),
            'mois' => $mois,
            'stats' => [
                'presents' => $pointages->where('statut', 'present')->count(),
                'retards' => $pointages->where('statut', 'retard')->count(),
                'absents_detectes' => $pointages->where('statut', 'absent')->count(),
                'hors_zone' => $pointages->where('statut', 'hors_zone')->count(),
                'heure_arrivee_moyenne' => $pointages->whereIn('statut', ['present', 'retard'])->avg(fn($p) => strtotime($p->heure_scan)),
            ],
            'details' => $pointages,
        ]);
    }

    /**
     * GÉNÉRER LES QR CODES DE TOUTES LES SALLES (pour impression)
     */
    public function genererQrCodes(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement;
        $salles = $etab->salles()->where('active', true)->get();

        $qrCodes = [];
        foreach ($salles as $salle) {
            $qr = QrCode::genererPourSalle($salle);
            $qrCodes[] = [
                'salle' => $salle->nom,
                'batiment' => $salle->batiment,
                'contenu_qr' => $qr->contenu_qr,
                'code' => $qr->code_unique,
            ];
        }

        return response()->json([
            'message' => count($qrCodes) . ' QR Codes générés. Prêts pour impression.',
            'etablissement' => $etab->nom,
            'qr_codes' => $qrCodes,
        ]);
    }

    /**
     * GÉNÉRER LE CODE PIN DU JOUR (Option B)
     */
    public function genererPinJournalier(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement;

        $pin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $codePinJour = CodePinJournalier::updateOrCreate(
            ['etablissement_id' => $etab->id, 'date' => today()],
            [
                'code_pin' => $pin,
                'heure_generation' => now()->format('H:i:s'),
                'heure_expiration' => '09:00:00',
            ]
        );

        // TODO: Envoyer par SMS au directeur

        return response()->json([
            'code_pin' => $pin,
            'expire_a' => '09:00',
            'message' => "Code PIN du jour : $pin. Affichez-le dans la salle des professeurs.",
        ]);
    }

    /**
     * ALERTES DE POINTAGE (non traitées)
     */
    public function alertes(Request $request): JsonResponse
    {
        $etab = $request->user()->etablissement_id;

        $alertes = AlertePointage::where('etablissement_id', $etab)
            ->where('traitee', false)
            ->with('enseignant:id,nom,prenom')
            ->latest()
            ->paginate(20);

        return response()->json($alertes);
    }
}
