<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('trackmania_player_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('points');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('season_id');
            $table->index('trackmania_player_id');
            $table->index('map_id');
            $table->index('type');
            $table->unique(['season_id', 'map_id', 'trackmania_player_id', 'type'], 'point_events_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_events');
    }
};
