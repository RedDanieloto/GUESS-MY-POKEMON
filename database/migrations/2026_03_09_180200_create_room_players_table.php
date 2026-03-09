<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 64);
            $table->string('nickname', 40);
            $table->enum('role', ['host', 'guesser', 'player1', 'player2']);
            $table->foreignId('hidden_pokemon_id')->nullable()->constrained('pokemons')->nullOnDelete();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['game_room_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_players');
    }
};
