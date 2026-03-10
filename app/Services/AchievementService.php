<?php

namespace App\Services;

use App\Models\PlayerAchievement;
use App\Models\PlayerProfile;
use App\Models\Pokemon;

class AchievementService
{
    /**
     * @return array<int, array<string, int|string>>
     */
    public static function definitions(): array
    {
        return [
            ['code' => 'play_1', 'title' => 'Primer Combate', 'description' => 'Juega 1 partida', 'metric' => 'games_played', 'target' => 1],
            ['code' => 'play_5', 'title' => 'Entrenador Constante', 'description' => 'Juega 5 partidas', 'metric' => 'games_played', 'target' => 5],
            ['code' => 'play_20', 'title' => 'Veterano del Coliseo', 'description' => 'Juega 20 partidas', 'metric' => 'games_played', 'target' => 20],
            ['code' => 'win_1', 'title' => 'Primera Victoria', 'description' => 'Gana 1 partida', 'metric' => 'wins', 'target' => 1],
            ['code' => 'win_5', 'title' => 'Racha Ganadora', 'description' => 'Gana 5 partidas', 'metric' => 'wins', 'target' => 5],
            ['code' => 'ask_25', 'title' => 'Pregunta Sin Miedo', 'description' => 'Haz 25 preguntas', 'metric' => 'questions_asked', 'target' => 25],
            ['code' => 'ask_100', 'title' => 'Maestro de Pistas', 'description' => 'Haz 100 preguntas', 'metric' => 'questions_asked', 'target' => 100],
            ['code' => 'answer_25', 'title' => 'Oráculo Pokémon', 'description' => 'Responde 25 preguntas', 'metric' => 'questions_answered', 'target' => 25],
            ['code' => 'guess_10', 'title' => 'Instinto de Campeón', 'description' => 'Haz 10 intentos de adivinanza', 'metric' => 'guesses_made', 'target' => 10],
            ['code' => 'correct_3', 'title' => 'Detective Pokémon', 'description' => 'Acierta 3 Pokémon', 'metric' => 'correct_guesses', 'target' => 3],
            ['code' => 'correct_10', 'title' => 'Profesor en Camino', 'description' => 'Acierta 10 Pokémon', 'metric' => 'correct_guesses', 'target' => 10],
            ['code' => 'level_10', 'title' => 'Subiendo de Rango', 'description' => 'Llega a nivel 10', 'metric' => 'level', 'target' => 10],
            ['code' => 'level_25', 'title' => 'Elite Trainer', 'description' => 'Llega a nivel 25', 'metric' => 'level', 'target' => 25],
        ];
    }

    public function syncUnlocks(PlayerProfile $profile): void
    {
        $existingCodes = PlayerAchievement::query()
            ->where('player_profile_id', $profile->id)
            ->pluck('code')
            ->all();

        foreach (self::definitions() as $definition) {
            if (in_array($definition['code'], $existingCodes, true)) {
                continue;
            }

            $metric = (string) $definition['metric'];
            $target = (int) $definition['target'];
            $current = (int) ($profile->{$metric} ?? 0);

            if ($current < $target) {
                continue;
            }

            PlayerAchievement::query()->create([
                'player_profile_id' => $profile->id,
                'code' => (string) $definition['code'],
                'title' => (string) $definition['title'],
                'description' => (string) $definition['description'],
                'reward_pokemon_id' => $this->randomRewardPokemonId(),
                'meta' => [
                    'metric' => $metric,
                    'target' => $target,
                ],
                'unlocked_at' => now(),
            ]);
        }

        $summary = $this->viewData($profile)['summary'];
        $currentTier = (string) ($summary['tier_code'] ?? 'bronze');
        $previousTier = (string) (($profile->meta['achievement_tier'] ?? null) ?: 'bronze');

        if ($this->tierRank($currentTier) > $this->tierRank($previousTier)) {
            app(GachaService::class)->grantTierUpReward($profile, $currentTier);
        }

        $meta = $profile->meta ?? [];
        $meta['achievement_tier'] = $currentTier;
        $profile->meta = $meta;
        $profile->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(PlayerProfile $profile): array
    {
        $achievements = PlayerAchievement::query()
            ->with('rewardPokemon:id,display_name,pokeapi_id,sprites,primary_type,secondary_type')
            ->where('player_profile_id', $profile->id)
            ->latest('unlocked_at')
            ->get();

        $definitions = collect(self::definitions())->map(function (array $definition) use ($profile, $achievements): array {
            $metric = (string) $definition['metric'];
            $target = (int) $definition['target'];
            $current = (int) ($profile->{$metric} ?? 0);
            $achievement = $achievements->firstWhere('code', $definition['code']);

            return [
                'code' => $definition['code'],
                'title' => $definition['title'],
                'description' => $definition['description'],
                'metric' => $metric,
                'target' => $target,
                'current' => $current,
                'progress_percent' => (int) max(0, min(100, floor(($current / max($target, 1)) * 100))),
                'is_unlocked' => (bool) $achievement,
                'unlocked_at' => $achievement?->unlocked_at?->toIso8601String(),
                'reward' => $achievement ? [
                    'pokemon_id' => $achievement->rewardPokemon?->id,
                    'pokeapi_id' => $achievement->rewardPokemon?->pokeapi_id,
                    'display_name' => $achievement->rewardPokemon?->display_name,
                    'sprite' => SpriteService::pokemonSpriteUrl(
                        $achievement->rewardPokemon?->pokeapi_id,
                        $achievement->rewardPokemon?->sprites['front_default']
                            ?? $achievement->rewardPokemon?->sprites['official_artwork']
                            ?? null,
                    ),
                    'primary_type' => $achievement->rewardPokemon?->primary_type,
                    'secondary_type' => $achievement->rewardPokemon?->secondary_type,
                ] : null,
            ];
        })->all();

        $unlocked = collect($definitions)->where('is_unlocked', true)->count();
        $completionPercent = (int) max(0, min(100, floor(($unlocked / max(count($definitions), 1)) * 100)));
        $tier = $this->tierFromCompletion($completionPercent);

        return [
            'summary' => [
                'unlocked' => $unlocked,
                'total' => count($definitions),
                'completion_percent' => $completionPercent,
                'tier_code' => $tier['code'],
                'tier_name' => $tier['name'],
                'tier_min_percent' => $tier['min_percent'],
            ],
            'items' => $definitions,
        ];
    }

    /**
     * @return array{code:string,name:string,min_percent:int}
     */
    public function tierFromCompletion(int $percent): array
    {
        return match (true) {
            $percent >= 100 => ['code' => 'champion', 'name' => 'Campeón', 'min_percent' => 100],
            $percent >= 80 => ['code' => 'master', 'name' => 'Maestro', 'min_percent' => 80],
            $percent >= 60 => ['code' => 'gold', 'name' => 'Oro', 'min_percent' => 60],
            $percent >= 35 => ['code' => 'silver', 'name' => 'Plata', 'min_percent' => 35],
            default => ['code' => 'bronze', 'name' => 'Bronce', 'min_percent' => 0],
        };
    }

    private function tierRank(string $code): int
    {
        return match ($code) {
            'bronze' => 1,
            'silver' => 2,
            'gold' => 3,
            'master' => 4,
            'champion' => 5,
            default => 0,
        };
    }

    private function randomRewardPokemonId(): ?int
    {
        $pokemon = Pokemon::query()->inRandomOrder()->first(['id']);
        return $pokemon?->id;
    }
}
