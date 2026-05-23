<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnseignantHoraireImport extends Model
{
    protected $table = 'edt_enseignant_horaires_imports';

    protected $fillable = [
        'enseignant_id',
        'annee_scolaire_id',
        'source_type',
        'fichier_path',
        'original_filename',
        'statut',
        'payload_ocr_json',
        'etablissement_detecte',
        'professeur_detecte',
        'confidence_score',
        'notes_ocr',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'payload_ocr_json' => 'array',
        'confidence_score' => 'integer',
        'validated_at'     => 'datetime',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function horairesExternes(): HasMany
    {
        return $this->hasMany(EnseignantHoraireExterne::class, 'import_id');
    }

    /** Slots extraits par l'OCR, prêts pour validation */
    public function getSlotsOcrAttribute(): array
    {
        return $this->payload_ocr_json['slots'] ?? [];
    }

    public function getConfidenceLabelAttribute(): string
    {
        $score = $this->confidence_score;

        if ($score >= 80) return 'Élevé';
        if ($score >= 50) return 'Moyen';

        return 'Faible';
    }

    public function getConfidenceColorAttribute(): string
    {
        $score = $this->confidence_score;

        if ($score >= 80) return 'green';
        if ($score >= 50) return 'yellow';

        return 'red';
    }

    public function estAnalyse(): bool
    {
        return in_array($this->statut, ['analyse', 'valide'], true);
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }
}
