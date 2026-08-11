<?php

namespace Tests\Feature\Trending;

use App\Enums\PostStatus;
use App\Enums\TrendingTopicStatus;
use App\Jobs\GenerateAiContentJob;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\TrendingTopic;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Providers\FakeProvider;
use App\Services\Trending\TrendingContentPlanner;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TrendingGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    private FakeProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);

        Http::preventStrayRequests();
        config(['ai.providers.gemini.key' => 'test-key-not-real']);

        $this->provider = new FakeProvider;
        app(AiProviderManager::class)->swap($this->provider);

        $this->admin = User::factory()->admin()->create();

        // Named explicitly rather than left to faker: part of the suite
        // truncates instead of rolling back, so a random two-word name can
        // collide with a category another test left behind.
        $this->category = Category::factory()->create([
            'name' => 'Trending Pipeline Fixture',
            'slug' => 'trending-pipeline-fixture',
        ]);
    }

    private function topic(array $attributes = []): TrendingTopic
    {
        return TrendingTopic::factory()->create(array_merge([
            'category_id' => $this->category->id,
            'trend_score' => 90,
            'detected_at' => now()->subHour(),
        ], $attributes));
    }

    private function runJobFor(TrendingTopic $topic): void
    {
        $generation = AiGeneration::where('trending_topic_id', $topic->id)->sole();
        $job = Queue::pushedJobs()[GenerateAiContentJob::class][0]['job'];

        $this->assertSame($generation->id, $job->generationId);

        $job->handle(app(AiContentService::class));
    }

    /**
     * Resolved through the generation rather than Post::sole(): part of the
     * suite truncates instead of rolling back, so a bare "there is one post"
     * assertion is not reliable.
     */
    private function postFor(TrendingTopic $topic): Post
    {
        $postId = AiGeneration::where('trending_topic_id', $topic->id)->sole()->post_id;

        $this->assertNotNull($postId, 'The generation did not create a post.');

        return Post::findOrFail($postId);
    }

    public function test_the_run_picks_the_highest_scoring_topics_and_spaces_them_out(): void
    {
        Queue::fake();

        $this->topic(['topic' => 'The low scoring one', 'trend_score' => 50]);
        $this->topic(['topic' => 'The high scoring one', 'trend_score' => 95]);
        $this->topic(['topic' => 'The middle one', 'trend_score' => 70]);

        $result = app(TrendingContentPlanner::class)->run(limit: 2);

        $this->assertSame(2, $result['queued']);
        $this->assertCount(2, $result['slots']);
        // Two articles in the same run must not land in the same minute.
        $this->assertNotSame($result['slots'][0], $result['slots'][1]);

        $topics = AiGeneration::pluck('topic');
        $this->assertContains('The high scoring one', $topics);
        $this->assertContains('The middle one', $topics);
        $this->assertNotContains('The low scoring one', $topics);
    }

    public function test_a_topic_is_claimed_before_dispatch_so_it_cannot_run_twice(): void
    {
        Queue::fake();

        $topic = $this->topic();

        app(TrendingContentPlanner::class)->run(limit: 1);

        $this->assertSame(TrendingTopicStatus::Generating, $topic->refresh()->status);

        // A second run has nothing left to pick up.
        $this->assertSame(0, app(TrendingContentPlanner::class)->run(limit: 1)['queued']);
    }

    public function test_low_scoring_and_stale_topics_are_skipped(): void
    {
        Queue::fake();

        $this->topic(['trend_score' => 10]);
        $this->topic(['trend_score' => 95, 'detected_at' => now()->subDays(5)]);
        $this->topic(['trend_score' => 95])->forceFill(['status' => TrendingTopicStatus::Ignored])->save();

        $this->assertSame(0, app(TrendingContentPlanner::class)->run(limit: 5)['queued']);
    }

    public function test_a_blocked_topic_is_never_picked_up_automatically(): void
    {
        Queue::fake();

        $this->topic([
            'topic' => 'A public figure died last night, say reports',
            'trend_score' => 100,
        ]);

        $this->assertSame(0, app(TrendingContentPlanner::class)->run(limit: 5)['queued']);
    }

    public function test_generated_content_is_scheduled_rather_than_published_at_once(): void
    {
        Queue::fake();

        config(['site.content.auto_publish' => true]);

        $topic = $this->topic();

        app(TrendingContentPlanner::class)->run(limit: 1);
        $this->runJobFor($topic);

        $post = $this->postFor($topic);

        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertNotNull($post->scheduled_at);
        $this->assertTrue($post->scheduled_at->isFuture());
        $this->assertTrue($post->ai_generated);

        // The publisher command reads scheduled_posts, not the post column.
        $this->assertDatabaseHas('scheduled_posts', ['post_id' => $post->id, 'status' => 'pending']);

        $topic->refresh();
        $this->assertSame(TrendingTopicStatus::Scheduled, $topic->status);
        $this->assertSame($post->id, $topic->post_id);
    }

    public function test_nothing_is_scheduled_while_auto_publish_is_off(): void
    {
        Queue::fake();

        config(['site.content.auto_publish' => false]);

        $topic = $this->topic();

        app(TrendingContentPlanner::class)->run(limit: 1);
        $this->runJobFor($topic);

        $post = $this->postFor($topic);

        // The default has to be a draft: a scheduled post is still an
        // unreviewed post going live.
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame(0, ScheduledPost::where('post_id', $post->id)->count());
        $this->assertSame(TrendingTopicStatus::Generated, $topic->refresh()->status);
    }

    public function test_content_that_fails_the_quality_gate_stays_a_draft(): void
    {
        Queue::fake();

        config(['site.content.auto_publish' => true]);

        $this->provider->willReturn([
            'title' => 'A headline that is long enough',
            'slug' => 'too-short',
            'excerpt' => 'Summary.',
            'content' => '<h2>Section</h2><p>Far too short to publish.</p>',
            'seo_title' => 'Short',
            'seo_description' => 'Short.',
            'seo_keywords' => 'a',
            'tags' => [],
            'image_prompt' => 'x',
        ]);

        $topic = $this->topic();

        app(TrendingContentPlanner::class)->run(limit: 1);
        $this->runJobFor($topic);

        $post = $this->postFor($topic);

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame(0, ScheduledPost::where('post_id', $post->id)->count());
    }

    public function test_a_failed_generation_releases_the_topic_for_another_attempt(): void
    {
        Queue::fake();

        $topic = $this->topic();

        app(TrendingContentPlanner::class)->run(limit: 1);

        $job = Queue::pushedJobs()[GenerateAiContentJob::class][0]['job'];
        $job->failed(new \RuntimeException('provider exploded'));

        $this->assertSame(TrendingTopicStatus::Failed, $topic->refresh()->status);
        // Failed is still eligible, so the next scheduled run retries it.
        $this->assertTrue($topic->status->isAvailableForGeneration());
    }

    public function test_the_admin_can_write_one_topic_on_demand(): void
    {
        Queue::fake();

        $topic = $this->topic();

        $this->actingAs($this->admin)
            ->post(route('admin.trending.generate', $topic))
            ->assertSessionHasNoErrors();

        Queue::assertPushed(GenerateAiContentJob::class);
        $this->assertSame(TrendingTopicStatus::Generating, $topic->refresh()->status);
    }

    public function test_the_command_does_nothing_while_automation_is_off(): void
    {
        Queue::fake();

        config(['trending.automation.enabled' => false]);
        $this->topic();

        $this->artisan('content:generate-trending')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_the_command_runs_when_forced(): void
    {
        Queue::fake();

        config(['trending.automation.enabled' => false]);
        $this->topic();

        $this->artisan('content:generate-trending --force --limit=1')->assertSuccessful();

        Queue::assertPushed(GenerateAiContentJob::class);
    }
}
