<?php

namespace Database\Factories;

use App\Enums\PostSourceType;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');
        $content = collect(fake()->paragraphs(8))
            ->map(fn (string $p): string => '<p>'.e($p).'</p>')
            ->implode("\n");

        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'excerpt' => fake()->sentence(20),
            'content' => $content,
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-6 months'),
            'source_type' => PostSourceType::Manual,
            'ai_generated' => false,
            'language' => 'en',
            'reading_time' => max(1, (int) round(str_word_count(strip_tags($content)) / 200)),
            'seo_title' => $title,
            'seo_description' => fake()->sentence(18),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function scheduled(?\DateTimeInterface $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Scheduled,
            'published_at' => null,
            'scheduled_at' => $at ?? now()->addDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PostStatus::Archived]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }

    public function trending(): static
    {
        return $this->state(fn (array $attributes) => ['is_trending' => true]);
    }

    public function aiGenerated(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_generated' => true,
            'source_type' => PostSourceType::Ai,
        ]);
    }
}
