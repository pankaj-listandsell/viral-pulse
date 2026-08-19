<?php

namespace App\Services\Images;

use App\Models\Post;
use App\Services\ActivityLogger;
use App\Services\Images\Contracts\FeaturedImageGenerator;
use Illuminate\Support\Str;

/**
 * Gives a post a featured image when it has none.
 *
 * The generator is resolved from the container, so swapping branded cards for
 * something else - generated photography, stock photos - is a binding change
 * and nothing here moves.
 */
class FeaturedImageService
{
    public function __construct(
        private readonly FeaturedImageGenerator $generator,
        private readonly BrandCardGenerator $cards,
        private readonly ActivityLogger $logger,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('site.media.auto_featured_image', true);
    }

    /**
     * @return bool whether an image was attached
     */
    public function ensure(Post $post, bool $force = false): bool
    {
        if (! $force && (! $this->enabled() || filled($post->featured_image))) {
            return false;
        }

        $media = $this->generator->generate($post);

        if (! $media) {
            // Already logged by the generator. A post without a picture is
            // still a perfectly good post.
            return false;
        }

        $post->forceFill([
            'featured_image' => $media->path,
            // The generator knows what its own picture shows; only fall back
            // to the headline when it did not say. Claiming a stock photo
            // depicts the story would be a lie to anyone using a screen reader.
            'featured_image_alt' => $media->alt_text ?: Str::limit($post->title, 120, ''),
        ])->save();

        $this->shareCard($post, $media->path);

        $this->logger->log('post.image_generated', $post, "Generated a picture for \"{$post->title}\"");

        return true;
    }

    /**
     * The 1200x630 headline card that social networks show.
     *
     * Only needed when the featured image is something else. A stock photo of
     * a trading floor makes a poor share image next to a headline the reader
     * cannot see, and the card was built for exactly that slot - so the post
     * keeps both: the photograph on the page, the card in the feed.
     */
    private function shareCard(Post $post, string $featuredPath): void
    {
        if (filled($post->og_image) || str_contains($featuredPath, '/cards/')) {
            return;
        }

        $card = $this->cards->generate($post);

        if ($card) {
            $post->forceFill(['og_image' => $card->path])->save();
        }
    }
}
