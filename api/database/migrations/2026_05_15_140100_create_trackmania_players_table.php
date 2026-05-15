<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trackmania_players', function (Blueprint $table): void {
            $table->id();
            $table->string('account_id')->unique();
            $table->string('display_name');
            $table->string('zone_id')->nullable();
            $table->string('zone_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_in_club_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trackmania_players');
    }
};
