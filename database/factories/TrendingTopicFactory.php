<?php

namespace Database\Factories;

use App\Enums\TrendingSource;
use App\Enums\TrendingTopicStatus;
use App\Models\TrendingTopic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrendingTopic>
 */
class TrendingTopicFactory extends Factory
{
    public function definition(): array
    {
        $topic = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'topic' => $topic,
            'topic_hash' => TrendingTopic::hashTopic($topic),
            'slug' => Str::slug($topic),
            'description' => fake()->sentence(15),
            'source' => TrendingSource::Manual,
            'trend_score' => fake()->numberBetween(1, 100),
            'region' => 'IN',
            'language' => 'en',
            'detected_at' => now(),
            'status' => TrendingTopicStatus::New,
        ];
    }

    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => ['status' => TrendingTopicStatus::Ignored]);
    }
}
