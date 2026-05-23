<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdtGenerationRun extends Model
{
    protected $table = 'edt_generation_runs';

    protected $fillable = [
        'scenario_id',
        'etablissement_id',
        'annee_scolaire_id',
        'run_uuid',
        'status',
        'score_global',
        'summary_json',
        'conformite_json',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'summary_json' => 'array',
        'conformite_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(EdtGenerationIssue::class, 'run_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(EdtGenerationScenario::class, 'scenario_id');
    }
}