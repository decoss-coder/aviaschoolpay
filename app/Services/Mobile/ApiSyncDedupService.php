<?php

namespace App\Services\Mobile;

use App\Models\ApiSyncDedup;
use Illuminate\Http\Request;

class ApiSyncDedupService
{
    /**
     * Si client_mutation_id déjà traité pour cet utilisateur, retourne le snapshot JSON.
     */
    public static function replayIfExists(Request $request, string $resourceType): ?array
    {
        $mid = $request->input('client_mutation_id');
        if (!$mid || !is_string($mid) || strlen($mid) > 64) {
            return null;
        }

        $row = ApiSyncDedup::where('user_id', $request->user()->id)
            ->where('client_mutation_id', $mid)
            ->first();

        return $row?->response_snapshot;
    }

    public static function store(Request $request, string $resourceType, ?int $resourceId, array $response): void
    {
        $mid = $request->input('client_mutation_id');
        if (!$mid || !is_string($mid) || strlen($mid) > 64) {
            return;
        }

        ApiSyncDedup::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'client_mutation_id' => $mid,
            ],
            [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'response_snapshot' => $response,
            ]
        );
    }
}
