<?php

namespace App\Http\Controllers;

use App\Models\AlertePointage;
use App\Models\Enseignant;
use App\Models\EmploiDuTemps;
use App\Models\Etablissement;
use App\Models\Pointage;
use App\Models\QrCode as QrCodeModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Portail Enseignant — Pointage QR par caméra.
 *
 *  - GET  /mon-espace/pointage         : page scanner (caméra) + historique du jour
 *  - POST /mon-espace/pointage/scan    : enregistre un pointage à partir du QR + GPS
 */
class MonPointageController extends Controller
{
    private function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(!$ens, 403, 'Compte enseignant introuvable pour cette école.');
        return $ens;
    }

    public function index(Request $request)
    {
        $ens   = $this->enseignant($request);
        $etabId = (int) $request->user()->ecoleActiveId();
        $etab   = Etablissement::find($etabId);

        $today = today();

        $pointagesAujourdHui = Pointage::where('enseignant_id', $ens->id)
            ->whereDate('date', $today)
            ->with('salle')
            ->orderByDesc('heure_scan')
            ->get();

        $derniersJours = Pointage::where('enseignant_id', $ens->id)
            ->where('date', '>=', $today->copy()->subDays(7))
            ->with('salle')
            ->orderByDesc('date')
            ->orderByDesc('heure_scan')
            ->get()
            ->groupBy(fn ($p) => $p->date->format('Y-m-d'));

        $aDejaArrivee = $pointagesAujourdHui->where('type_scan', 'arrivee')->isNotEmpty();
        $aDejaDepart  = $pointagesAujourdHui->where('type_scan', 'depart')->isNotEmpty();

        // Prochaine séance d'aujourd'hui depuis l'EDT
        $jourFr  = strtolower(Carbon::now()->locale('fr')->isoFormat('dddd'));
        $jours   = ['lundi','mardi','mercredi','jeudi','vendredi','samedi'];
        $prochaineSeance = null;
        if (in_array($jourFr, $jours)) {
            $prochaineSeance = EmploiDuTemps::where('enseignant_id', $ens->id)
                ->where('jour', $jourFr)
                ->where('actif', true)
                ->with(['classe', 'matiere', 'salle', 'creneau'])
                ->get()
                ->sortBy(fn ($s) => $s->creneau?->heure_debut ?? '23:59')
                ->first(fn ($s) => ($s->creneau?->heure_fin ?? '00:00') >= now()->format('H:i'));
        }

        return view('mon-espace.pointage.index', compact(
            'ens', 'etab', 'pointagesAujourdHui', 'derniersJours',
            'aDejaArrivee', 'aDejaDepart', 'prochaineSeance'
        ));
    }

    /**
     * Enregistrer un pointage à partir du QR + GPS (appelé en AJAX).
     */
    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code_qr'        => 'required|string',
            'gps_latitude'   => 'required|numeric|between:-90,90',
            'gps_longitude'  => 'required|numeric|between:-180,180',
            'gps_precision'  => 'nullable|numeric',
            'type_scan'      => 'required|in:arrivee,depart',
        ]);

        $ens   = $this->enseignant($request);
        $etab  = Etablissement::find($request->user()->ecoleActiveId());
        if (!$etab) return response()->json(['error' => 'École active introuvable.'], 403);

        // ── 1. Décoder et vérifier le QR ──
        $contenu = json_decode($data['code_qr'], true);
        if (!$contenu || ($contenu['app'] ?? '') !== 'aviaschoolpay') {
            return response()->json(['error' => 'QR Code non reconnu (format invalide).'], 422);
        }

        $qrCode = QrCodeModel::where('code_unique', $contenu['code'] ?? '')
            ->where('actif', true)->first();
        if (!$qrCode) return response()->json(['error' => 'QR Code désactivé ou inconnu.'], 404);

        // QR doit appartenir à l'école active du prof
        if ((int) $qrCode->etablissement_id !== (int) $etab->id) {
            return response()->json([
                'error' => 'Ce QR appartient à une autre école. Changez d\'école active si besoin.',
            ], 422);
        }

        $salle = $qrCode->salle;

        // ── 2. Vérifier déjà pointé ──
        $dejaPointe = Pointage::where('enseignant_id', $ens->id)
            ->whereDate('date', today())
            ->where('type_scan', $data['type_scan'])
            ->exists();
        if ($dejaPointe) {
            return response()->json([
                'error' => 'Vous avez déjà effectué ce pointage (' . $data['type_scan'] . ') aujourd\'hui.',
            ], 422);
        }

        // ── 3. Calcul distance GPS ──
        $distance = null;
        $gpsValide = true;
        if ($etab->gps_latitude && $etab->gps_longitude) {
            $distance = Pointage::calculerDistance(
                $data['gps_latitude'], $data['gps_longitude'],
                (float) $etab->gps_latitude, (float) $etab->gps_longitude
            );
            $rayon = $etab->gps_rayon_metres ?? 100;
            $gpsValide = $distance <= $rayon;
        }

        // ── 4. Spoofing (précision suspecte) ──
        $precision = $data['gps_precision'] ?? null;
        $spoofing = $precision !== null && ($precision < 1 || $precision > 500);

        // ── 5. Conformité avec EDT ──
        $jourFr = strtolower(Carbon::now()->locale('fr')->isoFormat('dddd'));
        $conformeEdt = EmploiDuTemps::where('enseignant_id', $ens->id)
            ->where('salle_id', $salle->id)
            ->where('jour', $jourFr)
            ->where('actif', true)
            ->exists();

        // ── 6. Statut ──
        $heureScan = now()->format('H:i');
        if (!$gpsValide)        $statut = 'hors_zone';
        elseif ($spoofing)      $statut = 'fraude_detectee';
        elseif ($heureScan <= '07:45') $statut = 'present';
        else                    $statut = 'retard';

        // ── 7. Persistance ──
        $token = hash('sha256', $ens->id . '-' . now()->timestamp . '-' . Str::random(16));

        $pointage = Pointage::create([
            'enseignant_id'        => $ens->id,
            'etablissement_id'     => $etab->id,
            'qr_code_id'           => $qrCode->id,
            'salle_id'             => $salle->id,
            'date'                 => today(),
            'type_scan'            => $data['type_scan'],
            'heure_scan'           => now()->format('H:i:s'),
            'methode'              => 'qr_gps',
            'statut'               => $statut,
            'gps_latitude'         => $data['gps_latitude'],
            'gps_longitude'        => $data['gps_longitude'],
            'gps_precision_metres' => $precision,
            'distance_ecole_metres'=> $distance !== null ? round($distance, 1) : null,
            'gps_valide'           => $gpsValide,
            'spoofing_detecte'     => $spoofing,
            'token_validation'     => $token,
            'token_expire_at'      => now()->addSeconds(30),
            'token_valide'         => $gpsValide && !$spoofing,
            'conforme_emploi_temps'=> $conformeEdt,
        ]);

        // ── 8. Alertes ──
        if (in_array($statut, ['hors_zone', 'fraude_detectee', 'retard'])) {
            AlertePointage::create([
                'etablissement_id' => $etab->id,
                'enseignant_id'    => $ens->id,
                'pointage_id'      => $pointage->id,
                'date'             => today(),
                'type_alerte'      => match ($statut) {
                    'hors_zone'        => 'hors_zone',
                    'fraude_detectee'  => 'spoofing_gps',
                    'retard'           => 'retard',
                },
                'gravite' => $statut === 'fraude_detectee' ? 'critique' : 'warning',
                'message' => match ($statut) {
                    'hors_zone'       => "{$ens->prenom} {$ens->nom} a tenté de pointer à " . round($distance ?? 0) . "m de l'école.",
                    'fraude_detectee' => "Spoofing GPS détecté pour {$ens->prenom} {$ens->nom}.",
                    'retard'          => "{$ens->prenom} {$ens->nom} pointage tardif à {$heureScan}.",
                },
            ]);
        }

        $success = $gpsValide && !$spoofing;
        $message = match ($statut) {
            'present'         => 'Pointage enregistré. Vous êtes présent(e).',
            'retard'          => 'Pointage enregistré, mais comptabilisé en retard.',
            'hors_zone'       => 'Pointage rejeté : vous êtes hors du périmètre de l\'école (' . round($distance ?? 0) . 'm).',
            'fraude_detectee' => 'Pointage rejeté : position GPS suspecte.',
            default           => 'Pointage enregistré.',
        };

        return response()->json([
            'success'   => $success,
            'message'   => $message,
            'statut'    => $statut,
            'salle'     => $salle->nom,
            'distance'  => $distance !== null ? round($distance) : null,
            'conforme'  => $conformeEdt,
            'heure'     => now()->format('H:i:s'),
            'pointage'  => $pointage->only(['id','type_scan','statut','heure_scan']),
        ], $success ? 200 : 422);
    }
}
