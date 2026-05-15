<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trackmania_clubs', function (Blueprint $table): void {
            $table->id();
            $table->string('club_id')->unique();
            $table->string('name');
            $table->string('tag')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('member_count')->nullable();
            $table->string('icon_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trackmania_clubs');
    }
};
