<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaderboard_snapshots', function (Blueprint $table): void {
            $table->renameColumn('position', 'global_position');
        });

        Schema::table('leaderboard_snapshots', function (Blueprint $table): void {
            $table->unsignedInteger('current_position')->nullable()->after('global_position');
        });
    }

    public function down(): void
    {
        Schema::table('leaderboard_snapshots', function (Blueprint $table): void {
            $table->dropColumn('current_position');
        });

        Schema::table('leaderboard_snapshots', function (Blueprint $table): void {
            $table->renameColumn('global_position', 'position');
        });
    }
};
