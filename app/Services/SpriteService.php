<?php

namespace App\Services;

class SpriteService
{
    public static function pokemonLocalPublicUrl(int $pokeapiId): string
    {
        return '/sprites/pokemon/'.$pokeapiId.'.png';
    }

    public static function pokemonLocalFilePath(int $pokeapiId): string
    {
        return public_path('sprites/pokemon/'.$pokeapiId.'.png');
    }

    public static function pokemonSpriteUrl(?int $pokeapiId, ?string $fallbackUrl = null): ?string
    {
        if ($pokeapiId && file_exists(self::pokemonLocalFilePath($pokeapiId))) {
            return self::pokemonLocalPublicUrl($pokeapiId);
        }

        return $fallbackUrl;
    }

    public static function itemLocalPublicUrl(string $slug): string
    {
        return '/sprites/items/'.$slug.'.png';
    }

    public static function itemLocalFilePath(string $slug): string
    {
        return public_path('sprites/items/'.$slug.'.png');
    }

    public static function itemRemoteUrl(string $slug): string
    {
        return 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/'.$slug.'.png';
    }

    public static function itemSpriteUrl(string $slug): string
    {
        if (file_exists(self::itemLocalFilePath($slug))) {
            return self::itemLocalPublicUrl($slug);
        }

        return self::itemRemoteUrl($slug);
    }
}
