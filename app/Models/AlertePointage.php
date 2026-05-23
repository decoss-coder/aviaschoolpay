<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AlertePointage extends Model
{
    protected $table = 'alertes_pointage';

    public const GRAVITE_INFO = 'info';
    public const GRAVITE_WARNING = 'warning';
    public const GRAVITE_CRITIQUE = 'critique';

    protected $fillable = [
        'etablissement_id',
        'enseignant_id',
        'pointage_id',
        'date',
        'type_alerte',
        'gravite',
        'message',
        'lue',
        'traitee',
        'traitee_par',
        'commentaire_traitement',
    ];

    protected $casts = [
        'date' => 'date',
        'lue' => 'boolean',
        'traitee' => 'boolean',
    ];

    protected $appends = [
        'type_alerte_libelle',
        'gravite_libelle',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function pointage(): BelongsTo
    {
        return $this->belongsTo(Pointage::class);
    }

    public function traiteePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traitee_par');
    }

    public function getTypeAlerteLibelleAttribute(): string
    {
        return Str::headline(str_replace('_', ' ', (string) $this->type_alerte));
    }

    public function getGraviteLibelleAttribute(): string
    {
        return match ($this->gravite) {
            self::GRAVITE_INFO => 'Info',
            self::GRAVITE_WARNING => 'Warning',
            self::GRAVITE_CRITIQUE => 'Critique',
            default => Str::headline(str_replace('_', ' ', (string) $this->gravite)),
        };
    }

    public function scopeNonLues(Builder $query): Builder
    {
        return $query->where('lue', false);
    }

    public function scopeNonTraitees(Builder $query): Builder
    {
        return $query->where('traitee', false);
    }

    public function scopeCritiques(Builder $query): Builder
    {
        return $query->where('gravite', self::GRAVITE_CRITIQUE);
    }
}