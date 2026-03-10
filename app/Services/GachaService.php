<?php

namespace App\Services;

use App\Models\PlayerGachaReward;
use App\Models\PlayerProfile;
use App\Models\Pokemon;

class GachaService
{
    /**
     * @return array<string, string>
     */
    public static function ballCatalog(): array
    {
        return [
            'poke-ball' => SpriteService::itemSpriteUrl('poke-ball'),
            'great-ball' => SpriteService::itemSpriteUrl('great-ball'),
            'ultra-ball' => SpriteService::itemSpriteUrl('ultra-ball'),
            'cherish-ball' => SpriteService::itemSpriteUrl('cherish-ball'),
            'master-ball' => SpriteService::itemSpriteUrl('master-ball'),
        ];
    }

    public function grantLevelUpRewards(PlayerProfile $profile, int $fromLevel, int $toLevel): void
    {
        if ($toLevel <= $fromLevel) {
            return;
        }

        for ($level = $fromLevel + 1; $level <= $toLevel; $level++) {
            $rarity = $this->rollRarityForLevel($level);
            $pokemon = $this->pickPokemonForRarity($rarity);

            if (! $pokemon) {
                continue;
            }

            PlayerGachaReward::query()->create([
                'player_profile_id' => $profile->id,
                'pokemon_id' => $pokemon->id,
                'source' => 'level_up',
                'level_reached' => $level,
                'rarity' => $rarity,
                'ball_type' => $this->ballForRarity($rarity),
                'meta' => ['kind' => 'level_reward'],
            ]);
        }
    }

    public function grantTierUpReward(PlayerProfile $profile, string $tierCode): void
    {
        $rarity = random_int(1, 100) <= 65 ? 'mythic' : 'legendary';
        $pokemon = $this->pickPokemonForRarity($rarity);

        if (! $pokemon) {
            return;
        }

        PlayerGachaReward::query()->create([
            'player_profile_id' => $profile->id,
            'pokemon_id' => $pokemon->id,
            'source' => 'tier_up',
            'tier_code' => $tierCode,
            'rarity' => $rarity,
            'ball_type' => $this->ballForRarity($rarity),
            'meta' => ['kind' => 'tier_reward'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function queueView(PlayerProfile $profile): array
    {
        $pending = PlayerGachaReward::query()
            ->with('pokemon:id,display_name,pokeapi_id,sprites,primary_type,secondary_type')
            ->where('player_profile_id', $profile->id)
            ->where('is_opened', false)
            ->oldest('id')
            ->get();

        $openedRecent = PlayerGachaReward::query()
            ->with('pokemon:id,display_name,pokeapi_id,sprites,primary_type,secondary_type')
            ->where('player_profile_id', $profile->id)
            ->where('is_opened', true)
            ->latest('opened_at')
            ->limit(16)
            ->get();

        return [
            'pending_count' => $pending->count(),
            'pending_preview' => $pending->take(5)->map(fn (PlayerGachaReward $reward): array => $this->rewardPayload($reward))->all(),
            'opened_recent' => $openedRecent->map(fn (PlayerGachaReward $reward): array => $this->rewardPayload($reward))->all(),
            'ball_catalog' => self::ballCatalog(),
        ];
    }

    public function openNext(PlayerProfile $profile): ?PlayerGachaReward
    {
        $reward = PlayerGachaReward::query()
            ->with('pokemon:id,display_name,pokeapi_id,sprites,primary_type,secondary_type')
            ->where('player_profile_id', $profile->id)
            ->where('is_opened', false)
            ->oldest('id')
            ->first();

        if (! $reward) {
            return null;
        }

        $reward->is_opened = true;
        $reward->opened_at = now();
        $reward->save();

        return $reward->fresh(['pokemon:id,display_name,pokeapi_id,sprites,primary_type,secondary_type']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rewardPayload(PlayerGachaReward $reward): array
    {
        return [
            'id' => $reward->id,
            'source' => $reward->source,
            'level_reached' => $reward->level_reached,
            'tier_code' => $reward->tier_code,
            'rarity' => $reward->rarity,
            'ball_type' => $reward->ball_type,
            'ball_sprite' => self::ballCatalog()[$reward->ball_type] ?? self::ballCatalog()['poke-ball'],
            'is_opened' => $reward->is_opened,
            'opened_at' => $reward->opened_at?->toIso8601String(),
            'pokemon' => [
                'id' => $reward->pokemon?->id,
                'pokeapi_id' => $reward->pokemon?->pokeapi_id,
                'display_name' => $reward->pokemon?->display_name,
                'sprite' => SpriteService::pokemonSpriteUrl(
                    $reward->pokemon?->pokeapi_id,
                    $reward->pokemon?->sprites['front_default']
                        ?? $reward->pokemon?->sprites['official_artwork']
                        ?? null,
                ),
                'primary_type' => $reward->pokemon?->primary_type,
                'secondary_type' => $reward->pokemon?->secondary_type,
            ],
        ];
    }

    private function rollRarityForLevel(int $level): string
    {
        $levelBonus = min(18, max(0, $level - 1));
        $roll = random_int(1, 10000);

        $legendaryThreshold = 10 + (int) floor($levelBonus * 0.7);
        $mythicThreshold = $legendaryThreshold + 25 + $levelBonus;
        $ultraThreshold = $mythicThreshold + 220 + ($levelBonus * 5);
        $specialThreshold = $ultraThreshold + 900 + ($levelBonus * 12);
        $rareThreshold = $specialThreshold + 2300 + ($levelBonus * 15);

        if ($roll <= $legendaryThreshold) {
            return 'legendary';
        }

        if ($roll <= $mythicThreshold) {
            return 'mythic';
        }

        if ($roll <= $ultraThreshold) {
            return 'ultra';
        }

        if ($roll <= $specialThreshold) {
            return 'special';
        }

        if ($roll <= $rareThreshold) {
            return 'rare';
        }

        return 'normal';
    }

    private function ballForRarity(string $rarity): string
    {
        return match ($rarity) {
            'legendary' => 'master-ball',
            'mythic' => 'cherish-ball',
            'ultra' => 'ultra-ball',
            'special' => 'great-ball',
            'rare' => 'great-ball',
            default => 'poke-ball',
        };
    }

    private function pickPokemonForRarity(string $rarity): ?Pokemon
    {
        $all = Pokemon::query()->get(['id', 'slug', 'is_legendary', 'is_mythical', 'base_experience', 'stats']);

        $pool = $all->filter(function (Pokemon $pokemon) use ($rarity): bool {
            $total = array_sum((array) ($pokemon->stats ?? []));

            return match ($rarity) {
                'legendary' => (bool) $pokemon->is_legendary,
                'mythic' => (bool) $pokemon->is_mythical,
                'ultra' => ! $pokemon->is_legendary && ! $pokemon->is_mythical && ($total >= 560 || (int) $pokemon->base_experience >= 240),
                'special' => ! $pokemon->is_legendary && ! $pokemon->is_mythical && $total >= 500,
                'rare' => ! $pokemon->is_legendary && ! $pokemon->is_mythical && ($total >= 430 || (int) $pokemon->base_experience >= 120 || $pokemon->slug === 'pikachu'),
                default => ! $pokemon->is_legendary && ! $pokemon->is_mythical,
            };
        })->values();

        if ($pool->isEmpty()) {
            return Pokemon::query()->inRandomOrder()->first();
        }

        $picked = $pool->random();
        return Pokemon::query()->find($picked->id);
    }
}
