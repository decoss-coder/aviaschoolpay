<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $fillable = [
        'etablissement_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'trimestre_id',
        'type_evaluation_id',
        'titre',
        'date_evaluation',
        'note_sur',
        'coefficient',
        'description',
        'statut',
        'notes_publiees',
        'fichier_sujet_path',
        'fichier_corrige_path',
    ];

    protected $casts = [
        'date_evaluation' => 'date',
        'note_sur' => 'decimal:2',
        'coefficient' => 'decimal:2',
        'notes_publiees' => 'boolean',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
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

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class);
    }

    public function typeEvaluation(): BelongsTo
    {
        return $this->belongsTo(TypeEvaluation::class, 'type_evaluation_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function moyenneClasse(): ?float
    {
        $avg = $this->notes()->whereNotNull('note')->avg('note');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    public function nbNotesSaisies(): int
    {
        return $this->notes()->count();
    }

    public function scopePubliees(Builder $query): Builder
    {
        return $query->where('notes_publiees', true);
    }

    public function scopeDeClasse(Builder $query, int $classeId): Builder
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeDeMatiere(Builder $query, int $matiereId): Builder
    {
        return $query->where('matiere_id', $matiereId);
    }

    public function scopeDeEnseignant(Builder $query, int $enseignantId): Builder
    {
        return $query->where('enseignant_id', $enseignantId);
    }

    public function scopeDeTrimestre(Builder $query, int $trimestreId): Builder
    {
        return $query->where('trimestre_id', $trimestreId);
    }
}