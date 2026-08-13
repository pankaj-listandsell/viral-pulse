<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\Images\Contracts\FeaturedImageGenerator;
use App\Services\Images\FeaturedImageService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Storage::fake(config('site.media.disk'));
    }

    private function article(array $attributes = []): Post
    {
        return Post::factory()->create(array_merge([
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
            'featured_image' => null,
        ], $attributes));
    }

    public function test_a_post_without_an_image_gets_a_card(): void
    {
        $post = $this->article(['title' => 'What the new metro line changes for daily commuters']);

        $this->assertTrue(app(FeaturedImageService::class)->ensure($post));

        $post->refresh();

        $this->assertNotNull($post->featured_image);
        Storage::disk(config('site.media.disk'))->assertExists($post->featured_image);

        // Recorded as media, so it appears in the library and MediaResolver can
        // find its thumbnail sizes like any other image.
        $this->assertDatabaseHas('media', ['path' => $post->featured_image, 'folder' => 'cards']);
    }

    public function test_the_card_is_the_size_every_social_network_crops_to(): void
    {
        $post = $this->article();

        app(FeaturedImageService::class)->ensure($post);

        $media = Media::firstWhere('path', $post->refresh()->featured_image);

        $this->assertSame(1200, $media->width);
        $this->assertSame(630, $media->height);
    }

    public function test_the_alt_text_describes_the_card_rather_than_claiming_a_photo(): void
    {
        $post = $this->article(['title' => 'A headline about something']);

        app(FeaturedImageService::class)->ensure($post);

        // The card shows the headline. Saying anything else would mislead
        // whoever is listening to it.
        $this->assertSame('A headline about something', $post->refresh()->featured_image_alt);
    }

    public function test_an_existing_image_is_left_alone(): void
    {
        $post = $this->article(['featured_image' => 'media/2026/08/a-real-photograph.webp']);

        $this->assertFalse(app(FeaturedImageService::class)->ensure($post));
        $this->assertSame('media/2026/08/a-real-photograph.webp', $post->refresh()->featured_image);
    }

    public function test_force_replaces_an_existing_image(): void
    {
        $post = $this->article(['featured_image' => 'media/2026/08/a-real-photograph.webp']);

        $this->assertTrue(app(FeaturedImageService::class)->ensure($post, force: true));
        $this->assertNotSame('media/2026/08/a-real-photograph.webp', $post->refresh()->featured_image);
    }

    public function test_the_feature_can_be_switched_off(): void
    {
        config(['site.media.auto_featured_image' => false]);

        $post = $this->article();

        $this->assertFalse(app(FeaturedImageService::class)->ensure($post));
        $this->assertNull($post->refresh()->featured_image);
    }

    public function test_a_generator_failure_never_takes_the_post_with_it(): void
    {
        // A missing font, a broken GD build, a full disk: the article is still
        // an article, it just has no picture.
        $this->swap(FeaturedImageGenerator::class, new class implements FeaturedImageGenerator
        {
            public function generate(Post $post): ?Media
            {
                return null;
            }

            public function name(): string
            {
                return 'Always fails';
            }
        });

        $post = $this->article();

        $this->assertFalse(app(FeaturedImageService::class)->ensure($post));
        $this->assertNull($post->refresh()->featured_image);
    }

    public function test_a_very_long_headline_still_produces_a_card(): void
    {
        $post = $this->article([
            'title' => 'An extraordinarily long headline that runs on and on well past the point '
                .'where any sensible editor would have stopped writing it altogether',
        ]);

        $this->assertTrue(app(FeaturedImageService::class)->ensure($post));
        Storage::disk(config('site.media.disk'))->assertExists($post->refresh()->featured_image);
    }

    public function test_a_post_card_becomes_that_posts_share_image(): void
    {
        $category = Category::factory()->create(['slug' => 'technology']);
        $post = $this->article(['category_id' => $category->id, 'slug' => 'a-post-with-a-card']);

        app(FeaturedImageService::class)->ensure($post);

        // The whole point: every article shares with its own picture instead of
        // the one site-wide default.
        $this->get(route('posts.show', 'a-post-with-a-card'))
            ->assertOk()
            ->assertSee($post->refresh()->featured_image);
    }

    public function test_the_backfill_command_only_touches_posts_without_an_image(): void
    {
        $this->article(['title' => 'Needs a card']);
        $this->article(['title' => 'Already has one', 'featured_image' => 'media/2026/08/existing.webp']);

        $this->artisan('posts:generate-cards --limit=10')->assertSuccessful();

        $this->assertNotNull(Post::where('title', 'Needs a card')->value('featured_image'));
        $this->assertSame(
            'media/2026/08/existing.webp',
            Post::where('title', 'Already has one')->value('featured_image')
        );
    }

    public function test_generating_a_card_is_recorded_in_the_activity_log(): void
    {
        User::factory()->admin()->create();

        app(FeaturedImageService::class)->ensure($this->article());

        $this->assertDatabaseHas('activity_logs', ['action' => 'post.image_generated']);
    }
}
