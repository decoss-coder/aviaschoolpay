<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdtReferentielProfil extends Model
{
    protected $table = 'edt_referentiel_profils';

    protected $fillable = [
        'source_id',
        'code',
        'niveau_code',
        'option_code',
        'libelle',
        'cycle',
        'total_eleve_minutes',
        'total_prof_minutes',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(EdtReferentielSource::class, 'source_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(EdtReferentielLigne::class, 'profil_id');
    }
}