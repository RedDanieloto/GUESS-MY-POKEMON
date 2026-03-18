<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE player_gacha_rewards MODIFY source ENUM('level_up','tier_up','admin') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE player_gacha_rewards MODIFY source ENUM('level_up','tier_up') NOT NULL");
    }
};
