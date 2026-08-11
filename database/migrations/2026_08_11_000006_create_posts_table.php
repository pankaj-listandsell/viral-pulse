<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();

            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            $table->enum('source_type', ['manual', 'ai', 'trending', 'imported'])->default('manual');
            $table->boolean('ai_generated')->default(false);
            $table->char('language', 5)->default('en');
            $table->unsignedSmallInteger('reading_time')->default(0);

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);

            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The archive/feed query path: everything public filters on these two.
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            // The scheduler polls this every minute.
            $table->index(['status', 'scheduled_at']);
            $table->index(['is_featured', 'status']);
            $table->index(['is_trending', 'status']);
            $table->index('ai_generated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
