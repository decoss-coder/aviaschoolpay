<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtPolicyClassOverride extends Model
{
    protected $table = 'edt_policy_class_overrides';

    protected $fillable = [
        'policy_id',
        'classe_id',
        'niveau_reglementaire_code',
        'option_reglementaire_code',
        'total_cible_minutes',
        'total_min_minutes',
        'commentaire',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(EdtPolicy::class, 'policy_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }
}