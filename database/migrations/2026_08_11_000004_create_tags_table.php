<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('posts_count')->default(0);
            $table->boolean('is_trending')->default(false);
            $table->timestamps();

            $table->index(['is_trending', 'posts_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
