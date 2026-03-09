<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pokemons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('pokeapi_id')->unique();
            $table->string('slug')->unique();
            $table->string('display_name');
            $table->unsignedTinyInteger('generation')->nullable();
            $table->string('primary_type');
            $table->string('secondary_type')->nullable();
            $table->boolean('is_legendary')->default(false);
            $table->boolean('is_mythical')->default(false);
            $table->boolean('is_baby')->default(false);
            $table->unsignedSmallInteger('height_dm');
            $table->unsignedSmallInteger('weight_hg');
            $table->unsignedSmallInteger('base_experience')->nullable();
            $table->json('abilities')->nullable();
            $table->json('stats')->nullable();
            $table->json('sprites')->nullable();
            $table->timestamps();

            $table->index(['generation', 'primary_type']);
            $table->index(['generation', 'secondary_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};
