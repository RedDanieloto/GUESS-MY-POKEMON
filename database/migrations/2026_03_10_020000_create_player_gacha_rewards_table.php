<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_gacha_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pokemon_id')->constrained('pokemons')->cascadeOnDelete();
            $table->enum('source', ['level_up', 'tier_up']);
            $table->unsignedInteger('level_reached')->nullable();
            $table->string('tier_code', 30)->nullable();
            $table->enum('rarity', ['normal', 'rare', 'special', 'ultra', 'mythic', 'legendary']);
            $table->string('ball_type', 30);
            $table->boolean('is_opened')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['player_profile_id', 'is_opened']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_gacha_rewards');
    }
};
