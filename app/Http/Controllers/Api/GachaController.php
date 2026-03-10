<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\GachaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GachaController extends Controller
{
    /**
     * Resolve profile from authenticated user or player_token
     */
    private function resolveProfile(Request $request): ?PlayerProfile
    {
        // If user is authenticated, get their profile
        if ($user = $request->user()) {
            return PlayerProfile::query()->where('user_id', $user->id)->first();
        }

        // Otherwise, use player_token
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        return PlayerProfile::query()->where('session_id', $validated['player_token'])->first();
    }

    public function index(Request $request, GachaService $gachaService): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        if (! $profile) {
            return response()->json(['gacha' => null]);
        }

        return response()->json([
            'gacha' => $gachaService->queueView($profile),
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
        ]);
    }
}
