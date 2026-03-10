<?php

namespace App\Services;

use App\Models\Pokemon;

class QuestionEvaluator
{
    public static function evaluate(string $questionKey, Pokemon $pokemon): ?string
    {
        if (str_starts_with($questionKey, 'generation_')) {
            $generation = (int) str_replace('generation_', '', $questionKey);
            return $pokemon->generation === $generation ? 'yes' : 'no';
        }

        if (str_starts_with($questionKey, 'type_')) {
            $type = str_replace('type_', '', $questionKey);
            return ($pokemon->primary_type === $type || $pokemon->secondary_type === $type) ? 'yes' : 'no';
        }

        $totalStats = self::totalStats($pokemon);
        $abilitiesCount = count($pokemon->abilities ?? []);

        return match ($questionKey) {
            'has_secondary_type' => $pokemon->secondary_type ? 'yes' : 'no',
            'is_single_type' => $pokemon->secondary_type ? 'no' : 'yes',
            'generation_1_3' => in_array($pokemon->generation, [1, 2, 3], true) ? 'yes' : 'no',
            'generation_4_6' => in_array($pokemon->generation, [4, 5, 6], true) ? 'yes' : 'no',
            'generation_7_9' => in_array($pokemon->generation, [7, 8, 9], true) ? 'yes' : 'no',
            'generation_lte_5' => ((int) $pokemon->generation) <= 5 ? 'yes' : 'no',
            'generation_gte_5' => ((int) $pokemon->generation) >= 5 ? 'yes' : 'no',
            'generation_lte_3' => ((int) $pokemon->generation) <= 3 ? 'yes' : 'no',
            'generation_gte_7' => ((int) $pokemon->generation) >= 7 ? 'yes' : 'no',
            'is_legendary' => $pokemon->is_legendary ? 'yes' : 'no',
            'is_non_legendary' => $pokemon->is_legendary ? 'no' : 'yes',
            'is_mythical' => $pokemon->is_mythical ? 'yes' : 'no',
            'is_legendary_or_mythical' => ($pokemon->is_legendary || $pokemon->is_mythical) ? 'yes' : 'no',
            'is_baby' => $pokemon->is_baby ? 'yes' : 'no',
            'height_under_5' => $pokemon->height_dm < 5 ? 'yes' : 'no',
            'height_over_10' => $pokemon->height_dm > 10 ? 'yes' : 'no',
            'height_over_15' => $pokemon->height_dm > 15 ? 'yes' : 'no',
            'height_over_20' => $pokemon->height_dm > 20 ? 'yes' : 'no',
            'weight_under_100' => $pokemon->weight_hg < 100 ? 'yes' : 'no',
            'weight_over_300' => $pokemon->weight_hg > 300 ? 'yes' : 'no',
            'weight_over_500' => $pokemon->weight_hg > 500 ? 'yes' : 'no',
            'weight_over_600' => $pokemon->weight_hg > 600 ? 'yes' : 'no',
            'weight_over_1000' => $pokemon->weight_hg > 1000 ? 'yes' : 'no',
            'base_experience_over_150' => ((int) ($pokemon->base_experience ?? 0)) > 150 ? 'yes' : 'no',
            'base_experience_over_220' => ((int) ($pokemon->base_experience ?? 0)) > 220 ? 'yes' : 'no',
            'base_experience_under_80' => ((int) ($pokemon->base_experience ?? 0)) < 80 ? 'yes' : 'no',
            'ability_count_over_1' => $abilitiesCount > 1 ? 'yes' : 'no',
            'ability_count_over_2' => $abilitiesCount > 2 ? 'yes' : 'no',
            'total_stats_over_420' => $totalStats > 420 ? 'yes' : 'no',
            'total_stats_over_500' => $totalStats > 500 ? 'yes' : 'no',
            'total_stats_over_580' => $totalStats > 580 ? 'yes' : 'no',
            'total_stats_under_350' => $totalStats < 350 ? 'yes' : 'no',
            'stat_hp_over_70' => self::stat($pokemon, 'hp') > 70 ? 'yes' : 'no',
            'stat_hp_over_100' => self::stat($pokemon, 'hp') > 100 ? 'yes' : 'no',
            'stat_attack_over_90' => self::stat($pokemon, 'attack') > 90 ? 'yes' : 'no',
            'stat_defense_over_90' => self::stat($pokemon, 'defense') > 90 ? 'yes' : 'no',
            'stat_special_attack_over_100' => self::stat($pokemon, 'special-attack') > 100 ? 'yes' : 'no',
            'stat_special_defense_over_90' => self::stat($pokemon, 'special-defense') > 90 ? 'yes' : 'no',
            'stat_speed_over_90' => self::stat($pokemon, 'speed') > 90 ? 'yes' : 'no',
            'stat_speed_over_110' => self::stat($pokemon, 'speed') > 110 ? 'yes' : 'no',
            'dex_under_151' => $pokemon->pokeapi_id <= 151 ? 'yes' : 'no',
            'dex_under_386' => $pokemon->pokeapi_id <= 386 ? 'yes' : 'no',
            'dex_under_649' => $pokemon->pokeapi_id <= 649 ? 'yes' : 'no',
            default => null,
        };
    }

    private static function stat(Pokemon $pokemon, string $key): int
    {
        return (int) (($pokemon->stats ?? [])[$key] ?? 0);
    }

    private static function totalStats(Pokemon $pokemon): int
    {
        $stats = $pokemon->stats ?? [];
        if (! is_array($stats)) {
            return 0;
        }

        return (int) array_sum($stats);
    }
}
