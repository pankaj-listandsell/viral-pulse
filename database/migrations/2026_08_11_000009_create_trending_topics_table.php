<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trending_topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            // sha1 of the normalised topic. Unique, so repeated feed pulls
            // cannot create duplicates.
            $table->char('topic_hash', 40)->unique();
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('source', ['manual', 'rss', 'google_trends', 'news_api', 'social'])->default('manual');
            $table->string('source_url')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('trend_score')->default(0);
            $table->char('region', 5)->nullable();
            $table->char('language', 5)->default('en');
            $table->json('raw_payload')->nullable();
            $table->timestamp('detected_at');
            $table->enum('status', [
                'new', 'queued', 'generating', 'generated', 'scheduled', 'ignored', 'failed',
            ])->default('new');
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'trend_score']);
            $table->index(['source', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trending_topics');
    }
};
