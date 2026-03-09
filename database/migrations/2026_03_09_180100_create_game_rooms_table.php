<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8)->unique();
            $table->enum('mode', ['online', 'vs']);
            $table->enum('difficulty', ['normal', 'easy', 'hard'])->default('normal');
            $table->enum('status', ['waiting', 'active', 'finished'])->default('waiting');
            $table->string('host_session_id', 64);
            $table->string('turn_session_id', 64)->nullable();
            $table->string('winner_session_id', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
