<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trending_topic_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('content_type', 50);
            $table->string('topic');
            $table->char('language', 5)->default('en');
            $table->string('tone', 50)->nullable();
            $table->string('target_audience')->nullable();
            $table->unsignedInteger('target_length')->nullable();

            $table->longText('prompt');
            $table->longText('raw_response')->nullable();
            $table->json('parsed_output')->nullable();

            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'rejected'])->default('pending');
            $table->text('error_message')->nullable();

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedTinyInteger('quality_score')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
