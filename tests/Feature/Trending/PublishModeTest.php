<?php

namespace Tests\Feature\Trending;

use App\Enums\PostStatus;
use App\Enums\TrendingTopicStatus;
use App\Jobs\GenerateAiContentJob;
use App\Models\Category;
use App\Models\Post;
use App\Models\TrendingTopic;
use App\Models\User;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Providers\FakeProvider;
use App\Services\Trending\PublishWindow;
use App\Services\Trending\TrendingContentPlanner;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Two ways to publish, and the difference between them matters:
 *
 * immediate  - written when its time arrives, live as soon as it is ready.
 *              Simple, and the article is minutes old.
 * scheduled  - written ahead, published exactly on the minute. Survives a
 *              failed generation, because the article already exists.
 */
class PublishModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);

        Http::preventStrayRequests();
        config([
            'ai.providers.gemini.key' => 'test-key-not-real',
            'trending.automation.enabled' => true,
            'trending.publishing.slots' => '08:00, 13:00, 19:00',
        ]);

        app(AiProviderManager::class)->swap(new FakeProvider);

        $this->admin = User::factory()->admin()->create();
        Category::factory()->create();
    }

    private function topic(): TrendingTopic
    {
        return TrendingTopic::factory()->create([
            'status' => TrendingTopicStatus::New,
            'trend_score' => 90,
            'detected_at' => now(),
        ]);
    }

    public function test_immediate_mode_attaches_no_future_date(): void
    {
        Queue::fake();
        config(['trending.publishing.mode' => 'immediate']);

        $this->topic();

        $result = app(TrendingContentPlanner::class)->run(1);

        $this->assertSame(1, $result['queued']);

        // No publishAt: the job puts the post live the moment it is written.
        Queue::assertPushed(GenerateAiContentJob::class, fn ($job) => $job->publishAt === null);
    }

    public function test_scheduled_mode_reserves_a_future_slot(): void
    {
        Queue::fake();
        Carbon::setTestNow(today()->setTime(7, 0));
        config(['trending.publishing.mode' => 'scheduled']);

        $this->topic();

        app(TrendingContentPlanner::class)->run(1);

        Queue::assertPushed(GenerateAiContentJob::class, fn ($job) => str_contains((string) $job->publishAt, 'T08:00'));

        Carbon::setTestNow();
    }

    public function test_immediate_mode_publishes_the_article_straight_away(): void
    {
        config([
            'trending.publishing.mode' => 'immediate',
            'site.content.auto_publish' => true,
            'site.content.min_quality_score' => 10,
        ]);

        $this->topic();

        app(TrendingContentPlanner::class)->run(1);

        $post = Post::latest('id')->first();

        $this->assertNotNull($post);
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNull($post->scheduled_at);
    }

    public function test_the_command_waits_for_one_of_the_configured_times(): void
    {
        config(['trending.publishing.mode' => 'immediate']);
        $this->topic();

        Carbon::setTestNow(today()->setTime(10, 30));
        Queue::fake();

        // 10:30 is not one of the listed times, so the minute-by-minute run
        // does nothing at all.
        $this->artisan('content:generate-trending')->assertSuccessful();
        Queue::assertNothingPushed();

        Carbon::setTestNow(today()->setTime(13, 0));
        $this->artisan('content:generate-trending')->assertSuccessful();
        Queue::assertPushed(GenerateAiContentJob::class);

        Carbon::setTestNow();
    }

    public function test_a_slot_a_few_seconds_late_still_counts(): void
    {
        config(['trending.publishing.mode' => 'immediate']);

        // A scheduler tick rarely lands exactly on the second.
        Carbon::setTestNow(today()->setTime(13, 0, 40));

        $this->assertTrue(app(PublishWindow::class)->isSlotTimeNow());

        Carbon::setTestNow(today()->setTime(13, 5));
        $this->assertFalse(app(PublishWindow::class)->isSlotTimeNow());

        Carbon::setTestNow();
    }

    public function test_force_ignores_the_clock(): void
    {
        config(['trending.publishing.mode' => 'immediate']);
        Carbon::setTestNow(today()->setTime(10, 30));
        Queue::fake();

        $this->topic();

        $this->artisan('content:generate-trending --force --limit=1')->assertSuccessful();

        Queue::assertPushed(GenerateAiContentJob::class);

        Carbon::setTestNow();
    }

    public function test_scheduled_mode_will_not_write_days_ahead(): void
    {
        Queue::fake();
        Carbon::setTestNow(today()->setTime(19, 30));
        config([
            'trending.publishing.mode' => 'scheduled',
            'trending.publishing.max_lookahead_hours' => 3,
        ]);

        $this->topic();

        // Every slot today has passed, and tomorrow's 08:00 is more than three
        // hours away. Writing it now would produce an article about today's
        // news that goes live tomorrow morning, by which point it is wrong.
        $result = app(TrendingContentPlanner::class)->run(1);

        $this->assertSame(0, $result['queued']);
        Queue::assertNothingPushed();

        Carbon::setTestNow();
    }
}
