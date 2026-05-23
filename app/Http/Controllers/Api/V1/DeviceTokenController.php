<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => 'required|in:ios,android,web',
            'token' => 'required|string|max:512',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'],
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['message' => 'Token enregistré.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string|max:512']);
        DeviceToken::where('user_id', $request->user()->id)->where('token', $data['token'])->delete();

        return response()->json(['message' => 'Token supprimé.']);
    }
}
