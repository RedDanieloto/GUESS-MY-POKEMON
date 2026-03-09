<?php

namespace App\Console\Commands;

use App\Services\PokeApiService;
use Illuminate\Console\Command;

class SyncPokemonCommand extends Command
{
    protected $signature = 'pokemon:sync {--limit=151} {--offset=0}';

    protected $description = 'Sincroniza Pokémon desde PokeAPI a la base local';

    public function handle(PokeApiService $pokeApiService): int
    {
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');

        $this->info("Sincronizando {$limit} Pokémon desde offset {$offset}...");
        $summary = $pokeApiService->sync($limit, $offset);

        $this->table(['created', 'updated', 'skipped'], [[$summary['created'], $summary['updated'], $summary['skipped']]]);

        return self::SUCCESS;
    }
}
