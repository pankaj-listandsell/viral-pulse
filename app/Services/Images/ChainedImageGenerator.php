<?php

namespace App\Services\Images;

use App\Models\Media;
use App\Models\Post;
use App\Services\Images\Contracts\FeaturedImageGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Picks how a post gets its picture, based on the section it is in.
 *
 * The order comes from config('site.media.strategy'), which is where the
 * editorial decision lives: which sections may carry a photograph, which may
 * carry a drawing, and which get the branded card and nothing else. This class
 * only walks that list and stops at the first strategy that produces an image.
 *
 * A strategy that is unavailable - no Pexels key, no Gemini key, an API having
 * a bad afternoon - returns null and the next one runs. The card is last in
 * every list because it is drawn locally and cannot fail for those reasons.
 */
class ChainedImageGenerator implements FeaturedImageGenerator
{
    private const GENERATORS = [
        'cloudflare' => CloudflareImageGenerator::class,
        'illustration' => AiIllustrationGenerator::class,
        'stock' => StockPhotoGenerator::class,
        'card' => BrandCardGenerator::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function name(): string
    {
        return 'Chained';
    }

    public function generate(Post $post): ?Media
    {
        $post->loadMissing(['tags', 'category', 'author']);

        foreach ($this->strategies($post) as $strategy) {
            $class = self::GENERATORS[$strategy] ?? null;

            if (! $class) {
                continue;
            }

            try {
                $media = $this->container->make($class)->generate($post);

                if ($media) {
                    return $media;
                }
            } catch (\Throwable $e) {
                Log::warning("Image generator strategy [{$strategy}] failed for post {$post->id}: {$e->getMessage()}");
            }
        }

        // Ultimate safety: under no condition should image generation fail
        try {
            return $this->container->make(BrandCardGenerator::class)->generate($post);
        } catch (\Throwable $e) {
            Log::error("Ultimate BrandCardGenerator fallback failed for post {$post->id}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    public function strategies(Post $post): array
    {
        $map = config('site.media.strategy', []);
        $slug = $post->category?->slug;

        $chain = ($slug && isset($map[$slug])) ? $map[$slug] : ($map['*'] ?? ['card']);

        // However the list is configured, the locally drawn card is the floor:
        // a section whose only strategy is an API would end up with no picture
        // at all the day that API is down.
        return in_array('card', $chain, true) ? $chain : [...$chain, 'card'];
    }
}
