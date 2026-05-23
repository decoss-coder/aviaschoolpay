<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSyncDedup extends Model
{
    protected $table = 'api_sync_dedup';

    protected $fillable = ['user_id', 'client_mutation_id', 'resource_type', 'resource_id', 'response_snapshot'];

    protected $casts = ['response_snapshot' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
