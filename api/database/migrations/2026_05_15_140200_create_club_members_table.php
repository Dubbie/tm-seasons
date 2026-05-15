<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trackmania_club_id')->constrained('trackmania_clubs')->cascadeOnDelete();
            $table->foreignId('trackmania_player_id')->constrained('trackmania_players')->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['trackmania_club_id', 'trackmania_player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_members');
    }
};
