<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every slug a post has ever had.
     *
     * Renaming a published article otherwise breaks every existing link to it
     * and throws away whatever ranking the old URL had earned. One row here
     * turns that into a 301.
     */
    public function up(): void
    {
        Schema::create('post_slug_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamp('created_at')->nullable();

            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_slug_history');
    }
};
