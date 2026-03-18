<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AchievementController extends Controller
{
        private function ensureUserProfile($user): PlayerProfile
        {
            return PlayerProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'session_id' => (string) Str::uuid(),
                    'nickname' => $user->name,
                    'experience_tier' => 'beginner',
                    'meta' => ['avatar_key' => 'trainer-a'],
                ]
            );
        }

    private function resolveApiUser(Request $request)
    {
        return auth('sanctum')->user() ?: $request->user();
    }

    private function playerTokenFromRequest(Request $request): string
    {
        return trim((string) ($request->input('player_token') ?: $request->query('player_token', '')));
    }

    /**
     * Resolve profile from authenticated user or player_token
     */
    private function resolveProfile(Request $request): ?PlayerProfile
    {
        $playerToken = $this->playerTokenFromRequest($request);

        if ($playerToken !== '') {
            $tokenProfile = PlayerProfile::query()->where('session_id', $playerToken)->first();
            if ($tokenProfile) {
                if ($user = $this->resolveApiUser($request)) {
                    if ($tokenProfile->user_id === null || (int) $tokenProfile->user_id === (int) $user->id) {
                        if ($tokenProfile->user_id === null) {
                            $tokenProfile->user_id = $user->id;
                            $tokenProfile->save();
                        }

                        return $tokenProfile;
                    }

                    return $this->ensureUserProfile($user);
                }

                return $tokenProfile;
            }
        }

        if ($user = $this->resolveApiUser($request)) {
            return $this->ensureUserProfile($user);
        }

        if ($playerToken === '') {
            return null;
        }

        return PlayerProfile::query()->where('session_id', $playerToken)->first();
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
