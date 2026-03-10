<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('title');
            $table->string('description');
            $table->foreignId('reward_pokemon_id')->nullable()->constrained('pokemons')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['player_profile_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_achievements');
    }
};
