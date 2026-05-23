<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploiDuTempsAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'emploi_du_temps_id',
        'etablissement_id',
        'annee_scolaire_id',
        'user_id',
        'action',
        'generation_uuid',
        'old_payload',
        'new_payload',
        'reason',
        'used_for_learning',
    ];

    protected $casts = [
        'old_payload' => 'array',
        'new_payload' => 'array',
        'used_for_learning' => 'boolean',
    ];

    public function emploiDuTemps(): BelongsTo
    {
        return $this->belongsTo(EmploiDuTemps::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}