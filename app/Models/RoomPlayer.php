<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'session_id',
        'nickname',
        'role',
        'hidden_pokemon_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function hiddenPokemon(): BelongsTo
    {
        return $this->belongsTo(Pokemon::class, 'hidden_pokemon_id');
    }
}
