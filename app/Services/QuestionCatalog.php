<?php

namespace App\Services;

class QuestionCatalog
{
    /**
     * @return array<string, array{label:string, label_es:string, label_en:string, category:string}>
     */
    public static function all(): array
    {
        $questions = [
            'has_secondary_type' => [
                'label' => 'Tiene doble tipo?',
                'label_es' => 'Tiene doble tipo?',
                'label_en' => 'Does it have two types?',
                'category' => 'type',
            ],
            'is_legendary' => [
                'label' => 'Es legendario?',
                'label_es' => 'Es legendario?',
                'label_en' => 'Is it legendary?',
                'category' => 'rarity',
            ],
            'is_mythical' => [
                'label' => 'Es mítico?',
                'label_es' => 'Es mítico?',
                'label_en' => 'Is it mythical?',
                'category' => 'rarity',
            ],
            'is_baby' => [
                'label' => 'Es forma bebé?',
                'label_es' => 'Es forma bebé?',
                'label_en' => 'Is it a baby form?',
                'category' => 'rarity',
            ],
            'height_over_10' => [
                'label' => 'Mide más de 1.0 m?',
                'label_es' => 'Mide más de 1.0 m?',
                'label_en' => 'Is it taller than 1.0 m?',
                'category' => 'stats',
            ],
            'weight_over_500' => [
                'label' => 'Pesa más de 50 kg?',
                'label_es' => 'Pesa más de 50 kg?',
                'label_en' => 'Is it heavier than 50 kg?',
                'category' => 'stats',
            ],
            'is_single_type' => [
                'label' => 'Es de un solo tipo?',
                'label_es' => 'Es de un solo tipo?',
                'label_en' => 'Is it a single-type Pokemon?',
                'category' => 'type',
            ],
            'generation_1_3' => [
                'label' => 'Es de generaciones 1 a 3?',
                'label_es' => 'Es de generaciones 1 a 3?',
                'label_en' => 'Is it from generations 1 to 3?',
                'category' => 'generation',
            ],
            'generation_4_6' => [
                'label' => 'Es de generaciones 4 a 6?',
                'label_es' => 'Es de generaciones 4 a 6?',
                'label_en' => 'Is it from generations 4 to 6?',
                'category' => 'generation',
            ],
            'generation_7_9' => [
                'label' => 'Es de generaciones 7 a 9?',
                'label_es' => 'Es de generaciones 7 a 9?',
                'label_en' => 'Is it from generations 7 to 9?',
                'category' => 'generation',
            ],
            'generation_lte_5' => [
                'label' => 'Es de quinta generación o menor?',
                'label_es' => 'Es de quinta generación o menor?',
                'label_en' => 'Is it 5th generation or earlier?',
                'category' => 'generation',
            ],
            'generation_gte_5' => [
                'label' => 'Es de quinta generación o mayor?',
                'label_es' => 'Es de quinta generación o mayor?',
                'label_en' => 'Is it 5th generation or later?',
                'category' => 'generation',
            ],
            'generation_lte_3' => [
                'label' => 'Es de tercera generación o menor?',
                'label_es' => 'Es de tercera generación o menor?',
                'label_en' => 'Is it 3rd generation or earlier?',
                'category' => 'generation',
            ],
            'generation_gte_7' => [
                'label' => 'Es de séptima generación o mayor?',
                'label_es' => 'Es de séptima generación o mayor?',
                'label_en' => 'Is it 7th generation or later?',
                'category' => 'generation',
            ],
            'is_non_legendary' => [
                'label' => 'No es legendario?',
                'label_es' => 'No es legendario?',
                'label_en' => 'Is it not legendary?',
                'category' => 'rarity',
            ],
            'is_legendary_or_mythical' => [
                'label' => 'Es legendario o mítico?',
                'label_es' => 'Es legendario o mítico?',
                'label_en' => 'Is it legendary or mythical?',
                'category' => 'rarity',
            ],
            'height_under_5' => [
                'label' => 'Mide menos de 0.5 m?',
                'label_es' => 'Mide menos de 0.5 m?',
                'label_en' => 'Is it shorter than 0.5 m?',
                'category' => 'stats',
            ],
            'height_over_15' => [
                'label' => 'Mide más de 1.5 m?',
                'label_es' => 'Mide más de 1.5 m?',
                'label_en' => 'Is it taller than 1.5 m?',
                'category' => 'stats',
            ],
            'height_over_20' => [
                'label' => 'Mide más de 2.0 m?',
                'label_es' => 'Mide más de 2.0 m?',
                'label_en' => 'Is it taller than 2.0 m?',
                'category' => 'stats',
            ],
            'weight_under_100' => [
                'label' => 'Pesa menos de 10 kg?',
                'label_es' => 'Pesa menos de 10 kg?',
                'label_en' => 'Does it weigh less than 10 kg?',
                'category' => 'stats',
            ],
            'weight_over_300' => [
                'label' => 'Pesa más de 30 kg?',
                'label_es' => 'Pesa más de 30 kg?',
                'label_en' => 'Does it weigh more than 30 kg?',
                'category' => 'stats',
            ],
            'weight_over_600' => [
                'label' => 'Pesa más de 60 kg?',
                'label_es' => 'Pesa más de 60 kg?',
                'label_en' => 'Does it weigh more than 60 kg?',
                'category' => 'stats',
            ],
            'weight_over_1000' => [
                'label' => 'Pesa más de 100 kg?',
                'label_es' => 'Pesa más de 100 kg?',
                'label_en' => 'Does it weigh more than 100 kg?',
                'category' => 'stats',
            ],
            'base_experience_over_150' => [
                'label' => 'Tiene experiencia base mayor a 150?',
                'label_es' => 'Tiene experiencia base mayor a 150?',
                'label_en' => 'Does it have base experience above 150?',
                'category' => 'stats',
            ],
            'base_experience_over_220' => [
                'label' => 'Tiene experiencia base mayor a 220?',
                'label_es' => 'Tiene experiencia base mayor a 220?',
                'label_en' => 'Does it have base experience above 220?',
                'category' => 'stats',
            ],
            'base_experience_under_80' => [
                'label' => 'Tiene experiencia base menor a 80?',
                'label_es' => 'Tiene experiencia base menor a 80?',
                'label_en' => 'Does it have base experience below 80?',
                'category' => 'stats',
            ],
            'ability_count_over_1' => [
                'label' => 'Tiene más de 1 habilidad?',
                'label_es' => 'Tiene más de 1 habilidad?',
                'label_en' => 'Does it have more than 1 ability?',
                'category' => 'stats',
            ],
            'ability_count_over_2' => [
                'label' => 'Tiene más de 2 habilidades?',
                'label_es' => 'Tiene más de 2 habilidades?',
                'label_en' => 'Does it have more than 2 abilities?',
                'category' => 'stats',
            ],
            'total_stats_over_420' => [
                'label' => 'La suma de stats base es mayor a 420?',
                'label_es' => 'La suma de stats base es mayor a 420?',
                'label_en' => 'Is its total base stats above 420?',
                'category' => 'stats',
            ],
            'total_stats_over_500' => [
                'label' => 'La suma de stats base es mayor a 500?',
                'label_es' => 'La suma de stats base es mayor a 500?',
                'label_en' => 'Is its total base stats above 500?',
                'category' => 'stats',
            ],
            'total_stats_over_580' => [
                'label' => 'La suma de stats base es mayor a 580?',
                'label_es' => 'La suma de stats base es mayor a 580?',
                'label_en' => 'Is its total base stats above 580?',
                'category' => 'stats',
            ],
            'total_stats_under_350' => [
                'label' => 'La suma de stats base es menor a 350?',
                'label_es' => 'La suma de stats base es menor a 350?',
                'label_en' => 'Is its total base stats below 350?',
                'category' => 'stats',
            ],
            'stat_hp_over_70' => [
                'label' => 'Tiene PS base mayor a 70?',
                'label_es' => 'Tiene PS base mayor a 70?',
                'label_en' => 'Is base HP above 70?',
                'category' => 'stats',
            ],
            'stat_hp_over_100' => [
                'label' => 'Tiene PS base mayor a 100?',
                'label_es' => 'Tiene PS base mayor a 100?',
                'label_en' => 'Is base HP above 100?',
                'category' => 'stats',
            ],
            'stat_attack_over_90' => [
                'label' => 'Tiene Ataque base mayor a 90?',
                'label_es' => 'Tiene Ataque base mayor a 90?',
                'label_en' => 'Is base Attack above 90?',
                'category' => 'stats',
            ],
            'stat_defense_over_90' => [
                'label' => 'Tiene Defensa base mayor a 90?',
                'label_es' => 'Tiene Defensa base mayor a 90?',
                'label_en' => 'Is base Defense above 90?',
                'category' => 'stats',
            ],
            'stat_special_attack_over_100' => [
                'label' => 'Tiene Ataque Esp. base mayor a 100?',
                'label_es' => 'Tiene Ataque Esp. base mayor a 100?',
                'label_en' => 'Is base Sp. Attack above 100?',
                'category' => 'stats',
            ],
            'stat_special_defense_over_90' => [
                'label' => 'Tiene Defensa Esp. base mayor a 90?',
                'label_es' => 'Tiene Defensa Esp. base mayor a 90?',
                'label_en' => 'Is base Sp. Defense above 90?',
                'category' => 'stats',
            ],
            'stat_speed_over_90' => [
                'label' => 'Tiene Velocidad base mayor a 90?',
                'label_es' => 'Tiene Velocidad base mayor a 90?',
                'label_en' => 'Is base Speed above 90?',
                'category' => 'stats',
            ],
            'stat_speed_over_110' => [
                'label' => 'Tiene Velocidad base mayor a 110?',
                'label_es' => 'Tiene Velocidad base mayor a 110?',
                'label_en' => 'Is base Speed above 110?',
                'category' => 'stats',
            ],
            'dex_under_151' => [
                'label' => 'Su número de Pokédex nacional es menor o igual a 151?',
                'label_es' => 'Su número de Pokédex nacional es menor o igual a 151?',
                'label_en' => 'Is its National Dex number 151 or below?',
                'category' => 'generation',
            ],
            'dex_under_386' => [
                'label' => 'Su número de Pokédex nacional es menor o igual a 386?',
                'label_es' => 'Su número de Pokédex nacional es menor o igual a 386?',
                'label_en' => 'Is its National Dex number 386 or below?',
                'category' => 'generation',
            ],
            'dex_under_649' => [
                'label' => 'Su número de Pokédex nacional es menor o igual a 649?',
                'label_es' => 'Su número de Pokédex nacional es menor o igual a 649?',
                'label_en' => 'Is its National Dex number 649 or below?',
                'category' => 'generation',
            ],
        ];

        for ($i = 1; $i <= 9; $i++) {
            $questions['generation_'.$i] = [
                'label' => "Es de generación {$i}?",
                'label_es' => "Es de generación {$i}?",
                'label_en' => "Is it generation {$i}?",
                'category' => 'generation',
            ];
        }

        foreach (self::types() as $type) {
            $questions['type_'.$type] = [
                'label' => 'Es de tipo '.self::typeLabel($type, 'es').'?',
                'label_es' => 'Es de tipo '.self::typeLabel($type, 'es').'?',
                'label_en' => 'Is it type '.self::typeLabel($type, 'en').'?',
                'category' => 'type',
            ];
        }

        return $questions;
    }

    /**
     * @return string[]
     */
    public static function types(): array
    {
        return [
            'normal', 'fire', 'water', 'grass', 'electric', 'ice', 'fighting',
            'poison', 'ground', 'flying', 'psychic', 'bug', 'rock', 'ghost',
            'dragon', 'dark', 'steel', 'fairy',
        ];
    }

    public static function labelFor(string $questionKey, string $language = 'es'): ?string
    {
        $language = $language === 'en' ? 'en' : 'es';
        $catalog = self::all();

        if (! isset($catalog[$questionKey])) {
            return null;
        }

        return $catalog[$questionKey]['label_'.$language] ?? $catalog[$questionKey]['label'] ?? null;
    }

    public static function typeLabel(string $type, string $language = 'es'): string
    {
        $map = [
            'normal' => ['es' => 'Normal', 'en' => 'Normal'],
            'fire' => ['es' => 'Fuego', 'en' => 'Fire'],
            'water' => ['es' => 'Agua', 'en' => 'Water'],
            'grass' => ['es' => 'Planta', 'en' => 'Grass'],
            'electric' => ['es' => 'Eléctrico', 'en' => 'Electric'],
            'ice' => ['es' => 'Hielo', 'en' => 'Ice'],
            'fighting' => ['es' => 'Lucha', 'en' => 'Fighting'],
            'poison' => ['es' => 'Veneno', 'en' => 'Poison'],
            'ground' => ['es' => 'Tierra', 'en' => 'Ground'],
            'flying' => ['es' => 'Volador', 'en' => 'Flying'],
            'psychic' => ['es' => 'Psíquico', 'en' => 'Psychic'],
            'bug' => ['es' => 'Bicho', 'en' => 'Bug'],
            'rock' => ['es' => 'Roca', 'en' => 'Rock'],
            'ghost' => ['es' => 'Fantasma', 'en' => 'Ghost'],
            'dragon' => ['es' => 'Dragón', 'en' => 'Dragon'],
            'dark' => ['es' => 'Siniestro', 'en' => 'Dark'],
            'steel' => ['es' => 'Acero', 'en' => 'Steel'],
            'fairy' => ['es' => 'Hada', 'en' => 'Fairy'],
        ];

        $language = $language === 'en' ? 'en' : 'es';
        return $map[$type][$language] ?? ucfirst($type);
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(string $language = 'es'): array
    {
        $labels = [];
        foreach (self::types() as $type) {
            $labels[$type] = self::typeLabel($type, $language);
        }

        return $labels;
    }
}
