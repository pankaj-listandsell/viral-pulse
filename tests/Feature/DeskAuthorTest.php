<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Jobs\GenerateAiContentJob;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Providers\FakeProvider;
use App\Services\AuthorPicker;
use App\Services\SitemapService;
use App\Support\DeskAuthors;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeskAuthorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Storage::fake(config('site.media.disk'));

        // The migration creates them too, but another test may have emptied
        // the users table since; sync() only adds what is missing.
        DeskAuthors::sync();
    }

    private function desk(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function test_there_are_eight_desks_and_none_can_sign_in(): void
    {
        DeskAuthors::sync(); // a second run adds nothing

        $desks = User::authors()->get();

        $this->assertCount(8, $desks);
        $this->assertTrue($desks->every(fn (User $desk) => ! $desk->canAccessAdminPanel() && filled($desk->bio)));
    }

    public function test_an_article_is_bylined_by_the_desk_covering_its_section(): void
    {
        $astrology = Category::factory()->create(['slug' => 'astrology']);

        $this->assertSame('astrology-desk', app(AuthorPicker::class)->forCategory($astrology->id)->username);
    }

    public function test_a_section_no_desk_covers_still_gets_a_desk(): void
    {
        $odd = Category::factory()->create(['slug' => 'something-new']);

        $this->assertTrue(app(AuthorPicker::class)->forCategory($odd->id)->is_author);
    }

    public function test_an_automatic_post_is_published_under_a_desk(): void
    {
        Queue::fake();
        app(AiProviderManager::class)->swap(new FakeProvider);
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $sports = Category::factory()->create(['slug' => 'sports']);

        $generation = app(AiContentService::class)->queue(
            new \App\Services\AI\GenerationRequest('Cricket fixtures this week', \App\Enums\ContentType::cases()[0], \App\Enums\ContentTone::Informative, $sports),
            $admin,
        );

        (new GenerateAiContentJob(
            generationId: $generation->id,
            requestData: ['topic' => 'Cricket fixtures this week', 'content_type' => \App\Enums\ContentType::cases()[0]->value, 'category_id' => $sports->id],
            userId: $admin->id,
            categoryId: $sports->id,
        ))->handle(app(AiContentService::class));

        $post = Post::latest('id')->firstOrFail();

        $this->assertSame('sports-desk', $post->author->username);
        // The admin who asked is still on record, just not on the byline.
        $this->assertSame($admin->id, $generation->refresh()->user_id);
    }

    public function test_the_post_page_shows_the_desk_and_names_it_in_the_schema(): void
    {
        $desk = $this->desk('tech-desk');
        $post = Post::factory()->create([
            'author_id' => $desk->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('ViralPulse Tech Desk')
            ->assertSee(route('authors.show', 'tech-desk'), false)
            ->assertSee('"author":{"@type":"Organization","name":"ViralPulse Tech Desk"', false);
    }

    public function test_a_post_without_a_desk_keeps_the_masthead(): void
    {
        $post = Post::factory()->create([
            'author_id' => User::factory()->admin()->create()->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('Written by');
    }

    public function test_the_desk_page_lists_its_stories_and_is_in_the_sitemap(): void
    {
        $desk = $this->desk('business-desk');
        $post = Post::factory()->create([
            'author_id' => $desk->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('authors.show', 'business-desk'))
            ->assertOk()
            ->assertSee('ViralPulse Business Desk')
            ->assertSee($post->title)
            ->assertSee('"@type":"ProfilePage"', false);

        $this->assertStringContainsString(route('authors.show', 'business-desk'), app(SitemapService::class)->pages());
        // A desk with nothing published is left out.
        $this->assertStringNotContainsString(route('authors.show', 'sports-desk'), app(SitemapService::class)->pages());
    }

    public function test_an_admin_account_has_no_author_page(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'boss']);

        $this->get(route('authors.show', $admin->username))->assertNotFound();
    }
}
