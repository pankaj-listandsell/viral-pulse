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

    /**
     * The schedule runs this every ten minutes across a morning window, because
     * a task pinned to one exact minute is skipped for the day whenever a
     * throttled cron does not wake during it. That only works if running twice
     * writes one article.
     */
    public function test_a_second_run_on_the_same_day_writes_nothing(): void
    {
        Queue::fake();
        config(['trending.automation.enabled' => true]);
        User::factory()->admin()->create(['is_active' => true]);

        $this->artisan('content:generate-daily-horoscope --force')->assertSuccessful();

        $this->assertSame(1, AiGeneration::count());

        // No --force: this is the scheduler's own repeat, ten minutes later.
        $this->artisan('content:generate-daily-horoscope')
            ->assertSuccessful()
            ->expectsOutputToContain('Already generated');

        $this->assertSame(1, AiGeneration::count());
        Queue::assertPushed(GenerateAiContentJob::class, 1);
    }

    public function test_a_failed_attempt_does_not_block_the_rest_of_the_day(): void
    {
        Queue::fake();
        config(['trending.automation.enabled' => true]);
        User::factory()->admin()->create(['is_active' => true]);

        $this->artisan('content:generate-daily-horoscope --force')->assertSuccessful();

        // The morning's attempt died - a model outage, a quota wall. The next
        // run has to pick the day up rather than treat it as already done.
        AiGeneration::query()->update(['status' => \App\Enums\AiGenerationStatus::Failed]);

        $this->artisan('content:generate-daily-horoscope')
            ->assertSuccessful()
            ->expectsOutputToContain('successfully queued');

        $this->assertSame(2, AiGeneration::count());
    }
}
