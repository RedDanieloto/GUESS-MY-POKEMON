<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request, AchievementService $achievementService): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        $profile = PlayerProfile::query()->where('session_id', $validated['player_token'])->first();

        if (! $profile) {
            return response()->json([
                'achievements' => null,
            ]);
        }

        $achievementService->syncUnlocks($profile);

        return response()->json([
            'achievements' => $achievementService->viewData($profile),
        ]);
    }
}
