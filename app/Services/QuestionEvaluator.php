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

        return match ($questionKey) {
            'has_secondary_type' => $pokemon->secondary_type ? 'yes' : 'no',
            'is_legendary' => $pokemon->is_legendary ? 'yes' : 'no',
            'is_mythical' => $pokemon->is_mythical ? 'yes' : 'no',
            'is_baby' => $pokemon->is_baby ? 'yes' : 'no',
            'height_over_10' => $pokemon->height_dm > 10 ? 'yes' : 'no',
            'weight_over_500' => $pokemon->weight_hg > 500 ? 'yes' : 'no',
            default => null,
        };
    }
}
