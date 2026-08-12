<?php

namespace Tests\Feature\Seo;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the limits a search engine actually applies to a page's metadata.
 *
 * Everything here was found by crawling the running site rather than by
 * reading the code, which is why each case names the length that matters.
 */
class MetadataQualityTest extends TestCase
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

    private function metaDescription(string $url): string
    {
        preg_match(
            '~<meta name="description" content="(.*?)"~s',
            $this->get($url)->assertOk()->getContent(),
            $match
        );

        return html_entity_decode($match[1] ?? '', ENT_QUOTES | ENT_HTML5);
    }

    public function test_an_overlong_seo_description_is_clamped_at_a_word_boundary(): void
    {
        // The AI writer trims its own output, but nothing stopped an editor
        // typing 400 characters into this field and shipping all of them.
        $post = $this->publishedPost([
            'seo_description' => str_repeat('This description was typed by hand and is far too long. ', 8),
        ]);

        $description = $this->metaDescription(route('posts.show', $post->slug));

        $this->assertLessThanOrEqual(159, mb_strlen($description));
        $this->assertStringEndsWith('…', $description);
        // Cut at a space, so the snippet never ends mid-word.
        $this->assertStringEndsNotWith(' …', $description);
    }

    public function test_a_description_within_the_limit_is_left_exactly_as_written(): void
    {
        $written = 'A description of a sensible length that says what the article is actually about.';

        $post = $this->publishedPost(['seo_description' => $written]);

        $this->assertSame($written, $this->metaDescription(route('posts.show', $post->slug)));
    }

    public function test_an_archive_gets_a_description_with_enough_substance_to_be_used(): void
    {
        $tag = Tag::factory()->create(['name' => 'UPI', 'slug' => 'upi', 'description' => null, 'posts_count' => 4]);
        $this->publishedPost()->tags()->attach($tag);

        $description = $this->metaDescription(route('tags.show', 'upi'));

        // "Stories tagged UPI." is nineteen characters. Google discards a
        // snippet that thin and writes a worse one from the page body.
        $this->assertGreaterThan(70, mb_strlen($description));
        $this->assertStringContainsString('UPI', $description);
    }

    public function test_a_hand_written_archive_description_is_preferred_over_the_fallback(): void
    {
        $written = 'Everything happening in Indian cricket: results, squads, schedules and the arguments in between.';

        $category = Category::factory()->create(['slug' => 'cricket', 'description' => $written]);
        $this->publishedPost(['category_id' => $category->id]);

        $this->assertSame($written, $this->metaDescription(route('categories.show', 'cricket')));
    }

    public function test_the_static_pages_do_not_share_one_description(): void
    {
        $descriptions = [];

        foreach (['about', 'privacy', 'terms', 'disclaimer'] as $page) {
            $descriptions[] = $this->metaDescription(route('pages.show', $page));
        }

        // These four are the pages an ad network reads before approving a site;
        // four copies of one sentence is the wrong first impression.
        $this->assertCount(4, array_unique($descriptions));

        foreach ($descriptions as $description) {
            $this->assertGreaterThan(70, mb_strlen($description));
        }
    }

    public function test_the_site_name_is_dropped_from_an_already_long_title(): void
    {
        $post = $this->publishedPost([
            'title' => 'A headline long enough that appending the site name would push it past what Google shows',
            'seo_title' => null,
        ]);

        $body = $this->get(route('posts.show', $post->slug))->assertOk()->getContent();
        preg_match('~<title>(.*?)</title>~s', $body, $match);

        $this->assertStringNotContainsString('·', $match[1]);
    }

    public function test_a_short_title_still_carries_the_site_name(): void
    {
        $post = $this->publishedPost(['title' => 'A short headline', 'seo_title' => null]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('<title>A short headline · '.config('app.name').'</title>', false);
    }

    public function test_the_share_image_falls_back_to_the_site_logo(): void
    {
        Setting::where('key', 'site_logo')->update(['value' => 'settings/logo.png']);
        app(SettingsService::class)->flush();

        $this->publishedPost();

        // A link with no picture is visibly smaller in every feed and gets
        // clicked less, so the logo is a better last resort than nothing.
        $this->get(route('latest'))
            ->assertOk()
            ->assertSee('og:image', false)
            ->assertSee('settings/logo.png');
    }

    public function test_a_configured_share_image_wins_over_the_logo(): void
    {
        Setting::where('key', 'site_logo')->update(['value' => 'settings/logo.png']);
        Setting::where('key', 'seo_default_og_image')->update(['value' => 'settings/share-card.png']);
        app(SettingsService::class)->flush();

        // Asserted against the og:image tag rather than the whole page: the
        // layout renders the logo in the header too, so a page-wide check would
        // pass no matter which image won.
        $ogImage = $this->ogImage(route('latest'));

        $this->assertStringContainsString('settings/share-card.png', $ogImage);
        $this->assertStringNotContainsString('settings/logo.png', $ogImage);
    }

    private function ogImage(string $url): string
    {
        preg_match(
            '~<meta property="og:image" content="(.*?)"~s',
            $this->get($url)->assertOk()->getContent(),
            $match
        );

        return $match[1] ?? '';
    }

    public function test_a_posts_own_image_wins_over_both(): void
    {
        Setting::where('key', 'seo_default_og_image')->update(['value' => 'settings/share-card.png']);
        app(SettingsService::class)->flush();

        $post = $this->publishedPost(['featured_image' => 'media/2026/08/the-article-image.webp']);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('the-article-image.webp');
    }

    public function test_whitespace_in_a_description_is_collapsed(): void
    {
        // A newline inside a meta attribute is legal but renders as a gap in
        // the snippet, and hand-pasted text is full of them.
        $post = $this->publishedPost(['seo_description' => "A description\n\nwith   broken\tspacing."]);

        $this->assertSame(
            'A description with broken spacing.',
            $this->metaDescription(route('posts.show', $post->slug))
        );
    }
}
