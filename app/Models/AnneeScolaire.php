<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeScolaire extends Model
{
    protected $table = 'annees_scolaires';

    protected $fillable = [
        'etablissement_id',
        'libelle',
        'date_debut',
        'date_fin',
        'en_cours',
        'cloturee',
        'archivee',
        'archive_path',
        'archive_checksum',
        'restoration_key_hash',
        'restoration_key_vault',
        'archived_at',
        'archived_by',
        'archive_meta',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'en_cours' => 'boolean',
        'cloturee' => 'boolean',
        'archivee' => 'boolean',
        'archived_at' => 'datetime',
        'archive_meta' => 'array',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function trimestres(): HasMany
    {
        return $this->hasMany(Trimestre::class)->orderBy('numero');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function scopeEnCours($query)
    {
        return $query->where('en_cours', true);
    }

    /** Année réactivée après archivage : consultation seule, pas de saisie. */
    public function estArchiveConsultation(): bool
    {
        return $this->en_cours
            && ! $this->archivee
            && ! empty($this->archive_meta['restaurer_le']);
    }

    public function estLectureSeule(): bool
    {
        return $this->archivee
            || $this->cloturee
            || $this->estArchiveConsultation();
    }
}