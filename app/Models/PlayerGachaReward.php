<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerGachaReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'pokemon_id',
        'source',
        'level_reached',
        'tier_code',
        'rarity',
        'ball_type',
        'is_opened',
        'opened_at',
        'meta',
    ];

    protected $casts = [
        'is_opened' => 'boolean',
        'opened_at' => 'datetime',
        'meta' => 'array',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_profile_id');
    }

    public function pokemon(): BelongsTo
    {
        return $this->belongsTo(Pokemon::class, 'pokemon_id');
    }
}
