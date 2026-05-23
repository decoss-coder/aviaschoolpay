<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongePermission extends Model
{
    protected $table = 'conges_permissions';

    public const TYPE_CONGE = 'conge';
    public const TYPE_PERMISSION = 'permission';

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_APPROUVE = 'approuve';
    public const STATUT_REFUSE = 'refuse';
    public const STATUT_ANNULE = 'annule';

    protected $fillable = [
        'enseignant_id',
        'etablissement_id',
        'type',
        'date_debut',
        'date_fin',
        'motif',
        'piece_justificative_path',
        'statut',
        'approuve_par',
        'date_approbation',
        'remplacant_id',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_approbation' => 'datetime',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function remplacant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'remplacant_id');
    }

    public function approuvePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approuve_par');
    }

    public function estApprouve(): bool
    {
        return $this->statut === self::STATUT_APPROUVE;
    }

    public function couvreDate($date): bool
    {
        $date = now()->parse($date)->toDateString();

        return $date >= $this->date_debut?->toDateString()
            && $date <= $this->date_fin?->toDateString();
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function scopeApprouves(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_APPROUVE);
    }

    public function scopePourEnseignant(Builder $query, int $enseignantId): Builder
    {
        return $query->where('enseignant_id', $enseignantId);
    }
}