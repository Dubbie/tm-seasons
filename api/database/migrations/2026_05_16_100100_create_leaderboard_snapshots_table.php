<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leaderboard_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trackmania_player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedBigInteger('time_ms');
            $table->string('zone_name')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['season_id', 'map_id']);
            $table->index('trackmania_player_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
