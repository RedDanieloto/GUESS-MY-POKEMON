<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    private function resolveApiUser(Request $request)
    {
        return auth('sanctum')->user() ?: $request->user();
    }

    /**
     * Resolve profile from authenticated user or player_token
     */
    private function resolveProfile(Request $request): ?PlayerProfile
    {
        // If user is authenticated, get their profile
        if ($user = $this->resolveApiUser($request)) {
            return PlayerProfile::query()->where('user_id', $user->id)->first();
        }

        // Otherwise, use player_token
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        return PlayerProfile::query()->where('session_id', $validated['player_token'])->first();
    }

    public function index(Request $request, AchievementService $achievementService): JsonResponse
    {
        $profile = $this->resolveProfile($request);

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
