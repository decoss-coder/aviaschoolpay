<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploiDuTemps extends Model
{
    protected $table = 'emploi_du_temps';

    // -------------------------------------------------------
    // Constantes — Jours
    // -------------------------------------------------------
    public const JOUR_LUNDI    = 'lundi';
    public const JOUR_MARDI    = 'mardi';
    public const JOUR_MERCREDI = 'mercredi';
    public const JOUR_JEUDI    = 'jeudi';
    public const JOUR_VENDREDI = 'vendredi';

    // -------------------------------------------------------
    // Constantes — Source
    // -------------------------------------------------------
    public const SOURCE_IA         = 'ia';
    public const SOURCE_MANUEL     = 'manuel';
    public const SOURCE_AJUSTEMENT = 'ajustement';

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'salle_id',
        'creneau_id',
        'jour',
        'valide_du',
        'valide_au',
        'actif',

        // Colonnes IA / apprentissage
        'source',
        'generation_uuid',
        'locked_by_user',
        'ia_score',
        'last_adjusted_by',
        'last_adjusted_at',
    ];

    protected $casts = [
        'valide_du'        => 'date',
        'valide_au'        => 'date',
        'actif'            => 'boolean',
        'locked_by_user'   => 'boolean',
        'ia_score'         => 'decimal:2',
        'last_adjusted_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Helpers statiques
    // -------------------------------------------------------
    public static function jours(): array
    {
        return [
            self::JOUR_LUNDI,
            self::JOUR_MARDI,
            self::JOUR_MERCREDI,
            self::JOUR_JEUDI,
            self::JOUR_VENDREDI,
        ];
    }

    public static function sources(): array
    {
        return [
            self::SOURCE_IA,
            self::SOURCE_MANUEL,
            self::SOURCE_AJUSTEMENT,
        ];
    }

    // -------------------------------------------------------
    // Relations
    // -------------------------------------------------------
    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(Creneau::class);
    }

    public function lastAdjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_adjusted_by');
    }

    // -------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------
    public function estValideALaDate($date = null): bool
    {
        $date = $date
            ? Carbon::parse($date)->toDateString()
            : today()->toDateString();

        if (!$this->actif) {
            return false;
        }

        if ($this->valide_du && $date < $this->valide_du->toDateString()) {
            return false;
        }

        if ($this->valide_au && $date > $this->valide_au->toDateString()) {
            return false;
        }

        return true;
    }

    public function estGenereParIA(): bool
    {
        return $this->source === self::SOURCE_IA;
    }

    public function estVerrouilleParUtilisateur(): bool
    {
        return (bool) $this->locked_by_user;
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------
    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeDuJour(Builder $query, string $jour): Builder
    {
        return $query->where('jour', $jour);
    }

    public function scopeDeClasse(Builder $query, int $classeId): Builder
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeDeEnseignant(Builder $query, int $enseignantId): Builder
    {
        return $query->where('enseignant_id', $enseignantId);
    }

    public function scopeDeSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeGeneresParIA(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_IA);
    }

    public function scopeDeGeneration(Builder $query, string $uuid): Builder
    {
        return $query->where('generation_uuid', $uuid);
    }

    public function scopeVerrouillesParUtilisateur(Builder $query): Builder
    {
        return $query->where('locked_by_user', true);
    }
}