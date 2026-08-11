<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            // One like per member and one per guest fingerprint.
            $table->unique(['post_id', 'user_id']);
            $table->unique(['post_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_likes');
    }
};
