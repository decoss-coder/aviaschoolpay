<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtReferentielSource extends Model
{
    protected $table = 'edt_referentiel_sources';

    protected $fillable = [
        'etablissement_id',
        'libelle',
        'source_document',
        'date_reference',
        'annee_reference',
        'description',
        'actif',
    ];

    protected $casts = [
        'date_reference' => 'date',
        'actif' => 'boolean',
    ];

    public function profils(): HasMany
    {
        return $this->hasMany(EdtReferentielProfil::class, 'source_id');
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_id');
    }
}