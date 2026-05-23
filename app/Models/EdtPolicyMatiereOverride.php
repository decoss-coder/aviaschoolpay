<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtPolicyMatiereOverride extends Model
{
    protected $table = 'edt_policy_matiere_overrides';

    protected $fillable = [
        'policy_id',
        'classe_id',
        'niveau_reglementaire_code',
        'option_reglementaire_code',
        'matiere_id',
        'enabled',
        'volume_cible_minutes',
        'volume_min_minutes',
        'priorite',
        'motif',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(EdtPolicy::class, 'policy_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }
}