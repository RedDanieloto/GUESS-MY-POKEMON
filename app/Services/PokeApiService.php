<?php

namespace App\Services;

use App\Models\Pokemon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class PokeApiService
{
    private const BASE_URL = 'https://pokeapi.co/api/v2';

    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    public function sync(int $limit = 151, int $offset = 0): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $listResponse = $this->safeGet(self::BASE_URL.'/pokemon', [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if (! $listResponse || ! $listResponse->ok()) {
            return compact('created', 'updated', 'skipped');
        }

        foreach ($listResponse->json('results', []) as $result) {
            $pokemonResponse = $this->safeGet($result['url']);
            if (! $pokemonResponse || ! $pokemonResponse->ok()) {
                $skipped++;
                continue;
            }

            $pokemonData = $pokemonResponse->json();
            $speciesResponse = $this->safeGet($pokemonData['species']['url'] ?? '');
            if (! $speciesResponse || ! $speciesResponse->ok()) {
                $skipped++;
                continue;
            }

            $species = $speciesResponse->json();
            $generation = (int) Str::of($species['generation']['name'] ?? 'generation-0')->after('generation-')->value();

            $types = Arr::pluck($pokemonData['types'] ?? [], 'type.name');
            $abilities = Arr::pluck($pokemonData['abilities'] ?? [], 'ability.name');

            $stats = [];
            foreach ($pokemonData['stats'] ?? [] as $entry) {
                $stats[$entry['stat']['name']] = $entry['base_stat'];
            }

            $payload = [
                'slug' => $pokemonData['name'],
                'display_name' => Str::headline(str_replace('-', ' ', $pokemonData['name'])),
                'generation' => $generation ?: null,
                'primary_type' => $types[0] ?? 'unknown',
                'secondary_type' => $types[1] ?? null,
                'is_legendary' => (bool) ($species['is_legendary'] ?? false),
                'is_mythical' => (bool) ($species['is_mythical'] ?? false),
                'is_baby' => (bool) ($species['is_baby'] ?? false),
                'height_dm' => (int) ($pokemonData['height'] ?? 0),
                'weight_hg' => (int) ($pokemonData['weight'] ?? 0),
                'base_experience' => $pokemonData['base_experience'] ?? null,
                'abilities' => array_values($abilities),
                'stats' => $stats,
                'sprites' => [
                    'front_default' => $pokemonData['sprites']['front_default'] ?? null,
                    'official_artwork' => $pokemonData['sprites']['other']['official-artwork']['front_default'] ?? null,
                ],
            ];

            $existing = Pokemon::query()->where('pokeapi_id', $pokemonData['id'])->first();

            if ($existing) {
                $existing->fill($payload)->save();
                $updated++;
            } else {
                Pokemon::query()->create([
                    'pokeapi_id' => (int) $pokemonData['id'],
                    ...$payload,
                ]);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    private function safeGet(string $url, array $query = []): ?Response
    {
        if ($url === '') {
            return null;
        }

        try {
            return Http::retry(3, 700)->timeout(45)->get($url, $query);
        } catch (Throwable) {
            return null;
        }
    }
}
