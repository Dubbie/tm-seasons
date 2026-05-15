<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_map_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trackmania_player_id')->constrained()->cascadeOnDelete();
            $table->string('milestone_key');
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->unique(['season_id', 'map_id', 'trackmania_player_id', 'milestone_key'], 'player_map_milestone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_map_milestones');
    }
};
