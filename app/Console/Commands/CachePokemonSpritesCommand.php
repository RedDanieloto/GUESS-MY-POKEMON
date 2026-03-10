<?php

namespace App\Console\Commands;

use App\Models\Pokemon;
use App\Services\SpriteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CachePokemonSpritesCommand extends Command
{
    protected $signature = 'pokemon:cache-sprites {--limit=0} {--force}';

    protected $description = 'Descarga sprites locales de Pokemon e items (gacha) a public/sprites';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $pokemonDir = public_path('sprites/pokemon');
        $itemsDir = public_path('sprites/items');
        File::ensureDirectoryExists($pokemonDir);
        File::ensureDirectoryExists($itemsDir);

        $query = Pokemon::query()->orderBy('pokeapi_id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $pokemons = $query->get(['pokeapi_id', 'sprites']);

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        $this->info('Cacheando sprites de Pokemon...');

        foreach ($pokemons as $pokemon) {
            $path = SpriteService::pokemonLocalFilePath((int) $pokemon->pokeapi_id);
            if (! $force && file_exists($path)) {
                $skipped++;
                continue;
            }

            $fallback = $pokemon->sprites['front_default']
                ?? $pokemon->sprites['official_artwork']
                ?? null;

            if (! $fallback) {
                $failed++;
                continue;
            }

            try {
                $response = Http::retry(3, 500)->timeout(30)->get($fallback);
                if (! $response->ok()) {
                    $failed++;
                    continue;
                }

                file_put_contents($path, $response->body());
                $downloaded++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $itemSlugs = ['poke-ball', 'great-ball', 'ultra-ball', 'cherish-ball', 'master-ball'];
        $itemDownloaded = 0;
        $itemSkipped = 0;
        $itemFailed = 0;

        $this->info('Cacheando sprites de balls...');

        foreach ($itemSlugs as $slug) {
            $path = SpriteService::itemLocalFilePath($slug);
            if (! $force && file_exists($path)) {
                $itemSkipped++;
                continue;
            }

            $url = SpriteService::itemRemoteUrl($slug);
            try {
                $response = Http::retry(3, 500)->timeout(20)->get($url);
                if (! $response->ok()) {
                    $itemFailed++;
                    continue;
                }

                file_put_contents($path, $response->body());
                $itemDownloaded++;
            } catch (\Throwable) {
                $itemFailed++;
            }
        }

        $this->newLine();
        $this->table(['tipo', 'downloaded', 'skipped', 'failed'], [
            ['pokemon', $downloaded, $skipped, $failed],
            ['items', $itemDownloaded, $itemSkipped, $itemFailed],
        ]);

        return self::SUCCESS;
    }
}
