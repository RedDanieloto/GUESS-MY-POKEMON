<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
