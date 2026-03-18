<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\GachaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GachaController extends Controller
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

        // Prefer the active player token profile when available to avoid session/profile drift.
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

        // If authenticated and no usable token profile, use the most recent profile linked to user.
        if ($user = $this->resolveApiUser($request)) {
            return $this->ensureUserProfile($user);
        }

        if ($playerToken === '') {
            return null;
        }

        return PlayerProfile::query()->where('session_id', $playerToken)->first();
    }

    public function index(Request $request, GachaService $gachaService): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        if (! $profile) {
            return response()->json(['gacha' => null]);
        }

        return response()->json([
            'gacha' => $gachaService->queueView($profile),
            'collection' => $gachaService->collectionView($profile),
        ]);
    }

    public function open(Request $request, GachaService $gachaService): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        if (! $profile) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        $reward = $gachaService->openNext($profile);

        if (! $reward) {
            return response()->json(['message' => 'No tienes cápsulas pendientes'], 422);
        }

        return response()->json([
            'reward' => $gachaService->rewardPayload($reward),
            'gacha' => $gachaService->queueView($profile),
            'collection' => $gachaService->collectionView($profile),
        ]);
    }
}
