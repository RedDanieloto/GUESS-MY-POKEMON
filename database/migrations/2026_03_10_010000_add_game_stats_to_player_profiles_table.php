<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table): void {
            $table->unsignedInteger('questions_asked')->default(0)->after('wins');
            $table->unsignedInteger('questions_answered')->default(0)->after('questions_asked');
            $table->unsignedInteger('guesses_made')->default(0)->after('questions_answered');
            $table->unsignedInteger('correct_guesses')->default(0)->after('guesses_made');
        });
    }

    public function down(): void
    {
        Schema::table('player_profiles', function (Blueprint $table): void {
            $table->dropColumn(['questions_asked', 'questions_answered', 'guesses_made', 'correct_guesses']);
        });
    }
};
