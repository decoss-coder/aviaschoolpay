<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtGenerationScenarioConstraint extends Model
{
    protected $table = 'edt_generation_scenario_constraints';

    protected $fillable = [
        'scenario_id',
        'constraint_id',
        'enabled',
        'weight',
        'params_json',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'weight' => 'decimal:2',
        'params_json' => 'array',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(EdtGenerationScenario::class, 'scenario_id');
    }

    public function constraint(): BelongsTo
    {
        return $this->belongsTo(EdtConstraintCatalog::class, 'constraint_id');
    }
}