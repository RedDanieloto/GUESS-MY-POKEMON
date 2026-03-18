<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table): void {
            $table->foreignId('bot_pokemon_id')->nullable()->after('winner_session_id')->constrained('pokemons')->nullOnDelete();
            $table->unsignedInteger('question_limit_per_player')->nullable()->after('bot_pokemon_id');
        });

        // Expand enums to support the all-vs-bot game mode and extra player roles.
        DB::statement("ALTER TABLE game_rooms MODIFY mode ENUM('online','vs','allvsbot') NOT NULL");
        DB::statement("ALTER TABLE room_players MODIFY role ENUM('host','guesser','player1','player2','player3','player4') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE room_players MODIFY role ENUM('host','guesser','player1','player2') NOT NULL");
        DB::statement("ALTER TABLE game_rooms MODIFY mode ENUM('online','vs') NOT NULL");

        Schema::table('game_rooms', function (Blueprint $table): void {
            $table->dropForeign(['bot_pokemon_id']);
            $table->dropColumn(['bot_pokemon_id', 'question_limit_per_player']);
        });
    }
};
