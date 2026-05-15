<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('season_map_player_records', function (Blueprint $table): void {
            $table->renameColumn('current_position', 'global_position');
        });
    }

    public function down(): void
    {
        Schema::table('season_map_player_records', function (Blueprint $table): void {
            $table->renameColumn('global_position', 'current_position');
        });
    }
};
