<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'mode',
        'difficulty',
        'visibility',
        'room_name',
        'language',
        'status',
        'host_session_id',
        'turn_session_id',
        'winner_session_id',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(RoomQuestion::class);
    }
}
