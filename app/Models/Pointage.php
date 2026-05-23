<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Pointage extends Model
{
    public const TYPE_SCAN_ARRIVEE = 'arrivee';
    public const TYPE_SCAN_DEPART = 'depart';

    public const METHODE_QR_GPS = 'qr_gps';
    public const METHODE_PIN_GPS = 'pin_gps';
    public const METHODE_NFC_GPS = 'nfc_gps';
    public const METHODE_MANUEL = 'manuel';

    public const STATUT_PRESENT = 'present';
    public const STATUT_RETARD = 'retard';
    public const STATUT_ABSENT = 'absent';
    public const STATUT_ANOMALIE = 'anomalie';
    public const STATUT_HORS_ZONE = 'hors_zone';

    public const VALIDATION_VALIDE = 'valide';
    public const VALIDATION_PROVISOIRE = 'provisoire';
    public const VALIDATION_INCOMPLET = 'incomplet';
    public const VALIDATION_REJETE = 'rejete';
    public const VALIDATION_ANOMALIE = 'anomalie';

    public const CAHIER_EN_ATTENTE = 'en_attente';
    public const CAHIER_SOUMIS = 'soumis';
    public const CAHIER_VALIDE = 'valide';
    public const CAHIER_REFUSE = 'refuse';

    protected $fillable = [
        'enseignant_id',
        'etablissement_id',
        'qr_code_id',
        'salle_id',
        'emploi_du_temps_id',
        'date',
        'type_scan',
        'heure_scan',
        'methode',
        'statut',
        'gps_latitude',
        'gps_longitude',
        'gps_precision_metres',
        'distance_ecole_metres',
        'gps_valide',
        'spoofing_detecte',
        'token_validation',
        'token_expire_at',
        'token_valide',
        'selfie_path',
        'conforme_emploi_temps',
        'validation_finale',
        'observations',
        'cahier_texte_path',
        'cahier_texte_data',
        'cahier_texte_validated',
        'cahier_texte_status',
        'cahier_texte_validated_at',
        'cahier_texte_deadline_at',
        'cahier_texte_confidence',
    ];

    protected $casts = [
        'date' => 'date',
        'cahier_texte_data' => 'array',
        'cahier_texte_validated' => 'boolean',
        'cahier_texte_validated_at' => 'datetime',
        'cahier_texte_deadline_at' => 'datetime',
        'token_expire_at' => 'datetime',
        'gps_latitude' => 'decimal:7',
        'gps_longitude' => 'decimal:7',
        'gps_precision_metres' => 'decimal:1',
        'distance_ecole_metres' => 'decimal:1',
        'gps_valide' => 'boolean',
        'spoofing_detecte' => 'boolean',
        'token_valide' => 'boolean',
        'conforme_emploi_temps' => 'boolean',
    ];

    protected $hidden = [
        'token_validation',
    ];

    protected $appends = [
        'type_scan_libelle',
        'methode_libelle',
        'statut_libelle',
        'date_heure_scan',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function emploiDuTemps(): BelongsTo
    {
        return $this->belongsTo(EmploiDuTemps::class, 'emploi_du_temps_id');
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(AlertePointage::class);
    }

    public function getDateHeureScanAttribute(): ?Carbon
    {
        if (!$this->date || !$this->heure_scan) {
            return null;
        }

        return Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->heure_scan);
    }

    public function getTypeScanLibelleAttribute(): string
    {
        return match ($this->type_scan) {
            self::TYPE_SCAN_ARRIVEE => 'Arrivée',
            self::TYPE_SCAN_DEPART => 'Départ',
            default => Str::headline(str_replace('_', ' ', (string) $this->type_scan)),
        };
    }

    public function getMethodeLibelleAttribute(): string
    {
        return match ($this->methode) {
            self::METHODE_QR_GPS => 'QR + GPS',
            self::METHODE_PIN_GPS => 'PIN + GPS',
            self::METHODE_NFC_GPS => 'NFC + GPS',
            self::METHODE_MANUEL => 'Manuel',
            default => Str::headline(str_replace('_', ' ', (string) $this->methode)),
        };
    }

    public function getStatutLibelleAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_PRESENT => 'Présent',
            self::STATUT_RETARD => 'Retard',
            self::STATUT_ABSENT => 'Absent',
            self::STATUT_HORS_ZONE => 'Hors zone',
            default => Str::headline(str_replace('_', ' ', (string) $this->statut)),
        };
    }

    public function estAnormal(): bool
    {
        return $this->statut === self::STATUT_HORS_ZONE
            || $this->spoofing_detecte
            || !$this->gps_valide
            || !$this->token_valide
            || !$this->conforme_emploi_temps;
    }

    public function aCahierTexte(): bool
    {
        return filled($this->cahier_texte_path);
    }

    /** @return array<string, mixed> */
    public function cahierExtrait(): array
    {
        $data = (array) ($this->cahier_texte_data ?? []);
        unset($data['validation'], $data['raw_response']);

        return $data;
    }

    /** @return array<string, mixed> */
    public function cahierValidation(): array
    {
        return (array) (($this->cahier_texte_data ?? [])['validation'] ?? []);
    }

    public function syncValidationFinale(): void
    {
        if (in_array($this->statut, [self::STATUT_HORS_ZONE, 'fraude_detectee'], true)) {
            $this->validation_finale = self::VALIDATION_REJETE;

            return;
        }

        if ($this->cahier_texte_status === self::CAHIER_EN_ATTENTE) {
            if ($this->cahier_texte_deadline_at && now()->gt($this->cahier_texte_deadline_at)) {
                $this->validation_finale = self::VALIDATION_INCOMPLET;
            } else {
                $this->validation_finale = self::VALIDATION_PROVISOIRE;
            }

            return;
        }

        if ($this->cahier_texte_status === self::CAHIER_REFUSE
            || ($this->cahier_texte_status === self::CAHIER_SOUMIS && ! $this->cahier_texte_validated)) {
            $this->validation_finale = self::VALIDATION_INCOMPLET;

            return;
        }

        if (! $this->conforme_emploi_temps) {
            $this->validation_finale = self::VALIDATION_ANOMALIE;

            return;
        }

        if ($this->cahier_texte_validated && $this->gps_valide) {
            $this->validation_finale = self::VALIDATION_VALIDE;

            return;
        }

        $this->validation_finale = self::VALIDATION_PROVISOIRE;
    }

    public function getValidationFinaleLibelleAttribute(): string
    {
        return match ($this->validation_finale) {
            self::VALIDATION_VALIDE => 'Validé',
            self::VALIDATION_PROVISOIRE => 'Provisoire',
            self::VALIDATION_INCOMPLET => 'Incomplet',
            self::VALIDATION_REJETE => 'Rejeté',
            self::VALIDATION_ANOMALIE => 'Anomalie EDT',
            default => ucfirst((string) $this->validation_finale),
        };
    }

    public function scopePourEtablissement(Builder $query, int $etablissementId): Builder
    {
        return $query->where('etablissement_id', $etablissementId);
    }

    public function scopeAujourdhui(Builder $query): Builder
    {
        return $query->whereDate('date', today());
    }

    public function scopeArrivees(Builder $query): Builder
    {
        return $query->where('type_scan', self::TYPE_SCAN_ARRIVEE);
    }

    public function scopeDeparts(Builder $query): Builder
    {
        return $query->where('type_scan', self::TYPE_SCAN_DEPART);
    }

    public function scopePresents(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PRESENT);
    }

    public function scopeRetards(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_RETARD);
    }

    public function scopeAbsents(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_ABSENT);
    }

    public function scopeHorsZone(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_HORS_ZONE);
    }

    /**
     * Distance GPS entre deux coordonnées (formule de Haversine), en mètres.
     */
    public static function calculerDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6_371_000;

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}