<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\ProgressionService;
use App\Services\SpriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    private function playerTokenFromRequest(Request $request): string
    {
        return trim((string) ($request->input('player_token') ?: $request->query('player_token', '')));
    }

    private function resolveApiUser(Request $request)
    {
        return auth('sanctum')->user() ?: $request->user();
    }

    /**
     * Resolve the player profile from either authenticated user or player_token
     */
    private function resolveProfile(Request $request): ?PlayerProfile
    {
        // If user is authenticated, get or create their profile
        if ($user = $this->resolveApiUser($request)) {
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

        // Otherwise, check for player_token in request
        $validated = $request->validate(['player_token' => ['required', 'string', 'max:64']]);
        return PlayerProfile::query()->where('session_id', $validated['player_token'])->first();
    }

    public function upsert(Request $request, ProgressionService $progressionService): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['nullable', 'string', 'max:64'],
            'nickname' => ['nullable', 'string', 'min:2', 'max:40'],
            'experience_tier' => ['nullable', 'in:beginner,intermediate,expert'],
            'avatar_key' => ['nullable', 'string', 'max:40'],
        ]);

        // If user is authenticated, use their profile
        if ($user = $this->resolveApiUser($request)) {
            $profile = PlayerProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'session_id' => (string) Str::uuid(),
                    'nickname' => $validated['nickname'] ?? $user->name,
                    'experience_tier' => $validated['experience_tier'] ?? 'beginner',
                    'meta' => ['avatar_key' => $validated['avatar_key'] ?? 'trainer-a'],
                ]
            );

            $sessionId = $profile->session_id;
        } else {
            // Use player_token or create new one
            $sessionId = trim((string) ($validated['player_token'] ?? ''));
            if ($sessionId === '') {
                $sessionId = (string) Str::uuid();
            }

            $profile = PlayerProfile::query()->firstOrCreate(
                ['session_id' => $sessionId],
                [
                    'nickname' => $validated['nickname'] ?? null,
                    'experience_tier' => $validated['experience_tier'] ?? 'beginner',
                    'meta' => ['avatar_key' => $validated['avatar_key'] ?? 'trainer-a'],
                ]
            );
        }

        $profile->fill([
            'nickname' => $validated['nickname'] ?? $profile->nickname,
            'experience_tier' => $validated['experience_tier'] ?? $profile->experience_tier,
            'meta' => [
                ...($profile->meta ?? []),
                'avatar_key' => $validated['avatar_key'] ?? (($profile->meta['avatar_key'] ?? null) ?: 'trainer-a'),
            ],
        ]);
        $profile->save();

        return response()->json([
            'player_token' => $sessionId,
            'profile' => [
                ...$progressionService->profilePayload($profile),
                'avatar_key' => $profile->meta['avatar_key'] ?? 'trainer-a',
            ],
            'avatar_catalog' => self::avatarCatalog(),
        ]);
    }

    public function show(Request $request, ProgressionService $progressionService): JsonResponse
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
                    }
                }

                return response()->json([
                    'player_token' => $tokenProfile->session_id,
                    'profile' => [
                        ...$progressionService->profilePayload($tokenProfile),
                        'avatar_key' => $tokenProfile->meta['avatar_key'] ?? 'trainer-a',
                    ],
                    'avatar_catalog' => self::avatarCatalog(),
                ]);
            }
        }

        // If user is authenticated, show their profile
        if ($user = $this->resolveApiUser($request)) {
            $profile = PlayerProfile::query()
                ->where('user_id', $user->id)
                ->latest('updated_at')
                ->first();

            if (! $profile) {
                return response()->json([
                    'profile' => null,
                    'avatar_catalog' => self::avatarCatalog(),
                ]);
            }

            return response()->json([
                'player_token' => $profile->session_id,
                'profile' => [
                    ...$progressionService->profilePayload($profile),
                    'avatar_key' => $profile->meta['avatar_key'] ?? 'trainer-a',
                ],
                'avatar_catalog' => self::avatarCatalog(),
            ]);
        }

        $profile = $playerToken !== ''
            ? PlayerProfile::query()->where('session_id', $playerToken)->first()
            : null;

        if (! $profile) {
            return response()->json([
                'profile' => null,
                'avatar_catalog' => self::avatarCatalog(),
            ]);
        }

        return response()->json([
            'profile' => [
                ...$progressionService->profilePayload($profile),
                'avatar_key' => $profile->meta['avatar_key'] ?? 'trainer-a',
            ],
            'avatar_catalog' => self::avatarCatalog(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function avatarCatalog(): array
    {
        return [
            'trainer-a' => SpriteService::pokemonSpriteUrl(25, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png'),
            'trainer-b' => SpriteService::pokemonSpriteUrl(6, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png'),
            'trainer-c' => SpriteService::pokemonSpriteUrl(94, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/94.png'),
            'trainer-d' => SpriteService::pokemonSpriteUrl(131, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/131.png'),
            'trainer-e' => SpriteService::pokemonSpriteUrl(150, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/150.png'),
        ];
    }
}
