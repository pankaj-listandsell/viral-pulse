<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => rtrim(fake()->sentence(5), '.'),
            'message' => fake()->paragraph(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'status' => ContactMessageStatus::New,
        ];
    }
}
