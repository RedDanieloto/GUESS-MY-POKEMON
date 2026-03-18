<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE player_gacha_rewards MODIFY rarity ENUM('normal','rare','special','ultra','mythic','legendary','shiny') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE player_gacha_rewards MODIFY rarity ENUM('normal','rare','special','ultra','mythic','legendary') NOT NULL");
    }
};
