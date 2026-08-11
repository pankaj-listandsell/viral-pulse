<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nightly rollup of post_views. The dashboard charts read only from here,
     * so they stay fast after post_views grows into the millions.
     */
    public function up(): void
    {
        Schema::create('post_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('unique_views')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->timestamps();

            $table->unique(['post_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_daily_stats');
    }
};
