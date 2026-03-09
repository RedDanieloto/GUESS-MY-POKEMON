<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->string('asked_by_session_id', 64);
            $table->string('target_session_id', 64)->nullable();
            $table->string('question_key', 80)->nullable();
            $table->text('question_text');
            $table->enum('answer', ['yes', 'no', 'unknown'])->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['game_room_id', 'created_at']);
            $table->index(['game_room_id', 'question_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_questions');
    }
};
