<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $fillable = [
        'evaluation_id',
        'eleve_id',
        'note',
        'absent',
        'dispense',
        'observation',
        'saisie_par',
        'date_saisie',
    ];

    protected $casts = [
        'note' => 'decimal:2',
        'absent' => 'boolean',
        'dispense' => 'boolean',
        'date_saisie' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function saisiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisie_par');
    }

    public function noteSur20(): ?float
    {
        if ($this->note === null || $this->absent || $this->dispense) {
            return null;
        }

        $bareme = (float) ($this->evaluation?->note_sur ?? 20);

        if ($bareme <= 0) {
            return null;
        }

        return $bareme == 20.0
            ? (float) $this->note
            : round(((float) $this->note / $bareme) * 20, 2);
    }

    public function estValidePourCalcul(): bool
    {
        return $this->note !== null && !$this->absent && !$this->dispense;
    }

    public function scopeValides(Builder $query): Builder
    {
        return $query
            ->whereNotNull('note')
            ->where('absent', false)
            ->where('dispense', false);
    }
}