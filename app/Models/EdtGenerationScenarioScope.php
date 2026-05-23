<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtGenerationScenarioScope extends Model
{
    protected $table = 'edt_generation_scenario_scopes';

    public $timestamps = false;

    protected $fillable = [
        'scenario_id',
        'scope_type',
        'scope_id',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(EdtGenerationScenario::class, 'scenario_id');
    }
}