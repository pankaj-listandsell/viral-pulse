<?php

namespace Database\Factories;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGeneration>
 */
class AiGenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'fake',
            'model' => 'fake-model-1',
            'content_type' => 'news',
            'topic' => rtrim(fake()->sentence(6), '.'),
            'language' => 'en',
            'tone' => 'informative',
            'target_length' => 900,
            'prompt' => 'Write an article.',
            'status' => AiGenerationStatus::Pending,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiGenerationStatus::Completed,
            'quality_score' => 85,
        ]);
    }

    public function failed(string $message = 'Something went wrong'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiGenerationStatus::Failed,
            'error_message' => $message,
        ]);
    }
}
