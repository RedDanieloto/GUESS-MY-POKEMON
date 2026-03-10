<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    // This migration has been consolidated into 2026_03_09_180100_create_game_rooms_table.php
    // Keeping this file for migration history compatibility, but it's now a no-op
    public function up(): void
    {
        // Fields are now created in the main game_rooms migration
    }

    public function down(): void
    {
        // No-op
    }
};
