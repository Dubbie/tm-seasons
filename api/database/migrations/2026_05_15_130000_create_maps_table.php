<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('uid')->unique();
            $table->string('nadeo_map_id')->nullable();
            $table->string('name')->nullable();
            $table->string('author_account_id')->nullable();
            $table->string('author_name')->nullable();
            $table->unsignedInteger('author_time')->nullable();
            $table->unsignedInteger('gold_time')->nullable();
            $table->unsignedInteger('silver_time')->nullable();
            $table->unsignedInteger('bronze_time')->nullable();
            $table->string('map_type')->nullable();
            $table->string('map_style')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('collection_name')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('updated_at_source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
