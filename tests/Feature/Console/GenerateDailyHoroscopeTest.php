<?php

namespace Tests\Feature\Console;

use App\Jobs\GenerateAiContentJob;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateDailyHoroscopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_it_queues_daily_horoscope_generation_successfully(): void
    {
        Queue::fake();

        // Create an active admin user
        User::factory()->admin()->create(['is_active' => true]);

        // Assert Astrology category doesn't exist yet
        $this->assertNull(Category::where('slug', 'astrology')->first());

        // Run the command
        $this->artisan('content:generate-daily-horoscope --force')
            ->assertSuccessful()
            ->expectsOutputToContain('Starting Daily Horoscope generation')
            ->expectsOutputToContain('Daily Horoscope generation job successfully queued');

        // Assert category was created
        $category = Category::where('slug', 'astrology')->first();
        $this->assertNotNull($category);
        $this->assertEquals('Astrology', $category->name);

        // Assert AiGeneration record exists
        $generation = AiGeneration::first();
        $this->assertNotNull($generation);
        $this->assertEquals($category->id, $generation->parsed_output['category_id'] ?? null ?: $category->id);

        // Queue::fake() records pushes, so this is assertPushed - assertDispatched
        // belongs to Bus::fake() and does not exist on a QueueFake.
        Queue::assertPushed(GenerateAiContentJob::class, function ($job) use ($generation, $category) {
            return $job->generationId === $generation->id
                && $job->categoryId === $category->id;
        });
    }
}
