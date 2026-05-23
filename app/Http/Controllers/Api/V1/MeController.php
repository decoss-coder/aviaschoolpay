<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiEnvelope;
use App\Support\ApiUserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiEnvelope::success(
            ApiUserProfile::toArray($request->user()),
            'Profil utilisateur.'
        );
    }
}
