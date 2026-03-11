<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table): void {
            $table->boolean('timer_enabled')->default(false)->after('winner_session_id');
            $table->integer('timer_seconds')->default(180)->after('timer_enabled');
            $table->integer('timer_p1_remaining')->nullable()->after('timer_seconds');
            $table->integer('timer_p2_remaining')->nullable()->after('timer_p1_remaining');
            $table->timestamp('timer_last_tick')->nullable()->after('timer_p2_remaining');
            $table->string('timer_proposed_by', 64)->nullable()->after('timer_last_tick');
            $table->string('surrendered_by', 64)->nullable()->after('timer_proposed_by');
        });
    }

    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table): void {
            $table->dropColumn([
                'timer_enabled',
                'timer_seconds',
                'timer_p1_remaining',
                'timer_p2_remaining',
                'timer_last_tick',
                'timer_proposed_by',
                'surrendered_by',
            ]);
        });
    }
};
