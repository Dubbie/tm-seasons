<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('discord_id')->nullable()->unique()->after('email');
            $table->string('discord_username')->nullable()->after('discord_id');
            $table->string('discord_global_name')->nullable()->after('discord_username');
            $table->string('discord_avatar')->nullable()->after('discord_global_name');
            $table->boolean('is_admin')->default(false)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['discord_id']);
            $table->dropColumn([
                'discord_id',
                'discord_username',
                'discord_global_name',
                'discord_avatar',
                'is_admin',
                'last_login_at',
            ]);
        });
    }
};
