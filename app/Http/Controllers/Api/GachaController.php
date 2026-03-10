<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\GachaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GachaController extends Controller
{
    public function index(Request $request, GachaService $gachaService): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        $profile = PlayerProfile::query()->where('session_id', $validated['player_token'])->first();

        if (! $profile) {
            return response()->json(['gacha' => null]);
        }

        return response()->json([
            'gacha' => $gachaService->queueView($profile),
        ]);
    }

    public function open(Request $request, GachaService $gachaService): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        $profile = PlayerProfile::query()->where('session_id', $validated['player_token'])->firstOrFail();
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
