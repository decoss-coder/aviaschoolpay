<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdtGenerationScenario extends Model
{
    protected $table = 'edt_generation_scenarios';

    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'policy_id',
        'nom',
        'mode_generation',
        'portee',
        'jours_json',
        'creneaux_json',
        'salles_json',
        'options_json',
        'created_by',
    ];

    protected $casts = [
        'jours_json' => 'array',
        'creneaux_json' => 'array',
        'salles_json' => 'array',
        'options_json' => 'array',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(EdtPolicy::class, 'policy_id');
    }

    public function constraints(): HasMany
    {
        return $this->hasMany(EdtGenerationScenarioConstraint::class, 'scenario_id');
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(EdtGenerationScenarioScope::class, 'scenario_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(EdtGenerationRun::class, 'scenario_id');
    }
}