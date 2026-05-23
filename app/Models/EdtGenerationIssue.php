<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtGenerationIssue extends Model
{
    protected $table = 'edt_generation_issues';

    protected $fillable = [
        'run_id',
        'niveau',
        'issue_code',
        'scope_type',
        'scope_id',
        'message',
        'details_json',
    ];

    protected $casts = [
        'details_json' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(EdtGenerationRun::class, 'run_id');
    }
}