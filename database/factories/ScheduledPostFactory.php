<?php

namespace Database\Factories;

use App\Enums\ScheduledPostStatus;
use App\Models\Post;
use App\Models\ScheduledPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledPost>
 */
class ScheduledPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'scheduled_at' => now()->addHour(),
            'status' => ScheduledPostStatus::Pending,
            'attempts' => 0,
            'last_error' => null,
            'published_at' => null,
        ];
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => ['scheduled_at' => now()->subMinute()]);
    }
}
