<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'asked_by_session_id',
        'target_session_id',
        'question_key',
        'question_text',
        'answer',
        'meta',
        'answered_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'answered_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }
}
