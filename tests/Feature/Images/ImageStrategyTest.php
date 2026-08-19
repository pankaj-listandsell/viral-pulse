<?php

namespace Tests\Feature\Images;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Services\Images\AiIllustrationGenerator;
use App\Services\Images\ChainedImageGenerator;
use App\Services\Images\FeaturedImageService;
use App\Services\Images\StockPhotoGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the rule that decides where a post's picture comes from.
 *
 * The editorial part of that rule matters more than the plumbing: a news
 * report must never be handed an AI picture of an event that did not happen,
 * and it must not be handed a stock photo that could pass for a record of one.
 */
class ImageStrategyTest extends TestCase
{
    use RefreshDatabase;

    private function postIn(string $slug, array $attributes = []): Post
    {
        $category = Category::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);

        return Post::factory()->create(array_merge([
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
            'featured_image' => null,
            'og_image' => null,
        ], $attributes));
    }

    private function chain(): ChainedImageGenerator
    {
        return app(ChainedImageGenerator::class);
    }

    public function test_news_is_never_offered_a_photograph_or_a_drawing(): void
    {
        config(['site.media.strategy' => ['*' => ['card'], 'business' => ['stock', 'card']]]);

        $this->assertSame(['card'], $this->chain()->strategies($this->postIn('news')));
        $this->assertSame(['card'], $this->chain()->strategies($this->postIn('trending')));
    }

    public function test_a_section_can_opt_into_stock_photography(): void
    {
        config(['site.media.strategy' => ['*' => ['card'], 'business' => ['stock', 'card']]]);

        $this->assertSame(['stock', 'card'], $this->chain()->strategies($this->postIn('business')));
    }

    public function test_a_section_with_nothing_factual_to_depict_can_use_an_illustration(): void
    {
        config(['site.media.strategy' => ['*' => ['card'], 'astrology' => ['illustration', 'card']]]);

        $this->assertSame(['illustration', 'card'], $this->chain()->strategies($this->postIn('astrology')));
    }

    public function test_the_locally_drawn_card_is_appended_when_a_chain_forgets_it(): void
    {
        // Every other strategy depends on somebody else's API being up.
        config(['site.media.strategy' => ['*' => ['stock']]]);

        $this->assertSame(['stock', 'card'], $this->chain()->strategies($this->postIn('news')));
    }

    public function test_stock_photography_is_skipped_when_no_key_is_configured(): void
    {
        config(['site.media.stock.key' => null]);
        Http::fake();

        $this->assertNull(app(StockPhotoGenerator::class)->generate($this->postIn('business')));
        Http::assertNothingSent();
    }

    public function test_illustrations_are_skipped_when_no_key_is_configured(): void
    {
        config(['site.media.illustration.key' => null]);
        Http::fake();

        $this->assertNull(app(AiIllustrationGenerator::class)->generate($this->postIn('astrology')));
        Http::assertNothingSent();
    }

    public function test_a_stock_photo_is_stored_with_the_photographers_credit(): void
    {
        config(['site.media.stock.key' => 'test-key']);

        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => [[
                    'width' => 4000,
                    'photographer' => 'Ada Lovelace',
                    'alt' => 'A trading floor',
                    'src' => ['large2x' => 'https://images.pexels.com/photo.jpg'],
                ]],
            ]),
            'images.pexels.com/*' => Http::response($this->jpeg()),
        ]);

        $media = app(StockPhotoGenerator::class)->generate($this->postIn('business'));

        $this->assertNotNull($media);
        $this->assertSame('Photo by Ada Lovelace on Pexels', $media->caption);
        $this->assertStringContainsString('/stock/', $media->path);
    }

    public function test_a_photo_too_small_for_a_wide_card_is_rejected(): void
    {
        config(['site.media.stock.key' => 'test-key', 'site.media.stock.min_width' => 1200]);

        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => [['width' => 400, 'src' => ['large2x' => 'https://images.pexels.com/small.jpg']]],
            ]),
        ]);

        $this->assertNull(app(StockPhotoGenerator::class)->generate($this->postIn('business')));
    }

    public function test_an_illustration_carries_its_disclosure_and_refuses_real_people(): void
    {
        config([
            'site.media.illustration.key' => 'test-key',
            'site.media.illustration.model' => 'imagen-test',
        ]);

        Http::fake([
            '*:predict' => Http::response([
                'predictions' => [['bytesBase64Encoded' => base64_encode($this->png())]],
            ]),
        ]);

        $media = app(AiIllustrationGenerator::class)->generate($this->postIn('astrology'));

        $this->assertNotNull($media);
        $this->assertSame(AiIllustrationGenerator::CREDIT, $media->caption);
        $this->assertStringContainsString('/illustrations/', $media->path);

        Http::assertSent(function (Request $request) {
            $prompt = $request['instances'][0]['prompt'];

            return $request['parameters']['personGeneration'] === 'dont_allow'
                && str_contains($prompt, 'no real or recognisable people')
                && str_contains($prompt, 'No text');
        });
    }

    public function test_a_post_given_a_photograph_still_gets_a_headline_card_to_share(): void
    {
        config([
            'site.media.strategy' => ['*' => ['stock', 'card']],
            'site.media.stock.key' => 'test-key',
        ]);

        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => [[
                    'width' => 4000,
                    'photographer' => 'Ada Lovelace',
                    'alt' => 'A trading floor',
                    'src' => ['large2x' => 'https://images.pexels.com/photo.jpg'],
                ]],
            ]),
            'images.pexels.com/*' => Http::response($this->jpeg()),
        ]);

        $post = $this->postIn('business');

        $this->assertTrue(app(FeaturedImageService::class)->ensure($post));

        $post->refresh();

        // The photograph is what the page shows; the card is what a social
        // network shows, because a headline the reader cannot see makes a poor
        // share image.
        $this->assertStringContainsString('/stock/', $post->featured_image);
        $this->assertStringContainsString('/cards/', (string) $post->og_image);
    }

    public function test_a_post_that_only_got_a_card_is_not_given_a_second_one(): void
    {
        config(['site.media.strategy' => ['*' => ['card']]]);

        $post = $this->postIn('news');

        $this->assertTrue(app(FeaturedImageService::class)->ensure($post));

        $post->refresh();

        $this->assertStringContainsString('/cards/', $post->featured_image);
        $this->assertNull($post->og_image);
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(1600, 900);
        ob_start();
        imagejpeg($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(1600, 900);
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
