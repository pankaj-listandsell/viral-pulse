<?php

namespace Database\Factories;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'token' => Str::random(64),
            'status' => SubscriberStatus::Pending,
            'source' => 'footer',
        ];
    }

    public function subscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::Subscribed,
            'confirmed_at' => now(),
        ]);
    }
}
