<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table): void {
            $table->enum('visibility', ['public', 'private'])->default('private')->after('difficulty');
            $table->string('room_name', 60)->nullable()->after('visibility');
            $table->enum('language', ['es', 'en'])->default('es')->after('room_name');

            $table->index(['mode', 'visibility', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table): void {
            $table->dropIndex('game_rooms_mode_visibility_status_index');
            $table->dropColumn(['visibility', 'room_name', 'language']);
        });
    }
};
