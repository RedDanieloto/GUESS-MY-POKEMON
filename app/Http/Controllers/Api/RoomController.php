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
            'mode' => ['required', 'in:online,vs,allvsbot'],
            'difficulty' => ['required', 'in:easy,normal,hard'],
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
            'visibility' => ['nullable', 'in:public,private'],
            'room_name' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'in:es,en'],
            'player_token' => ['nullable', 'string', 'max:64'],
            'question_limit_per_player' => ['nullable', 'integer', 'min:1', 'max:20'],
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

        if ($mode === 'allvsbot' && $difficulty === 'hard') {
            $difficulty = 'normal';
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
            'turn_session_id' => in_array($mode, ['vs', 'allvsbot'], true) ? $sessionId : null,
            'bot_pokemon_id' => $mode === 'allvsbot' ? Pokemon::query()->inRandomOrder()->value('id') : null,
            'question_limit_per_player' => $mode === 'allvsbot' ? (int) ($validated['question_limit_per_player'] ?? 3) : null,
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
        $modeFilter = in_array($mode, ['online', 'vs', 'allvsbot'], true) ? $mode : 'online';

        $rooms = GameRoom::query()
            ->where('mode', $modeFilter)
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'active'])
            ->latest()
            ->limit(30)
            ->get();

        $payload = $rooms->map(function (GameRoom $room) use ($language): array {
            $playersCount = RoomPlayer::query()->where('game_room_id', $room->id)->count();
            $maxPlayers = in_array($room->mode, ['online', 'allvsbot'], true) ? 4 : 2;

            return [
                'code' => $room->code,
                'mode' => $room->mode,
                'difficulty' => $room->difficulty,
                'visibility' => $room->visibility,
                'room_name' => $room->room_name,
                'language' => $room->language,
                'status' => $room->status,
                'players_count' => $playersCount,
                'max_players' => $maxPlayers,
                'is_joinable' => $playersCount < $maxPlayers && $room->status !== 'finished',
                'question_limit_per_player' => $room->question_limit_per_player,
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

        $maxPlayers = in_array($room->mode, ['online', 'allvsbot'], true) ? 4 : 2;
        $currentPlayers = RoomPlayer::query()->where('game_room_id', $room->id)->count();
        if ($currentPlayers >= $maxPlayers) {
            return response()->json(['message' => 'La sala ya está llena'], 422);
        }

        if ($room->mode === 'online') {
            $role = 'guesser';
        } elseif ($room->mode === 'allvsbot') {
            $role = match ($currentPlayers + 1) {
                2 => 'player2',
                3 => 'player3',
                default => 'player4',
            };
        } else {
            $role = 'player2';
        }

        RoomPlayer::query()->create([
            'game_room_id' => $room->id,
            'session_id' => $sessionId,
            'nickname' => $validated['nickname'],
            'role' => $role,
            'joined_at' => now(),
        ]);
        $this->ensureProfile($sessionId, $validated['nickname']);

        if ($room->mode === 'allvsbot') {
            // Start the match when at least 2 players are present.
            if (($currentPlayers + 1) >= 2) {
                $room->status = 'active';
                $room->turn_session_id = $room->turn_session_id ?: $room->host_session_id;
                $room->save();
            }
        } else {
            $room->status = 'active';
            $room->save();
        }

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

        if ($room->mode === 'allvsbot' && $room->turn_session_id !== $player->session_id) {
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

        if ($room->mode === 'allvsbot' && ! $questionKey) {
            return response()->json(['message' => 'En allvsbot solo se permiten preguntas estructuradas'], 422);
        }

        if ($room->mode === 'allvsbot') {
            $limit = max(1, (int) ($room->question_limit_per_player ?? 3));
            $used = RoomQuestion::query()
                ->where('game_room_id', $room->id)
                ->where('asked_by_session_id', $player->session_id)
                ->where('meta->kind', 'question')
                ->count();

            if ($used >= $limit) {
                return response()->json(['message' => 'Ya usaste todas tus preguntas disponibles'], 422);
            }
        }

        if ($questionKey) {
            $text = QuestionCatalog::labelFor($questionKey, $validated['lang'] ?? $room->language ?? 'es')
                ?? $questionCatalog[$questionKey]['label'];
        }

        $target = $this->targetPlayerForQuestion($room, $player);

        // Auto-evaluate structured questions using the hidden Pokémon
        $autoAnswer = null;
        if ($room->mode === 'allvsbot' && $questionKey && $room->bot_pokemon_id) {
            $hiddenPokemon = Pokemon::query()->find($room->bot_pokemon_id);
            if ($hiddenPokemon) {
                $autoAnswer = QuestionEvaluator::evaluate($questionKey, $hiddenPokemon);
            }
        } elseif ($questionKey && $target && $target->hidden_pokemon_id) {
            $hiddenPokemon = $target->hiddenPokemon;
            if ($hiddenPokemon) {
                $autoAnswer = QuestionEvaluator::evaluate($questionKey, $hiddenPokemon);
            }
        }

        $question = RoomQuestion::query()->create([
            'game_room_id' => $room->id,
            'asked_by_session_id' => $player->session_id,
            'target_session_id' => $target?->session_id,
            'question_key' => $questionKey,
            'question_text' => $text,
            'answer' => $autoAnswer,
            'answered_at' => $autoAnswer ? now() : null,
            'meta' => ['kind' => 'question', 'auto_answered' => (bool) $autoAnswer],
        ]);
        $this->incrementProfileStat($player->session_id, 'questions_asked', 1);
        $this->awardExperience($player->session_id, $room, 8);

        if ($autoAnswer && $target) {
            $this->incrementProfileStat($target->session_id, 'questions_answered', 1);
            $this->awardExperience($target->session_id, $room, 6);

            // In VS/allvsbot mode, pass the turn after auto-answer
            if (in_array($room->mode, ['vs', 'allvsbot'], true) && $room->status === 'active') {
                $room->turn_session_id = $target->session_id;
                $room->save();
            }
        }

        if ($room->mode === 'allvsbot' && $room->status === 'active') {
            $this->advanceAllVsBotTurn($room, $player->session_id);
        }

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

        if ($room->mode === 'allvsbot' && $room->turn_session_id !== $player->session_id) {
            return response()->json(['message' => 'No es tu turno para adivinar'], 403);
        }

        $target = $this->targetPlayerForQuestion($room, $player);
        if ($room->mode === 'allvsbot') {
            if (! $room->bot_pokemon_id) {
                return response()->json(['message' => 'La partida no tiene Pokémon objetivo'], 422);
            }
        } elseif (! $target || ! $target->hidden_pokemon_id) {
            return response()->json(['message' => 'El rival todavía no eligió su Pokémon'], 422);
        }

        $guessPokemon = Pokemon::query()->findOrFail((int) $validated['pokemon_id']);
        $correct = $room->mode === 'allvsbot'
            ? ((int) $room->bot_pokemon_id === (int) $validated['pokemon_id'])
            : ($target->hidden_pokemon_id === (int) $validated['pokemon_id']);

        RoomQuestion::query()->create([
            'game_room_id' => $room->id,
            'asked_by_session_id' => $player->session_id,
            'target_session_id' => $target?->session_id,
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
        } elseif ($room->mode === 'allvsbot' && $room->status === 'active') {
            $this->awardExperience($player->session_id, $room, 10);
            $this->advanceAllVsBotTurn($room, $player->session_id);
        }

        return response()->json([
            'correct' => $correct,
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    public function surrender(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();

        if ($room->status === 'finished') {
            return response()->json(['message' => 'La partida ya terminó'], 422);
        }

        $player = $this->roomPlayerOrFail($room, $validated['player_token']);

        $opponent = RoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('session_id', '!=', $player->session_id)
            ->first();

        $room->status = 'finished';
        $room->surrendered_by = $player->session_id;
        $room->winner_session_id = $opponent?->session_id;
        $room->save();

        if ($opponent) {
            $this->finalizeRoomExperience($room, $opponent->session_id);
        }

        return response()->json([
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    public function timerPropose(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();

        if ($room->status === 'finished') {
            return response()->json(['message' => 'La partida ya terminó'], 422);
        }

        $player = $this->roomPlayerOrFail($room, $validated['player_token']);

        $playersCount = RoomPlayer::query()->where('game_room_id', $room->id)->count();
        if ($playersCount !== 2) {
            return response()->json(['message' => 'El reloj solo está disponible en partidas de 2 jugadores'], 422);
        }

        if ($room->timer_enabled) {
            return response()->json(['message' => 'El reloj ya está activo'], 422);
        }

        $room->timer_proposed_by = $player->session_id;
        $room->save();

        return response()->json([
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    public function timerAccept(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'player_token' => ['required', 'string', 'max:64'],
            'accept' => ['required', 'boolean'],
        ]);

        $room = GameRoom::query()->where('code', strtoupper($code))->firstOrFail();

        if ($room->status === 'finished') {
            return response()->json(['message' => 'La partida ya terminó'], 422);
        }

        $player = $this->roomPlayerOrFail($room, $validated['player_token']);

        $playersCount = RoomPlayer::query()->where('game_room_id', $room->id)->count();
        if ($playersCount !== 2) {
            return response()->json(['message' => 'El reloj solo está disponible en partidas de 2 jugadores'], 422);
        }

        if ($room->timer_enabled) {
            return response()->json(['message' => 'El reloj ya está activo'], 422);
        }

        if (! $room->timer_proposed_by) {
            return response()->json(['message' => 'No hay propuesta de reloj pendiente'], 422);
        }

        if ($room->timer_proposed_by === $player->session_id) {
            return response()->json(['message' => 'No puedes aceptar tu propia propuesta'], 422);
        }

        if ($validated['accept']) {
            $room->timer_enabled = true;
            $room->timer_p1_remaining = $room->timer_seconds;
            $room->timer_p2_remaining = $room->timer_seconds;
            $room->timer_last_tick = now();
            $room->timer_proposed_by = null;
        } else {
            $room->timer_proposed_by = null;
        }

        $room->save();

        return response()->json([
            'room' => $this->roomPayload($room->fresh(), $validated['player_token']),
        ]);
    }

    private function tickTimer(GameRoom $room): void
    {
        if (! $room->timer_enabled || $room->status !== 'active' || ! $room->timer_last_tick) {
            return;
        }

        $elapsed = (int) now()->diffInSeconds($room->timer_last_tick);
        if ($elapsed <= 0) {
            return;
        }

        $players = RoomPlayer::query()->where('game_room_id', $room->id)->orderBy('id')->get();
        if ($players->count() !== 2) {
            return;
        }

        $p1 = $players->first();

        if ($room->turn_session_id === $p1->session_id) {
            $room->timer_p1_remaining = max(0, (int) $room->timer_p1_remaining - $elapsed);
        } else {
            $room->timer_p2_remaining = max(0, (int) $room->timer_p2_remaining - $elapsed);
        }

        $room->timer_last_tick = now();

        if ($room->timer_p1_remaining <= 0 || $room->timer_p2_remaining <= 0) {
            $room->status = 'finished';
            $loser = $room->timer_p1_remaining <= 0 ? $p1 : $players->last();
            $winner = $room->timer_p1_remaining <= 0 ? $players->last() : $p1;
            $room->winner_session_id = $winner->session_id;
            $room->save();
            $this->finalizeRoomExperience($room, $winner->session_id);
            return;
        }

        $room->save();
    }

    private function roomPayload(GameRoom $room, string $sessionId): array
    {
        $this->tickTimer($room);

        $room->load([
            'players.hiddenPokemon',
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

        $p1Session = $players->first()?->session_id;

        return [
            'id' => $room->id,
            'code' => $room->code,
            'mode' => $room->mode,
            'max_players' => in_array($room->mode, ['online', 'allvsbot'], true) ? 4 : 2,
            'difficulty' => $room->difficulty,
            'visibility' => $room->visibility,
            'room_name' => $room->room_name,
            'language' => $room->language,
            'status' => $room->status,
            'turn_session_id' => $room->turn_session_id,
            'winner_session_id' => $room->winner_session_id,
            'surrendered_by' => $room->surrendered_by,
            'am_i_in_room' => (bool) $me,
            'my_role' => $me?->role,
            'my_session_id' => $sessionId,
            'players' => $playersPayload,
            'questions' => $questionsPayload,
            'question_catalog' => QuestionCatalog::all(),
            'remaining_hint' => $me ? $this->remainingHint($room, $me) : null,
            'pokedex_loaded' => Pokemon::query()->count(),
            'my_profile' => $this->profilePayloadFor($sessionId),
            'allvsbot' => $room->mode === 'allvsbot' ? [
                'question_limit_per_player' => (int) ($room->question_limit_per_player ?? 3),
                'my_questions_used' => RoomQuestion::query()
                    ->where('game_room_id', $room->id)
                    ->where('asked_by_session_id', $sessionId)
                    ->where('meta->kind', 'question')
                    ->count(),
            ] : null,
            'timer' => [
                'enabled' => (bool) $room->timer_enabled,
                'seconds' => (int) $room->timer_seconds,
                'proposed_by' => $room->timer_proposed_by,
                'proposed_by_name' => $room->timer_proposed_by ? ($nicknameBySession[$room->timer_proposed_by] ?? null) : null,
                'my_remaining' => $me ? ($me->session_id === $p1Session ? $room->timer_p1_remaining : $room->timer_p2_remaining) : null,
                'opponent_remaining' => $me ? ($me->session_id === $p1Session ? $room->timer_p2_remaining : $room->timer_p1_remaining) : null,
            ],
        ];
    }

    private function remainingHint(GameRoom $room, RoomPlayer $me): ?array
    {
        if ($room->difficulty !== 'easy') {
            return null;
        }

        $answersQuery = RoomQuestion::query()
            ->where('game_room_id', $room->id)
            ->whereNotNull('question_key')
            ->whereIn('answer', ['yes', 'no']);

        if ($room->mode !== 'allvsbot') {
            $target = $this->targetPlayerForQuestion($room, $me);
            if (! $target) {
                return null;
            }
            $answersQuery->where('target_session_id', $target->session_id);
        }

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

        if ($room->mode === 'allvsbot') {
            return $source;
        }

        return RoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->where('session_id', '!=', $source->session_id)
            ->first();
    }

    private function advanceAllVsBotTurn(GameRoom $room, string $currentSessionId): void
    {
        $players = RoomPlayer::query()
            ->where('game_room_id', $room->id)
            ->orderBy('id')
            ->get();

        if ($players->count() < 2) {
            return;
        }

        $index = $players->search(fn (RoomPlayer $player) => $player->session_id === $currentSessionId);
        if ($index === false) {
            $room->turn_session_id = $players->first()->session_id;
            $room->save();
            return;
        }

        $nextIndex = ($index + 1) % $players->count();
        $room->turn_session_id = $players[$nextIndex]->session_id;
        $room->save();
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
