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
            // Describes what the image actually is. Claiming it depicts the
            // story would be a lie to anyone using a screen reader.
            'featured_image_alt' => Str::limit($post->title, 120, ''),
        ])->save();

        $this->logger->log('post.image_generated', $post, "Generated a card for \"{$post->title}\"");

        return true;
    }
}
