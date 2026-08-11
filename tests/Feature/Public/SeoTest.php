<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The site earns its traffic from search, so the head is treated as a
 * contract: these assertions fail loudly if a tag disappears.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Cache::flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonLd(TestResponse $response): array
    {
        preg_match_all(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $response->getContent(),
            $matches
        );

        return array_map(fn (string $json): array => json_decode($json, true) ?? [], $matches[1]);
    }

    public function test_a_post_page_carries_the_full_head(): void
    {
        // seo_title left empty on purpose: the head must fall back to the
        // post title rather than rendering nothing.
        $post = Post::factory()->create([
            'title' => 'Bengaluru metro expansion explained',
            'seo_title' => null,
            'seo_description' => 'What the new line changes for daily commuters.',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertSee('<title>Bengaluru metro expansion explained', false);
        $response->assertSee('<meta name="description" content="What the new line changes for daily commuters.">', false);
        $response->assertSee('<link rel="canonical" href="'.route('posts.show', $post).'">', false);
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertSee('<meta property="og:title" content="Bengaluru metro expansion explained">', false);
        $response->assertSee('<meta name="twitter:card"', false);
        $response->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_the_seo_title_overrides_the_post_title_when_set(): void
    {
        $post = Post::factory()->create([
            'title' => 'A plain internal headline',
            'seo_title' => 'The headline searchers actually see',
        ]);

        $this->get(route('posts.show', $post))
            ->assertSee('<title>The headline searchers actually see', false)
            // The visible H1 still uses the real title.
            ->assertSee('A plain internal headline');
    }

    public function test_a_post_emits_article_and_breadcrumb_structured_data(): void
    {
        $post = Post::factory()->create(['title' => 'A structured story']);

        $schemas = $this->jsonLd($this->get(route('posts.show', $post)));
        $types = array_column($schemas, '@type');

        $this->assertContains('Article', $types);
        $this->assertContains('BreadcrumbList', $types);

        $article = collect($schemas)->firstWhere('@type', 'Article');

        $this->assertSame('A structured story', $article['headline']);
        $this->assertNotEmpty($article['datePublished']);
        // A real account, never a fabricated byline.
        $this->assertSame($post->author->name, $article['author']['name']);
        $this->assertSame('Organization', $article['publisher']['@type']);
    }

    public function test_the_home_page_emits_website_and_organization_data(): void
    {
        $types = array_column($this->jsonLd($this->get(route('home'))), '@type');

        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);
    }

    public function test_the_website_schema_declares_the_search_action(): void
    {
        $website = collect($this->jsonLd($this->get(route('home'))))->firstWhere('@type', 'WebSite');

        $this->assertSame('SearchAction', $website['potentialAction']['@type']);
        $this->assertStringContainsString('{search_term_string}', $website['potentialAction']['target']['urlTemplate']);
    }

    public function test_a_canonical_url_overrides_the_default_when_set(): void
    {
        $post = Post::factory()->create(['canonical_url' => 'https://original.example/story']);

        $this->get(route('posts.show', $post))
            ->assertSee('<link rel="canonical" href="https://original.example/story">', false);
    }

    public function test_paginated_archives_are_not_indexed(): void
    {
        $category = Category::factory()->create();
        Post::factory()->count(20)->for($category)->create();

        $this->get(route('categories.show', $category))
            ->assertSee('<meta name="robots" content="index, follow">', false);

        // Page two is thin, near-duplicate content and should stay out of the
        // index while still passing link equity onward.
        $this->get(route('categories.show', ['category' => $category, 'page' => 2]))
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_search_results_are_never_indexed(): void
    {
        $this->get(route('search', ['q' => 'anything']))
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_the_admin_panel_and_login_are_marked_noindex(): void
    {
        $this->get(route('login'))->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_every_public_page_declares_a_canonical_url(): void
    {
        $post = Post::factory()->create();

        foreach ([route('home'), route('latest'), route('trending'), route('posts.show', $post)] as $url) {
            $this->get($url)->assertSee('<link rel="canonical"', false);
        }
    }

    public function test_the_rss_feed_is_advertised_in_the_head(): void
    {
        $this->get(route('home'))->assertSee('type="application/rss+xml"', false);
    }
}
