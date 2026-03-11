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
        'timer_enabled',
        'timer_seconds',
        'timer_p1_remaining',
        'timer_p2_remaining',
        'timer_last_tick',
        'timer_proposed_by',
        'surrendered_by',
    ];

    protected $casts = [
        'timer_enabled' => 'boolean',
        'timer_last_tick' => 'datetime',
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
