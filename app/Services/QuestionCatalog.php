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
