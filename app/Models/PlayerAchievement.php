<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'code',
        'title',
        'description',
        'reward_pokemon_id',
        'meta',
        'unlocked_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'unlocked_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_profile_id');
    }

    public function rewardPokemon(): BelongsTo
    {
        return $this->belongsTo(Pokemon::class, 'reward_pokemon_id');
    }
}
