<?php
// app/Models/Creneau.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creneau extends Model
{
    protected $table = 'creneaux';
    
    public const TYPE_COURS           = 'cours';
    public const TYPE_RECREATION      = 'recreation';
    public const TYPE_PAUSE_DEJEUNER  = 'pause_dejeuner';

    protected $fillable = [
        'etablissement_id',
        'libelle',
        'heure_debut',
        'heure_fin',
        'type',
        'ordre',
        'est_recreation',  // colonnes legacy conservées
        'est_pause',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    // ── Accesseurs de commodité ──────────────────────────────────────

    /** Vrai si ce créneau est une récréation */
    public function getEstRecreationAttribute(): bool
    {
        return $this->type === self::TYPE_RECREATION;
    }

    /** Vrai si ce créneau est une pause déjeuner */
    public function getEstPauseAttribute(): bool
    {
        return $this->type === self::TYPE_PAUSE_DEJEUNER;
    }

    /** Vrai si ce créneau est un cours */
    public function getEstCoursAttribute(): bool
    {
        return $this->type === self::TYPE_COURS;
    }

    /**
     * Plage horaire du créneau : 'matin' si début avant 13h, 'apres_midi' sinon.
     * Utilisé par le moteur de génération EDT pour respecter les restrictions classe.
     */
    public function getPlageAttribute(): string
    {
        $heureDebut = (string) ($this->attributes['heure_debut'] ?? '00:00:00');
        return substr($heureDebut, 0, 5) < '13:00' ? 'matin' : 'apres_midi';
    }

    /**
     * Libellé affiché : utilise le champ libelle si rempli,
     * sinon "HH:MM – HH:MM".
     */
    public function getLabelAttribute(): string
    {
        if ($this->libelle) {
            return $this->libelle;
        }
        return ($this->heure_debut ?? '') . ' – ' . ($this->heure_fin ?? '');
    }

    // ── Relations ────────────────────────────────────────────────────

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function emploisDuTemps(): HasMany
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeCours($query)
    {
        return $query->where('type', self::TYPE_COURS);
    }

    public function scopePourEtablissement($query, int $etablissementId)
    {
        return $query->where('etablissement_id', $etablissementId);
    }
}