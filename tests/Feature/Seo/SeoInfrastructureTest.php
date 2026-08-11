<?php

namespace Tests\Feature\Seo;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    private function publishedPost(array $attributes = []): Post
    {
        return Post::factory()->create(array_merge([
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ], $attributes));
    }

    public function test_the_sitemap_index_lists_every_sub_sitemap(): void
    {
        $this->publishedPost();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<sitemapindex', false)
            ->assertSee('sitemap-posts-1.xml')
            ->assertSee('sitemap-categories.xml')
            ->assertSee('sitemap-tags.xml')
            ->assertSee('sitemap-pages.xml');
    }

    public function test_the_post_sitemap_lists_published_posts_only(): void
    {
        $live = $this->publishedPost(['slug' => 'a-live-article']);
        Post::factory()->create(['slug' => 'a-draft-article', 'status' => PostStatus::Draft]);
        Post::factory()->create([
            'slug' => 'a-future-article',
            'status' => PostStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get('/sitemap-posts-1.xml')->assertOk();

        $response->assertSee(route('posts.show', $live->slug));
        // A draft or a post dated in the future is not public yet; listing it
        // sends a crawler to a 404.
        $response->assertDontSee('a-draft-article');
        $response->assertDontSee('a-future-article');
    }

    public function test_an_out_of_range_sitemap_page_is_a_404(): void
    {
        $this->publishedPost();

        $this->get('/sitemap-posts-9.xml')->assertNotFound();
    }

    public function test_empty_categories_and_tags_are_left_out(): void
    {
        $used = Category::factory()->create(['slug' => 'has-posts', 'posts_count' => 3]);
        Category::factory()->create(['slug' => 'is-empty', 'posts_count' => 0]);
        Tag::factory()->create(['slug' => 'used-tag', 'posts_count' => 2]);
        Tag::factory()->create(['slug' => 'unused-tag', 'posts_count' => 0]);

        $this->get('/sitemap-categories.xml')
            ->assertOk()
            ->assertSee(route('categories.show', $used->slug))
            // An archive with nothing in it reads as a soft 404.
            ->assertDontSee('is-empty');

        $this->get('/sitemap-tags.xml')
            ->assertOk()
            ->assertSee('used-tag')
            ->assertDontSee('unused-tag');
    }

    public function test_the_sitemap_is_rebuilt_when_a_post_is_published(): void
    {
        $post = Post::factory()->create(['slug' => 'not-yet-live', 'status' => PostStatus::Draft]);

        $this->get('/sitemap-posts-1.xml')->assertOk()->assertDontSee('not-yet-live');

        app(PostService::class)->publish($post);

        // Without cache invalidation this would keep serving the old set until
        // the TTL expired.
        $this->get('/sitemap-posts-1.xml')->assertOk()->assertSee('not-yet-live');
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        // A staging copy indexed next to the real site is painful to undo.
        $this->assertStringContainsString("User-agent: *\nDisallow: /", $body);
    }

    public function test_robots_in_production_points_at_the_sitemap_and_closes_the_admin(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $body = $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString('Sitemap: '.route('sitemap.index'), $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /search', $body);
        $this->assertStringNotContainsString("Allow: /\n\nDisallow: /\n", $body);
    }

    public function test_indexing_can_be_switched_off_from_settings(): void
    {
        app()->detectEnvironment(fn () => 'production');
        Setting::where('key', 'seo_discourage_indexing')->update(['value' => '1']);
        app(SettingsService::class)->flush();

        $this->assertStringContainsString('Disallow: /', $this->get('/robots.txt')->getContent());
    }

    public function test_the_rss_feed_carries_the_latest_posts(): void
    {
        $post = $this->publishedPost(['title' => 'A story worth syndicating']);

        $this->get('/feed.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<rss version="2.0"', false)
            ->assertSee('A story worth syndicating')
            ->assertSee(route('posts.show', $post->slug));
    }

    public function test_a_category_feed_only_carries_that_category(): void
    {
        $category = Category::factory()->create(['slug' => 'tech-feed']);
        $this->publishedPost(['title' => 'Belongs in tech', 'category_id' => $category->id]);
        $this->publishedPost(['title' => 'Belongs elsewhere']);

        $this->get('/feed/tech-feed.xml')
            ->assertOk()
            ->assertSee('Belongs in tech')
            ->assertDontSee('Belongs elsewhere');
    }

    public function test_the_feed_is_valid_xml(): void
    {
        // Titles with ampersands and angle brackets are the usual way a feed
        // stops parsing.
        $this->publishedPost(['title' => 'Tea & biscuits <are> "great" 100% of the time']);

        $body = $this->get('/feed.xml')->assertOk()->getContent();

        $this->assertNotFalse(simplexml_load_string($body), 'The feed is not well-formed XML.');
    }

    public function test_the_sitemap_is_valid_xml(): void
    {
        $this->publishedPost();

        $this->assertNotFalse(simplexml_load_string($this->get('/sitemap.xml')->getContent()));
        $this->assertNotFalse(simplexml_load_string($this->get('/sitemap-posts-1.xml')->getContent()));
    }

    public function test_ads_txt_is_absent_until_there_is_something_real_to_serve(): void
    {
        // An empty ads.txt tells buyers nobody is authorised, which is worse
        // than not having the file at all.
        $this->get('/ads.txt')->assertNotFound();
    }

    public function test_ads_txt_is_built_from_the_publisher_id(): void
    {
        Setting::where('key', 'adsense_client_id')->update(['value' => 'ca-pub-1234567890123456']);
        app(SettingsService::class)->flush();

        $this->get('/ads.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0');
    }

    public function test_a_renamed_post_keeps_its_old_url_working(): void
    {
        $post = $this->publishedPost(['title' => 'The original headline', 'slug' => 'the-original-headline']);

        app(PostService::class)->update($post, [
            'title' => 'A better headline',
            'slug' => 'a-better-headline',
            'content' => $post->content,
            'category_id' => $post->category_id,
            'status' => PostStatus::Published->value,
        ]);

        $this->assertDatabaseHas('post_slug_history', ['slug' => 'the-original-headline']);

        // 301, not 302: the move is permanent, and only a 301 passes on the
        // ranking the old URL earned.
        $this->get('/post/the-original-headline')
            ->assertStatus(301)
            ->assertRedirect(route('posts.show', 'a-better-headline'));
    }

    public function test_an_unknown_slug_is_still_a_404(): void
    {
        $this->get('/post/never-existed')->assertNotFound();
    }

    public function test_the_root_url_is_left_alone(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_a_post_page_carries_article_and_breadcrumb_structured_data(): void
    {
        $post = $this->publishedPost();

        $body = $this->get(route('posts.show', $post->slug))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"Article"', $body);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $body);
        $this->assertStringContainsString('<link rel="canonical"', $body);
    }

    public function test_an_archive_carries_an_item_list(): void
    {
        $this->publishedPost();

        $this->get(route('latest'))
            ->assertOk()
            ->assertSee('"@type":"ItemList"', false);
    }

    public function test_paginated_archives_are_kept_out_of_the_index(): void
    {
        Post::factory()->count(15)->create([
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('latest'))->assertOk()->assertSee('content="index, follow"', false);
        $this->get(route('latest').'?page=2')->assertOk()->assertSee('content="noindex, follow"', false);
    }

    public function test_search_pages_are_never_indexed(): void
    {
        $this->get(route('search').'?q=anything')
            ->assertOk()
            ->assertSee('content="noindex, follow"', false);
    }

    public function test_admin_pages_are_not_reachable_through_the_sitemap(): void
    {
        User::factory()->admin()->create();

        $all = $this->get('/sitemap-pages.xml')->getContent()
            .$this->get('/sitemap-categories.xml')->getContent();

        $this->assertStringNotContainsString('/admin', $all);
    }
}
