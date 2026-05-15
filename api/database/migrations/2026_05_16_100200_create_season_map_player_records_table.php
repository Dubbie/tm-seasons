<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_map_player_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trackmania_player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_position')->nullable();
            $table->unsignedBigInteger('time_ms')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_improved_at')->nullable();
            $table->unsignedInteger('total_improvements')->default(0);
            $table->timestamps();

            $table->unique(['season_id', 'map_id', 'trackmania_player_id'], 'smp_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_map_player_records');
    }
};
