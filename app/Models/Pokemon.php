<?php

namespace App\Models;

use App\Services\SpriteService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    use HasFactory;

    protected $table = 'pokemons';

    protected $fillable = [
        'pokeapi_id',
        'slug',
        'display_name',
        'generation',
        'primary_type',
        'secondary_type',
        'is_legendary',
        'is_mythical',
        'is_baby',
        'height_dm',
        'weight_hg',
        'base_experience',
        'abilities',
        'stats',
        'sprites',
    ];

    protected $casts = [
        'abilities' => 'array',
        'stats' => 'array',
        'sprites' => 'array',
        'is_legendary' => 'boolean',
        'is_mythical' => 'boolean',
        'is_baby' => 'boolean',
    ];

    protected $appends = [
        'sprite',
    ];

    public function getSpriteAttribute(): ?string
    {
        return SpriteService::pokemonSpriteUrl(
            $this->pokeapi_id,
            $this->sprites['front_default'] ?? $this->sprites['official_artwork'] ?? null,
        );
    }
}
