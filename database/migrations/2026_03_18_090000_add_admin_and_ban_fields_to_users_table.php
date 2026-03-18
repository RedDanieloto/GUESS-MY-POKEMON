<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('avatar_url');
            $table->boolean('is_banned')->default(false)->after('is_admin');
            $table->string('banned_reason', 255)->nullable()->after('is_banned');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_admin', 'is_banned', 'banned_reason']);
        });
    }
};
