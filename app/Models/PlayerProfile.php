<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'nickname',
        'experience_tier',
        'xp',
        'level',
        'games_played',
        'wins',
        'questions_asked',
        'questions_answered',
        'guesses_made',
        'correct_guesses',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function achievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class);
    }

    public function gachaRewards(): HasMany
    {
        return $this->hasMany(PlayerGachaReward::class);
    }
}
