<?php

namespace Tests\Feature\Trending;

use App\Enums\TrendingTopicStatus;
use App\Models\Category;
use App\Models\TrendingTopic;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class TrendingFetchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);

        // Only the feeds a test explicitly fakes may be reached; anything else
        // is a real outbound request and fails loudly.
        Http::preventStrayRequests();

        config([
            'trending.sources.google_trends.enabled' => true,
            'trending.sources.google_news.enabled' => false,
            'trending.sources.news_api.enabled' => false,
            'trending.custom_feeds' => [],
        ]);
    }

    private function trendsFeed(string ...$topics): string
    {
        $items = '';

        foreach ($topics as $topic) {
            $items .= <<<XML
                <item>
                    <title>{$topic}</title>
                    <ht:approx_traffic>50,000+</ht:approx_traffic>
                    <pubDate>{$this->now()}</pubDate>
                    <link>https://example.test/story</link>
                    <ht:news_item>
                        <ht:news_item_title>{$topic}</ht:news_item_title>
                        <ht:news_item_url>https://example.test/story</ht:news_item_url>
                        <ht:news_item_snippet>Background on {$topic} from the feed.</ht:news_item_snippet>
                    </ht:news_item>
                </item>
            XML;
        }

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:ht="https://trends.google.com/trending/rss">
            <channel>{$items}</channel>
        </rss>
        XML;
    }

    private function newsFeed(string ...$titles): string
    {
        $items = '';

        foreach ($titles as $title) {
            $items .= '<item><title>'.$title.'</title><link>https://news.test/a</link><pubDate>'.$this->now().'</pubDate></item>';
        }

        return '<?xml version="1.0"?><rss version="2.0"><channel>'.$items.'</channel></rss>';
    }

    private function now(): string
    {
        return now()->toRfc2822String();
    }

    public function test_topics_are_stored_scored_and_categorised(): void
    {
        Http::fake([
            'trends.google.com/*' => Http::response($this->trendsFeed('India cricket squad announced for the T20 series')),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        $topic = TrendingTopic::sole();

        $this->assertSame('India cricket squad announced for the T20 series', $topic->topic);
        $this->assertSame(TrendingTopicStatus::New, $topic->status);
        $this->assertGreaterThan(0, $topic->trend_score);
        // "cricket" routes to Sports rather than the catch-all category.
        $this->assertSame('sports', $topic->category->slug);
        $this->assertSame('Background on India cricket squad announced for the T20 series from the feed.', $topic->description);
    }

    public function test_the_same_story_from_two_feeds_becomes_one_topic(): void
    {
        config(['trending.sources.google_news.enabled' => true]);

        Http::fake([
            'trends.google.com/*' => Http::response($this->trendsFeed('New metro line opens in the city today')),
            'news.google.com/*' => Http::response($this->newsFeed('New  metro line   opens in the city today - The Daily Times')),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        // Whitespace normalisation, case folding and publisher stripping all
        // have to agree, or this is two rows.
        $this->assertSame(1, TrendingTopic::count());

        $topic = TrendingTopic::sole();
        $this->assertSame(2, $topic->raw_payload['sources']);
    }

    public function test_the_publisher_suffix_is_stripped_but_a_real_subtitle_is_kept(): void
    {
        config([
            'trending.sources.google_trends.enabled' => false,
            'trending.sources.google_news.enabled' => true,
        ]);

        Http::fake([
            'news.google.com/*' => Http::response($this->newsFeed(
                'Budget session begins next week - Business Standard',
                'Monsoon arrives early - what it means for farmers this season',
            )),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        $topics = TrendingTopic::pluck('topic');

        $this->assertContains('Budget session begins next week', $topics);
        $this->assertContains('Monsoon arrives early - what it means for farmers this season', $topics);
    }

    public function test_a_sensitive_topic_is_stored_but_held_back(): void
    {
        Http::fake([
            'trends.google.com/*' => Http::response($this->trendsFeed('Three killed in a road accident on the highway')),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        // Kept rather than dropped, so the admin can see what was filtered -
        // but never picked up by the automatic run.
        $this->assertSame(TrendingTopicStatus::Ignored, TrendingTopic::sole()->status);
    }

    public function test_a_dead_feed_does_not_stop_the_run(): void
    {
        Sleep::fake();

        config(['trending.sources.google_news.enabled' => true]);

        Http::fake([
            'trends.google.com/*' => Http::response('', 503),
            'news.google.com/*' => Http::response($this->newsFeed('Something genuinely newsworthy happened today')),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        $this->assertSame(1, TrendingTopic::count());
    }

    public function test_malformed_xml_is_survivable(): void
    {
        Http::fake([
            'trends.google.com/*' => Http::response('<rss><channel><item><title>unclosed'),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        $this->assertSame(0, TrendingTopic::count());
    }

    public function test_a_refetch_does_not_duplicate_or_downgrade_a_decided_topic(): void
    {
        Http::fake([
            'trends.google.com/*' => Http::response($this->trendsFeed('A topic that keeps appearing in the feed')),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        $topic = TrendingTopic::sole();
        $topic->forceFill(['status' => TrendingTopicStatus::Ignored, 'trend_score' => 5])->save();

        $this->artisan('trending:fetch')->assertSuccessful();

        $this->assertSame(1, TrendingTopic::count());
        // An ignored topic stays ignored and keeps its score: a decision the
        // admin made must survive the next feed pull.
        $this->assertSame(TrendingTopicStatus::Ignored, $topic->refresh()->status);
        $this->assertSame(5, $topic->trend_score);
    }

    public function test_a_very_long_feed_url_is_stored_whole(): void
    {
        config([
            'trending.sources.google_trends.enabled' => false,
            'trending.sources.google_news.enabled' => true,
        ]);

        // Google News encodes the whole article URL into the path, which runs
        // well past 255 characters. Storing it truncated would leave a link
        // that no longer resolves.
        $url = 'https://news.google.com/rss/articles/'.str_repeat('CBMiVUFVX3lxTE1', 40).'?oc=5';

        Http::fake([
            'news.google.com/*' => Http::response(
                '<?xml version="1.0"?><rss version="2.0"><channel><item>'
                .'<title>A headline long enough to be accepted</title>'
                .'<link>'.$url.'</link>'
                .'</item></channel></rss>'
            ),
        ]);

        $this->artisan('trending:fetch')->assertSuccessful();

        $this->assertSame($url, TrendingTopic::sole()->source_url);
    }

    public function test_an_admin_can_add_a_topic_by_hand(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::first();

        $this->actingAs($admin)
            ->post(route('admin.trending.store'), [
                'topic' => 'A topic an editor decided is worth covering',
                'description' => 'Some context for the model.',
                'category_id' => $category->id,
            ])
            ->assertSessionHasNoErrors();

        $topic = TrendingTopic::sole();

        $this->assertSame($category->id, $topic->category_id);
        // Hand-picked topics start above the automation floor.
        $this->assertGreaterThanOrEqual((int) config('trending.automation.min_score'), $topic->trend_score);
    }

    public function test_the_trending_page_renders(): void
    {
        TrendingTopic::factory()->create(['topic' => 'A topic waiting to be written']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.trending.index'))
            ->assertOk()
            ->assertSee('A topic waiting to be written');
    }

    public function test_guests_cannot_reach_the_trending_screen(): void
    {
        $this->get(route('admin.trending.index'))->assertRedirect(route('login'));
    }
}
