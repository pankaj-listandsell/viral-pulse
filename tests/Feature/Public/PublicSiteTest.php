<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Cache::flush();
        $this->category = Category::factory()->create(['name' => 'Technology']);
    }

    public function test_every_public_page_renders(): void
    {
        $post = Post::factory()->for($this->category)->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag);

        $pages = [
            'home' => route('home'),
            'latest' => route('latest'),
            'trending' => route('trending'),
            'categories' => route('categories.index'),
            'category' => route('categories.show', $this->category),
            'tag' => route('tags.show', $tag),
            'post' => route('posts.show', $post),
            'search' => route('search'),
            'search with term' => route('search', ['q' => 'technology']),
            'contact' => route('contact'),
            'about' => route('pages.show', 'about'),
            'privacy' => route('pages.show', 'privacy'),
            'terms' => route('pages.show', 'terms'),
            'disclaimer' => route('pages.show', 'disclaimer'),
            'sitemap' => route('sitemap.page'),
        ];

        foreach ($pages as $label => $url) {
            $this->get($url)->assertOk("The {$label} page failed to render.");
        }
    }

    public function test_the_article_body_is_present_in_the_html_without_javascript(): void
    {
        $post = Post::factory()->for($this->category)->create([
            'title' => 'Bengaluru metro expansion explained',
            'content' => '<p>The new line opens in March and cuts the commute by half.</p>',
        ]);

        // This is the whole point of server rendering: a crawler that executes
        // no JavaScript still receives the article.
        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Bengaluru metro expansion explained')
            ->assertSee('cuts the commute by half', false);
    }

    public function test_a_draft_is_not_reachable_by_a_visitor(): void
    {
        $this->get(route('posts.show', Post::factory()->draft()->create()))->assertNotFound();
        $this->get(route('posts.show', Post::factory()->scheduled()->create()))->assertNotFound();
        $this->get(route('posts.show', Post::factory()->archived()->create()))->assertNotFound();
    }

    public function test_a_post_dated_in_the_future_is_not_reachable(): void
    {
        $post = Post::factory()->create(['published_at' => now()->addWeek()]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_an_admin_can_preview_a_draft(): void
    {
        $post = Post::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Preview');
    }

    public function test_a_hidden_category_returns_404(): void
    {
        $hidden = Category::factory()->inactive()->create();

        $this->get(route('categories.show', $hidden))->assertNotFound();
    }

    public function test_listings_only_contain_published_posts(): void
    {
        Post::factory()->for($this->category)->create(['title' => 'A live story']);
        Post::factory()->for($this->category)->draft()->create(['title' => 'A hidden draft']);

        $this->get(route('latest'))
            ->assertSee('A live story')
            ->assertDontSee('A hidden draft');
    }

    public function test_the_public_site_never_links_to_the_admin_panel(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee('/admin/login', false);
        $response->assertDontSee('Sign in');
    }

    public function test_search_finds_a_post_and_reports_an_empty_state(): void
    {
        Post::factory()->for($this->category)->create(['title' => 'Monsoon travel guide']);

        $this->get(route('search', ['q' => 'Monsoon']))
            ->assertOk()
            ->assertSee('Monsoon travel guide');

        $this->get(route('search', ['q' => 'zzzzqqqq']))
            ->assertOk()
            ->assertSee('No results');
    }

    public function test_ad_slots_render_nothing_while_adsense_is_off(): void
    {
        $post = Post::factory()->for($this->category)->create();

        $this->get(route('posts.show', $post))
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('Advertisement');
    }
}
