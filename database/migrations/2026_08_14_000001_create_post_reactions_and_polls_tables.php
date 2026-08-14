<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('reaction', 32); // 'fire', 'insight', 'shock', 'love'
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['post_id', 'user_id']);
            $table->unique(['post_id', 'ip_hash']);
            $table->index(['post_id', 'reaction']);
        });

        Schema::create('post_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('option', 64); // 'yes', 'no', 'neutral'
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['post_id', 'user_id']);
            $table->unique(['post_id', 'ip_hash']);
            $table->index(['post_id', 'option']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_polls');
        Schema::dropIfExists('post_reactions');
    }
};
