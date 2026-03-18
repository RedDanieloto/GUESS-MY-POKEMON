<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameRoom;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\GachaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function summary(): JsonResponse
    {
        $activeRooms = GameRoom::query()->whereIn('status', ['waiting', 'active'])->count();

        return response()->json([
            'summary' => [
                'total_users' => User::query()->count(),
                'total_admins' => User::query()->where('is_admin', true)->count(),
                'banned_users' => User::query()->where('is_banned', true)->count(),
                'active_rooms' => $activeRooms,
                'waiting_rooms' => GameRoom::query()->where('status', 'waiting')->count(),
                'live_rooms' => GameRoom::query()->where('status', 'active')->count(),
            ],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = User::query()->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->limit(100)->get(['id', 'name', 'email', 'is_admin', 'is_banned', 'banned_reason', 'created_at']);

        return response()->json(['users' => $users]);
    }

    public function setAdmin(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);

        $targetUser = User::query()->findOrFail($userId);
        $actor = auth('sanctum')->user();

        if ($actor && (int) $actor->id === (int) $targetUser->id && ! (bool) $validated['is_admin']) {
            return response()->json(['message' => 'No puedes quitarte admin a ti mismo'], 422);
        }

        $targetUser->is_admin = (bool) $validated['is_admin'];
        $targetUser->save();

        return response()->json([
            'message' => $targetUser->is_admin ? 'Usuario promovido a admin' : 'Permisos admin removidos',
            'user' => $targetUser->only(['id', 'name', 'email', 'is_admin']),
        ]);
    }

    public function banUser(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->findOrFail($userId);
        $user->is_banned = true;
        $user->banned_reason = trim((string) ($validated['reason'] ?? ''));
        $user->save();

        return response()->json([
            'message' => 'Usuario baneado',
            'user' => $user->only(['id', 'name', 'email', 'is_banned', 'banned_reason']),
        ]);
    }

    public function unbanUser(int $userId): JsonResponse
    {
        $user = User::query()->findOrFail($userId);
        $user->is_banned = false;
        $user->banned_reason = null;
        $user->save();

        return response()->json([
            'message' => 'Usuario desbaneado',
            'user' => $user->only(['id', 'name', 'email', 'is_banned', 'banned_reason']),
        ]);
    }

    public function grantCapsules(Request $request, int $userId, GachaService $gachaService): JsonResponse
    {
        $validated = $request->validate([
            'count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'rarity' => ['nullable', 'in:normal,rare,special,ultra,mythic,legendary,shiny'],
        ]);

        $user = User::query()->findOrFail($userId);
        $profile = PlayerProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'session_id' => (string) Str::uuid(),
                'nickname' => $user->name,
                'experience_tier' => 'beginner',
                'meta' => ['avatar_key' => 'trainer-a'],
            ]
        );

        $count = (int) ($validated['count'] ?? 1);
        $rarity = $validated['rarity'] ?? null;
        $gachaService->grantAdminRewards($profile, $count, $rarity);

        return response()->json([
            'message' => 'Cápsulas otorgadas',
            'granted' => $count,
            'rarity' => $rarity,
            'profile_id' => $profile->id,
        ]);
    }

    public function listRooms(Request $request): JsonResponse
    {
        $mode = trim((string) $request->query('mode', ''));
        $status = trim((string) $request->query('status', 'active_waiting'));
        $codeOrName = trim((string) $request->query('search', ''));

        $roomsQuery = GameRoom::query()
            ->with(['players:id,game_room_id,session_id,nickname,role,joined_at'])
            ->latest('updated_at');

        if (in_array($mode, ['online', 'vs', 'allvsbot'], true)) {
            $roomsQuery->where('mode', $mode);
        }

        if (in_array($status, ['waiting', 'active', 'finished'], true)) {
            $roomsQuery->where('status', $status);
        } elseif ($status === 'active_waiting') {
            $roomsQuery->whereIn('status', ['waiting', 'active']);
        }

        if ($codeOrName !== '') {
            $roomsQuery->where(function ($builder) use ($codeOrName): void {
                $builder->where('code', 'like', '%'.strtoupper($codeOrName).'%')
                    ->orWhere('room_name', 'like', '%'.$codeOrName.'%');
            });
        }

        $rooms = $roomsQuery->limit(160)->get();

        return response()->json([
            'rooms' => $rooms->map(fn (GameRoom $room): array => [
                'code' => $room->code,
                'mode' => $room->mode,
                'status' => $room->status,
                'difficulty' => $room->difficulty,
                'visibility' => $room->visibility,
                'room_name' => $room->room_name,
                'players_count' => $room->players->count(),
                'players' => $room->players->map(fn ($player): array => [
                    'session_id' => $player->session_id,
                    'nickname' => $player->nickname,
                    'role' => $player->role,
                    'joined_at' => $player->joined_at,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function spectateRoom(string $code): JsonResponse
    {
        $room = GameRoom::query()
            ->where('code', strtoupper($code))
            ->with([
                'players.hiddenPokemon:id,pokeapi_id,display_name,primary_type,secondary_type,sprites',
                'questions' => function ($query): void {
                    $query->latest('id')->limit(200);
                },
            ])
            ->firstOrFail();

        return response()->json([
            'room' => [
                'code' => $room->code,
                'mode' => $room->mode,
                'status' => $room->status,
                'difficulty' => $room->difficulty,
                'turn_session_id' => $room->turn_session_id,
                'winner_session_id' => $room->winner_session_id,
                'surrendered_by' => $room->surrendered_by,
                'players' => $room->players->map(fn ($player): array => [
                    'session_id' => $player->session_id,
                    'nickname' => $player->nickname,
                    'role' => $player->role,
                    'hidden_pokemon' => $player->hiddenPokemon ? [
                        'id' => $player->hiddenPokemon->id,
                        'pokeapi_id' => $player->hiddenPokemon->pokeapi_id,
                        'display_name' => $player->hiddenPokemon->display_name,
                        'primary_type' => $player->hiddenPokemon->primary_type,
                        'secondary_type' => $player->hiddenPokemon->secondary_type,
                        'sprite' => $player->hiddenPokemon->sprites['official_artwork'] ?? $player->hiddenPokemon->sprites['front_default'] ?? null,
                    ] : null,
                ])->values()->all(),
                'questions' => $room->questions->map(fn ($question): array => [
                    'id' => $question->id,
                    'asked_by_session_id' => $question->asked_by_session_id,
                    'target_session_id' => $question->target_session_id,
                    'question_text' => $question->question_text,
                    'question_key' => $question->question_key,
                    'answer' => $question->answer,
                    'asked_at' => $question->asked_at,
                    'answered_at' => $question->answered_at,
                ])->values()->all(),
            ],
        ]);
    }

    public function closeRoom(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'winner_session_id' => ['nullable', 'string', 'max:64'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();

        $winnerSessionId = trim((string) ($validated['winner_session_id'] ?? ''));
        if ($winnerSessionId !== '') {
            $exists = $room->players()->where('session_id', $winnerSessionId)->exists();
            if (! $exists) {
                return response()->json(['message' => 'winner_session_id no pertenece a la sala'], 422);
            }
            $room->winner_session_id = $winnerSessionId;
        }

        $room->status = 'finished';
        $room->surrendered_by = 'admin';
        $room->save();

        return response()->json([
            'message' => 'Sala cerrada por administrador',
            'room' => [
                'code' => $room->code,
                'status' => $room->status,
                'winner_session_id' => $room->winner_session_id,
            ],
        ]);
    }
}
