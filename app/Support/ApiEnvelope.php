<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Enveloppe JSON unique pour l’API mobile (/api/v1).
 */
class ApiEnvelope
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data ?? new \stdClass,
            'message' => $message,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function fail(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors === [] ? new \stdClass : $errors,
        ], $status);
    }
}
