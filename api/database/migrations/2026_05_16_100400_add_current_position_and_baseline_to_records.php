<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('season_map_player_records', function (Blueprint $table): void {
            $table->unsignedInteger('current_position')->nullable()->after('global_position');
            $table->unsignedBigInteger('baseline_time_ms')->nullable()->after('time_ms');
        });
    }

    public function down(): void
    {
        Schema::table('season_map_player_records', function (Blueprint $table): void {
            $table->dropColumn(['current_position', 'baseline_time_ms']);
        });
    }
};
