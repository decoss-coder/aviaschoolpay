<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdtVacataireImport extends Model
{
    protected $table = 'edt_vacataire_imports';

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'enseignant_id',
        'source_type',
        'fichier_path',
        'original_filename',
        'payload_extrait_json',
        'resume_extraction',
        'confidence_score',
        'status',
        'validated_by',
        'validated_at',
        'created_by',
    ];

    protected $casts = [
        'payload_extrait_json' => 'array',
        'validated_at' => 'datetime',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(EdtVacataireSlot::class, 'import_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }
}