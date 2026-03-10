<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameRoom;
use App\Models\PlayerProfile;
use App\Models\Pokemon;
use App\Models\RoomPlayer;
use App\Models\RoomQuestion;
use App\Services\AchievementService;
use App\Services\ProgressionService;
use App\Services\QuestionCatalog;
use App\Services\QuestionEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:online,vs'],
            'difficulty' => ['required', 'in:easy,normal,hard'],
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
            'visibility' => ['nullable', 'in:public,private'],
            'room_name' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'in:es,en'],
            'player_token' => ['nullable', 'string', 'max:64'],
        ]);

        $sessionId = $this->resolveSessionId($validated['player_token'] ?? null);
        $mode = $validated['mode'];

        $difficulty = $validated['difficulty'];
        if ($mode === 'online' && $difficulty === 'hard') {
            $difficulty = 'normal';
        }
        if ($mode === 'vs' && $difficulty === 'normal') {
            $difficulty = 'hard';
        }

        $visibility = $validated['visibility'] ?? 'private';

        $room = GameRoom::query()->create([
            'code' => $this->generateRoomCode(),
            'mode' => $mode,
            'difficulty' => $difficulty,
            'visibility' => $visibility,
            'room_name' => trim((string) ($validated['room_name'] ?? '')) ?: null,
            'language' => $validated['language'] ?? 'es',
            'status' => 'waiting',
            'host_session_id' => $sessionId,
            'turn_session_id' => $mode === 'vs' ? $sessionId : null,
        ]);

        RoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'session_id' => $sessionId,
            'nickname' => $validated['nickname'],
            'role' => $mode === 'online' ? 'host' : 'player1',
            'joined_at' => now(),
        ]);
        $this->ensureProfile($sessionId, $validated['nickname']);

        return response()->json([
            'player_token' => $sessionId,
            'room' => $this->roomPayload($room->fresh(), $sessionId),
        ]);
    }

    public function publicRooms(Request $request): JsonResponse
    {
        $mode = (string) $request->query('mode', 'online');
        $language = $request->query('lang', 'es') === 'en' ? 'en' : 'es';

        $rooms = GameRoom::query()
            ->where('mode', $mode === 'vs' ? 'vs' : 'online')
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'active'])
            ->latest()
            ->limit(30)
            ->get();

        $payload = $rooms->map(function (GameRoom $room) use ($language): array {
            $playersCount = RoomPlayer::query()->where('game_room_id', $room->id)->count();

            return [
                'code' => $room->code,
                'mode' => $room->mode,
                'difficulty' => $room->difficulty,
                'visibility' => $room->visibility,
                'room_name' => $room->room_name,
                'language' => $room->language,
                'status' => $room->status,
                'players_count' => $playersCount,
                'is_joinable' => $playersCount < 2 && $room->status !== 'finished',
                'type_labels' => QuestionCatalog::typeLabels($language),
            ];
        });

        return response()->json([
            'rooms' => $payload,
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
            'player_token' => ['nullable', 'string', 'max:64'],
        ]);

        $sessionId = $this->resolveSessionId($validated['player_token'] ?? null);
        $room = GameRoom::query()->where('code', strtoupper($validated['code']))->firstOrFail();

        $existing = RoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('session_id', $sessionId)
            ->first();

        if ($existing) {
            $existing->update(['nickname' => $validated['nickname']]);
            $this->ensureProfile($sessionId, $validated['nickname']);

            return response()->json([
                'player_token' => $sessionId,
                'room' => $this->roomPayload($room->fresh(), $sessionId),
            ]);
        }

        $currentPlayers = RoomPlayer::query()->where('game_room_id', $room->id)->count();
        if ($currentPlayers >= 2) {
            return response()->json(['message' => 'La sala ya está llena'], 422);
        }

        $role = $room->mode === 'online' ? 'guesser' : 'player2';

        RoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'session_id' => $sessionId,
            'nickname' => $validated['nickname'],
            'role' => $role,
            'joined_at' => now(),
        ]);
        $this->ensureProfile($sessionId, $validated['nickname']);

        $room->status = 'active';
        $room->save();

        return response()->json([
            'player_token' => $sessionId,
            'room' => $this->roomPayload($room->fresh(), $sessionId),
        ]);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $sessionId = (string) $request->query('player_token', '');
        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();

        return response()->json([
            'room' => $this->roomPayload($room, $sessionId),
        ]);
    }

    public function selectHidden(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
            'pokemon_id' => ['required', 'exists:pokemons,id'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();
        $player = $this->roomPlayerOrFail($room, $validated['player_token']);

        if ($room->mode === 'online' && $player->role !== 'host') {
            return response()->json(['message' => 'Solo el dueño puede definir el Pokémon oculto'], 403);
        }

        $player->hidden_pokemon_id = (int) $validated['pokemon_id'];
        $player->save();

        if ($room->mode === 'online') {
            $hasGuesser = RoomPlayer::query()->where('game_room_id', $room->id)->where('role', 'guesser')->exists();
            if ($hasGuesser) {
                $room->status = 'active';
                $room->save();
            }
        }

        if ($room->mode === 'vs') {
            $allReady = RoomPlayer::query()
                ->where('game_room_id', $room->id)
                ->whereNotNull('hidden_pokemon_id')
                ->count() === 2;
            if ($allReady) {
                $room->status = 'active';
                $room->save();
            }
        }

        return response()->json([
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    public function ask(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
            'question_key' => ['nullable', 'string', 'max:80'],
            'question_text' => ['nullable', 'string', 'max:200'],
            'lang' => ['nullable', 'in:es,en'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();

        if ($room->status === 'finished') {
            return response()->json(['message' => 'La partida ya terminó'], 422);
        }

        $player = $this->roomPlayerOrFail($room, $validated['player_token']);
        $questionCatalog = QuestionCatalog::all();

        if ($room->mode === 'online' && $player->role !== 'guesser') {
            return response()->json(['message' => 'Solo quien adivina puede preguntar'], 403);
        }

        if ($room->mode === 'vs' && $room->turn_session_id !== $player->session_id) {
            return response()->json(['message' => 'No es tu turno para preguntar'], 403);
        }

        $questionKey = $validated['question_key'] ?? null;
        $text = trim((string) ($validated['question_text'] ?? ''));

        if (! $questionKey && $text === '') {
            return response()->json(['message' => 'Debes enviar una pregunta'], 422);
        }

        if ($questionKey && ! array_key_exists($questionKey, $questionCatalog)) {
            return response()->json(['message' => 'Pregunta estructurada inválida'], 422);
        }

        if ($questionKey) {
            $text = QuestionCatalog::labelFor($questionKey, $validated['lang'] ?? $room->language ?? 'es')
                ?? $questionCatalog[$questionKey]['label'];
        }

        $target = $this->targetPlayerForQuestion($room, $player);

        RoomQuestion::query()->create([
            'game_room_id' => $room->id,
            'asked_by_session_id' => $player->session_id,
            'target_session_id' => $target?->session_id,
            'question_key' => $questionKey,
            'question_text' => $text,
            'meta' => ['kind' => 'question'],
        ]);
        $this->incrementProfileStat($player->session_id, 'questions_asked', 1);
        $this->awardExperience($player->session_id, $room, 8);

        return response()->json([
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    public function answer(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
            'question_id' => ['required', 'integer', 'exists:room_questions,id'],
            'answer' => ['required', 'in:yes,no,unknown'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();
        $player = $this->roomPlayerOrFail($room, $validated['player_token']);

        $question = RoomQuestion::query()
            ->where('game_room_id', $room->id)
            ->where('id', $validated['question_id'])
            ->firstOrFail();

        if ($question->target_session_id !== $player->session_id) {
            return response()->json(['message' => 'No puedes responder esta pregunta'], 403);
        }

        if ($question->answer !== null) {
            return response()->json(['message' => 'Esta pregunta ya fue respondida'], 422);
        }

        $question->answer = $validated['answer'];
        $question->answered_at = now();
        $question->save();
        $this->incrementProfileStat($player->session_id, 'questions_answered', 1);
        $this->awardExperience($player->session_id, $room, 6);

        if ($room->mode === 'vs' && $room->status === 'active') {
            $room->turn_session_id = $player->session_id;
            $room->save();
        }

        return response()->json([
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    public function guess(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
            'pokemon_id' => ['required', 'exists:pokemons,id'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();
        if ($room->status === 'finished') {
            return response()->json(['message' => 'La partida ya terminó'], 422);
        }
        $player = $this->roomPlayerOrFail($room, $validated['player_token']);

        if ($room->mode === 'vs' && $room->turn_session_id !== $player->session_id) {
            return response()->json(['message' => 'No es tu turno para adivinar'], 403);
        }

        $target = $this->targetPlayerForQuestion($room, $player);
        if (! $target || ! $target->hidden_pokemon_id) {
            return response()->json(['message' => 'El rival todavía no eligió su Pokémon'], 422);
        }

        $guessPokemon = Pokemon::query()->findOrFail((int) $validated['pokemon_id']);
        $correct = $target->hidden_pokemon_id === (int) $validated['pokemon_id'];

        RoomQuestion::query()->create([
            'game_room_id' => $room->id,
            'asked_by_session_id' => $player->session_id,
            'target_session_id' => $target->session_id,
            'question_text' => 'Adivino: '.$guessPokemon->display_name,
            'answer' => $correct ? 'yes' : 'no',
            'answered_at' => now(),
            'meta' => [
                'kind' => 'guess',
                'guessed_pokemon_id' => $guessPokemon->id,
                'correct' => $correct,
            ],
        ]);
        $this->incrementProfileStat($player->session_id, 'guesses_made', 1);

        if ($correct) {
            $this->incrementProfileStat($player->session_id, 'correct_guesses', 1);
            $room->status = 'finished';
            $room->winner_session_id = $player->session_id;
            $room->save();
            $this->finalizeRoomExperience($room, $player->session_id);
        } elseif ($room->mode === 'vs' && $room->status === 'active') {
            $room->turn_session_id = $target->session_id;
            $room->save();
            $this->awardExperience($player->session_id, $room, 10);
        }

        return response()->json([
            'correct' => $correct,
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    private function roomPayload(GameRoom $room, string $sessionId): array
    {
        $room->load([
            'players.hiddenPokemon:id,display_name,pokeapi_id,primary_type,secondary_type,generation,sprites',
            'questions' => fn ($query) => $query->latest()->limit(100),
        ]);

        $players = $room->players->sortBy('id')->values();
        $me = $players->firstWhere('session_id', $sessionId);

        $playersPayload = $players->map(function (RoomPlayer $player) use ($sessionId): array {
            return [
                'id' => $player->id,
                'session_id' => $player->session_id,
                'nickname' => $player->nickname,
                'role' => $player->role,
                'is_me' => $player->session_id === $sessionId,
                'has_hidden_pokemon' => (bool) $player->hidden_pokemon_id,
                'hidden_pokemon' => $player->session_id === $sessionId ? $player->hiddenPokemon : null,
            ];
        });

        $nicknameBySession = $players->pluck('nickname', 'session_id')->all();

        $questionsPayload = $room->questions
            ->sortBy('id')
            ->values()
            ->map(function (RoomQuestion $question) use ($sessionId, $nicknameBySession): array {
                return [
                    'id' => $question->id,
                    'question_key' => $question->question_key,
                    'question_text' => $question->question_text,
                    'answer' => $question->answer,
                    'asked_by_session_id' => $question->asked_by_session_id,
                    'asked_by_name' => $nicknameBySession[$question->asked_by_session_id] ?? 'Jugador',
                    'target_session_id' => $question->target_session_id,
                    'target_name' => $nicknameBySession[$question->target_session_id] ?? null,
                    'created_at' => $question->created_at?->toIso8601String(),
                    'is_pending_for_me' => $question->target_session_id === $sessionId && $question->answer === null,
                    'meta' => $question->meta,
                ];
            });

        return [
            'id' => $room->id,
            'code' => $room->code,
            'mode' => $room->mode,
            'difficulty' => $room->difficulty,
            'visibility' => $room->visibility,
            'room_name' => $room->room_name,
            'language' => $room->language,
            'status' => $room->status,
            'turn_session_id' => $room->turn_session_id,
            'winner_session_id' => $room->winner_session_id,
            'am_i_in_room' => (bool) $me,
            'my_role' => $me?->role,
            'my_session_id' => $sessionId,
            'players' => $playersPayload,
            'questions' => $questionsPayload,
            'question_catalog' => QuestionCatalog::all(),
            'remaining_hint' => $me ? $this->remainingHint($room, $me) : null,
            'pokedex_loaded' => Pokemon::query()->count(),
            'my_profile' => $this->profilePayloadFor($sessionId),
        ];
    }

    private function remainingHint(GameRoom $room, RoomPlayer $me): ?array
    {
        if ($room->difficulty !== 'easy') {
            return null;
        }

        $target = $this->targetPlayerForQuestion($room, $me);
        if (! $target) {
            return null;
        }

        $answersQuery = RoomQuestion::query()
            ->where('game_room_id', $room->id)
            ->where('target_session_id', $target->session_id)
            ->whereNotNull('question_key')
            ->whereIn('answer', ['yes', 'no']);

        if ($room->mode === 'vs') {
            $answersQuery->where('asked_by_session_id', $me->session_id);
        }

        $answers = $answersQuery->get();

        $allPokemons = Pokemon::query()->orderBy('pokeapi_id')->get();
        $remaining = $allPokemons->filter(function (Pokemon $pokemon) use ($answers): bool {
            foreach ($answers as $answer) {
                $evaluation = QuestionEvaluator::evaluate((string) $answer->question_key, $pokemon);
                if ($evaluation === null || $evaluation !== $answer->answer) {
                    return false;
                }
            }

            return true;
        })->values();

        return [
            'count' => $remaining->count(),
            'items' => $remaining
                ->take(60)
                ->map(fn (Pokemon $pokemon): array => [
                    'id' => $pokemon->id,
                    'pokeapi_id' => $pokemon->pokeapi_id,
                    'display_name' => $pokemon->display_name,
                    'primary_type' => $pokemon->primary_type,
                    'secondary_type' => $pokemon->secondary_type,
                    'generation' => $pokemon->generation,
                    'sprite' => $pokemon->sprites['official_artwork'] ?? $pokemon->sprites['front_default'] ?? null,
                ])
                ->all(),
        ];
    }

    private function roomPlayerOrFail(GameRoom $room, string $sessionId): RoomPlayer
    {
        return RoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();
    }

    private function resolveSessionId(?string $provided): string
    {
        $value = trim((string) $provided);
        if ($value !== '') {
            return Str::limit($value, 64, '');
        }

        return Str::uuid()->toString();
    }

    private function generateRoomCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (GameRoom::query()->where('code', $code)->exists());

        return $code;
    }

    private function targetPlayerForQuestion(GameRoom $room, RoomPlayer $source): ?RoomPlayer
    {
        if ($room->mode === 'online') {
            return RoomPlayer::query()
                ->where('game_room_id', $room->id)
                ->where('role', 'host')
                ->first();
        }

        return RoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('session_id', '!=', $source->session_id)
            ->first();
    }

    private function ensureProfile(string $sessionId, ?string $nickname = null): PlayerProfile
    {
        $profile = PlayerProfile::query()->firstOrCreate(
            ['session_id' => $sessionId],
            [
                'nickname' => $nickname,
                'experience_tier' => 'beginner',
                'meta' => ['avatar_key' => 'trainer-a'],
            ]
        );

        if ($nickname && $profile->nickname !== $nickname) {
            $profile->nickname = $nickname;
            $profile->save();
        }

        return $profile;
    }

    private function awardExperience(string $sessionId, GameRoom $room, int $baseXp): void
    {
        $profile = $this->ensureProfile($sessionId);
        app(ProgressionService::class)->award($profile, $baseXp, (string) $room->difficulty);
        app(AchievementService::class)->syncUnlocks($profile);
    }

    private function finalizeRoomExperience(GameRoom $room, string $winnerSessionId): void
    {
        $profiles = [];
        $players = RoomPlayer::query()->where('game_room_id', $room->id)->get();

        foreach ($players as $player) {
            $profile = $this->ensureProfile($player->session_id, $player->nickname);
            $profile->games_played += 1;
            if ($player->session_id === $winnerSessionId) {
                $profile->wins += 1;
            }
            $profile->save();
            app(AchievementService::class)->syncUnlocks($profile);
            $profiles[] = $profile;
        }

        foreach ($profiles as $profile) {
            $isWinner = $profile->session_id === $winnerSessionId;
            app(ProgressionService::class)->award($profile, $isWinner ? 120 : 55, (string) $room->difficulty);
            app(AchievementService::class)->syncUnlocks($profile);
        }
    }

    private function profilePayloadFor(string $sessionId): ?array
    {
        $profile = PlayerProfile::query()->where('session_id', $sessionId)->first();
        if (! $profile) {
            return null;
        }

        $progression = app(ProgressionService::class)->profilePayload($profile);
        return [
            ...$progression,
            'avatar_key' => $profile->meta['avatar_key'] ?? 'trainer-a',
        ];
    }

    private function incrementProfileStat(string $sessionId, string $field, int $amount = 1): void
    {
        $allowed = ['questions_asked', 'questions_answered', 'guesses_made', 'correct_guesses'];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        $profile = $this->ensureProfile($sessionId);
        $profile->{$field} = (int) ($profile->{$field} ?? 0) + $amount;
        $profile->save();
        app(AchievementService::class)->syncUnlocks($profile);
    }
}
