<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trackmania_clubs', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false)->after('icon_url');
        });

        $firstClub = DB::table('trackmania_clubs')->orderBy('id')->first();
        if ($firstClub) {
            DB::table('trackmania_clubs')->where('id', $firstClub->id)->update(['is_primary' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('trackmania_clubs', function (Blueprint $table): void {
            $table->dropColumn('is_primary');
        });
    }
};
