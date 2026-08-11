<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Highest-volume table in the schema. No timestamps, no soft deletes, and
     * visitor identity is stored only as a salted hash - never a raw IP.
     * Rows are rolled up into post_daily_stats nightly and then pruned.
     */
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('ip_hash', 64);
            $table->char('user_agent_hash', 64)->nullable();
            $table->string('referrer')->nullable();
            $table->char('country', 2)->nullable();
            $table->enum('device', ['desktop', 'mobile', 'tablet', 'bot'])->nullable();
            $table->timestamp('viewed_at');

            $table->index(['post_id', 'viewed_at']);
            // Dedupe window lookup: has this visitor already been counted?
            $table->index(['ip_hash', 'post_id', 'viewed_at']);
            // Pruning.
            $table->index('viewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
