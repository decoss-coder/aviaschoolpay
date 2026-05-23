<?php

namespace App\Services\Pointage;

use App\Models\AlertePointage;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\Pointage;
use App\Models\QrCode;
use App\Models\Salle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PointageScanService
{
    public const HEURE_DEBUT = '07:00';

    public const HEURE_FIN_ARRIVEE = '18:30';

    public const HEURE_FIN_DEPART = '19:00';

    public const MARGE_PRESENT_MIN = 5;

    public const MARGE_RETARD_MIN = 15;

    public const MARGE_DEPART_AVANT = 5;

    public const MARGE_DEPART_APRES = 10;

    /**
     * @param  array{latitude: float, longitude: float, precision: ?float}  $gps
     * @return array{success: bool, http_code: int, message: string, pointage: ?Pointage, distance_metres: ?int, conforme_emploi_temps: bool, edt: ?array}
     */
    public function scannerQr(
        Enseignant $enseignant,
        Etablissement $etab,
        QrCode $qrCode,
        Salle $salle,
        array $gps,
        string $typeScan,
        ?Carbon $now = null
    ): array {
        $now ??= now();
        $typeScan = $typeScan === Pointage::TYPE_SCAN_DEPART ? Pointage::TYPE_SCAN_DEPART : Pointage::TYPE_SCAN_ARRIVEE;
        $scanMinutes = $this->toMinutes($now->format('H:i'));

        if (! $this->dansPlageHoraireGlobale($typeScan, $scanMinutes)) {
            return $this->echec(
                $typeScan === Pointage::TYPE_SCAN_DEPART
                    ? 'Pointage départ impossible en dehors des heures autorisées (7h–19h).'
                    : 'Pointage impossible en dehors des heures autorisées (7h–18h30).',
                422
            );
        }

        $coursDuJour = $this->coursDuJour($enseignant, $now);
        if ($coursDuJour->isEmpty()) {
            return $this->echec('Vous n\'avez pas de cours prévu aujourd\'hui.', 422);
        }

        $edt = $this->trouverCreneauPourScan($coursDuJour, $salle, $typeScan, $now);
        if (! $edt) {
            return $this->echec(
                'Aucun cours prévu dans cette salle à cette heure. Vérifiez le QR code et votre emploi du temps.',
                422
            );
        }

        $dejaPointe = Pointage::query()
            ->where('enseignant_id', $enseignant->id)
            ->whereDate('date', $now->toDateString())
            ->where('type_scan', $typeScan)
            ->where('emploi_du_temps_id', $edt->id)
            ->exists();

        if ($dejaPointe) {
            return $this->echec('Vous avez déjà pointé pour ce créneau aujourd\'hui.', 422);
        }

        $gpsConfigured = $etab->gps_latitude && $etab->gps_longitude;
        $distance = null;
        $gpsValide = true;

        if ($gpsConfigured) {
            $distance = Pointage::calculerDistance(
                (float) $gps['latitude'],
                (float) $gps['longitude'],
                (float) $etab->gps_latitude,
                (float) $etab->gps_longitude
            );
            $rayon = (int) ($etab->gps_rayon_metres ?? 100);
            $gpsValide = $distance <= $rayon;
        }

        $precision = $gps['precision'] ?? null;
        $spoofing = $precision !== null && ($precision < 1 || $precision > 500);

        $statut = $this->determinerStatut($typeScan, $edt, $now, $gpsValide, $spoofing);
        $conformeEdt = true;

        $token = hash('sha256', $enseignant->id.'-'.$now->timestamp.'-'.Str::random(16));

        $pointage = Pointage::create([
            'enseignant_id' => $enseignant->id,
            'etablissement_id' => $etab->id,
            'qr_code_id' => $qrCode->id,
            'salle_id' => $salle->id,
            'emploi_du_temps_id' => $edt->id,
            'date' => $now->toDateString(),
            'type_scan' => $typeScan,
            'heure_scan' => $now->format('H:i:s'),
            'methode' => Pointage::METHODE_QR_GPS,
            'statut' => $statut,
            'gps_latitude' => $gps['latitude'],
            'gps_longitude' => $gps['longitude'],
            'gps_precision_metres' => $precision,
            'distance_ecole_metres' => $distance !== null ? round($distance, 1) : null,
            'gps_valide' => $gpsValide,
            'spoofing_detecte' => $spoofing,
            'token_validation' => $token,
            'token_expire_at' => $now->copy()->addSeconds(30),
            'token_valide' => $gpsValide && ! $spoofing,
            'conforme_emploi_temps' => $conformeEdt,
            'cahier_texte_status' => Pointage::CAHIER_EN_ATTENTE,
            'cahier_texte_deadline_at' => $now->copy()->setTime(18, 30, 0),
        ]);

        $pointage->syncValidationFinale();
        $pointage->save();

        $this->creerAlertes($pointage, $enseignant, $etab, $statut, $distance, $now, ! $gpsConfigured);

        $success = $gpsValide && ! $spoofing && ! in_array($statut, [Pointage::STATUT_HORS_ZONE, 'fraude_detectee'], true);

        $message = match ($statut) {
            Pointage::STATUT_PRESENT => 'Pointage enregistré. Complétez le cahier de texte pour valider.',
            Pointage::STATUT_RETARD => 'Pointage enregistré en retard. Complétez le cahier de texte.',
            Pointage::STATUT_HORS_ZONE => 'Pointage rejeté. Vous êtes hors du périmètre de l\'école.',
            'fraude_detectee' => 'Pointage rejeté. Position GPS suspecte détectée.',
            default => 'Pointage enregistré.',
        };

        return [
            'success' => $success,
            'http_code' => $success ? 200 : 422,
            'message' => $message,
            'pointage' => $pointage->fresh(['salle', 'emploiDuTemps.creneau', 'emploiDuTemps.matiere', 'emploiDuTemps.classe']),
            'distance_metres' => $distance !== null ? (int) round($distance) : null,
            'conforme_emploi_temps' => $conformeEdt,
            'edt' => $this->formatEdt($edt),
        ];
    }

    public static function jourFrancais(?Carbon $at = null): string
    {
        $at ??= now();

        return match ((int) $at->dayOfWeek) {
            1 => EmploiDuTemps::JOUR_LUNDI,
            2 => EmploiDuTemps::JOUR_MARDI,
            3 => EmploiDuTemps::JOUR_MERCREDI,
            4 => EmploiDuTemps::JOUR_JEUDI,
            5 => EmploiDuTemps::JOUR_VENDREDI,
            6 => 'samedi',
            default => 'dimanche',
        };
    }

    /** @return Collection<int, EmploiDuTemps> */
    public function coursDuJour(Enseignant $enseignant, Carbon $now): Collection
    {
        $jour = self::jourFrancais($now);

        return EmploiDuTemps::query()
            ->where('enseignant_id', $enseignant->id)
            ->where('jour', $jour)
            ->where('actif', true)
            ->with(['creneau', 'salle', 'matiere', 'classe'])
            ->get()
            ->filter(fn (EmploiDuTemps $e) => $e->estValideALaDate($now));
    }

    public function trouverCreneauPourScan(
        Collection $coursDuJour,
        Salle $salle,
        string $typeScan,
        Carbon $now
    ): ?EmploiDuTemps {
        foreach ($coursDuJour->where('salle_id', $salle->id) as $edt) {
            if ($this->scanDansFenetreCreneau($edt, $typeScan, $now)) {
                return $edt;
            }
        }

        return null;
    }

    public function scanDansFenetreCreneau(EmploiDuTemps $edt, string $typeScan, Carbon $now): bool
    {
        $creneau = $edt->creneau;
        if (! $creneau || ! $creneau->heure_debut || ! $creneau->heure_fin) {
            return false;
        }

        $scanMin = $this->toMinutes($now->format('H:i'));
        $debut = $this->toMinutes(substr((string) $creneau->heure_debut, 0, 5));
        $fin = $this->toMinutes(substr((string) $creneau->heure_fin, 0, 5));

        if ($typeScan === Pointage::TYPE_SCAN_ARRIVEE) {
            $fenetreMin = max($this->toMinutes(self::HEURE_DEBUT), $debut);
            $fenetreMax = $debut + self::MARGE_RETARD_MIN;

            return $scanMin >= $fenetreMin && $scanMin <= $fenetreMax;
        }

        $fenetreMin = $fin - self::MARGE_DEPART_AVANT;
        $fenetreMax = min($this->toMinutes(self::HEURE_FIN_DEPART), $fin + self::MARGE_DEPART_APRES);

        return $scanMin >= $fenetreMin && $scanMin <= $fenetreMax;
    }

    private function determinerStatut(
        string $typeScan,
        EmploiDuTemps $edt,
        Carbon $now,
        bool $gpsValide,
        bool $spoofing
    ): string {
        if (! $gpsValide) {
            return Pointage::STATUT_HORS_ZONE;
        }
        if ($spoofing) {
            return 'fraude_detectee';
        }

        if ($typeScan === Pointage::TYPE_SCAN_DEPART) {
            return Pointage::STATUT_PRESENT;
        }

        $debut = $this->toMinutes(substr((string) $edt->creneau->heure_debut, 0, 5));
        $scanMin = $this->toMinutes($now->format('H:i'));

        return $scanMin <= $debut + self::MARGE_PRESENT_MIN
            ? Pointage::STATUT_PRESENT
            : Pointage::STATUT_RETARD;
    }

    private function dansPlageHoraireGlobale(string $typeScan, int $scanMinutes): bool
    {
        $min = $this->toMinutes(self::HEURE_DEBUT);
        $max = $typeScan === Pointage::TYPE_SCAN_DEPART
            ? $this->toMinutes(self::HEURE_FIN_DEPART)
            : $this->toMinutes(self::HEURE_FIN_ARRIVEE);

        return $scanMinutes >= $min && $scanMinutes <= $max;
    }

    private function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_pad(explode(':', $hhmm), 2, 0);

        return ((int) $h) * 60 + ((int) $m);
    }

    /** @return array{success: bool, http_code: int, message: string, pointage: null, distance_metres: null, conforme_emploi_temps: false, edt: null} */
    private function echec(string $message, int $code): array
    {
        return [
            'success' => false,
            'http_code' => $code,
            'message' => $message,
            'pointage' => null,
            'distance_metres' => null,
            'conforme_emploi_temps' => false,
            'edt' => null,
        ];
    }

    private function creerAlertes(
        Pointage $pointage,
        Enseignant $enseignant,
        Etablissement $etab,
        string $statut,
        ?float $distance,
        Carbon $now,
        bool $gpsNonConfigure
    ): void {
        if ($gpsNonConfigure) {
            AlertePointage::create([
                'etablissement_id' => $etab->id,
                'enseignant_id' => $enseignant->id,
                'pointage_id' => $pointage->id,
                'date' => $now->toDateString(),
                'type_alerte' => 'gps_ecole_manquant',
                'gravite' => 'info',
                'message' => "Pointage de {$enseignant->nom_complet} sans vérification GPS : coordonnées établissement non configurées.",
            ]);
        }

        if (! in_array($statut, [Pointage::STATUT_HORS_ZONE, 'fraude_detectee', Pointage::STATUT_RETARD], true)) {
            return;
        }

        $heureScan = $now->format('H:i');
        $distanceMsg = $distance !== null ? round($distance) : '?';

        AlertePointage::create([
            'etablissement_id' => $etab->id,
            'enseignant_id' => $enseignant->id,
            'pointage_id' => $pointage->id,
            'date' => $now->toDateString(),
            'type_alerte' => match ($statut) {
                Pointage::STATUT_HORS_ZONE => 'hors_zone',
                'fraude_detectee' => 'spoofing_gps',
                Pointage::STATUT_RETARD => 'retard',
                default => 'anomalie',
            },
            'gravite' => $statut === 'fraude_detectee' ? 'critique' : 'warning',
            'message' => match ($statut) {
                Pointage::STATUT_HORS_ZONE => "{$enseignant->nom_complet} a tenté de pointer à {$distanceMsg}m de l'école.",
                'fraude_detectee' => "Spoofing GPS détecté pour {$enseignant->nom_complet}.",
                Pointage::STATUT_RETARD => "{$enseignant->nom_complet} est arrivé en retard à {$heureScan}.",
                default => "Anomalie de pointage pour {$enseignant->nom_complet}.",
            },
        ]);
    }

    /** @return array<string, mixed> */
    private function formatEdt(EmploiDuTemps $edt): array
    {
        return [
            'id' => $edt->id,
            'jour' => $edt->jour,
            'creneau' => $edt->creneau ? [
                'libelle' => $edt->creneau->libelle,
                'heure_debut' => substr((string) $edt->creneau->heure_debut, 0, 5),
                'heure_fin' => substr((string) $edt->creneau->heure_fin, 0, 5),
            ] : null,
            'matiere' => $edt->matiere?->only(['id', 'nom', 'code']),
            'classe' => $edt->classe?->only(['id', 'nom']),
            'salle' => $edt->salle?->only(['id', 'nom']),
        ];
    }
}
