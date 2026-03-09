<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('session_id', 64)->unique();
            $table->string('nickname', 40)->nullable();
            $table->enum('experience_tier', ['beginner', 'intermediate', 'expert'])->default('beginner');
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['experience_tier', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
