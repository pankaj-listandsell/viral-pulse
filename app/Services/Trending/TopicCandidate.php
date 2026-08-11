<?php

namespace App\Services\Trending;

use App\Enums\TrendingSource;
use Illuminate\Support\Carbon;

/**
 * One topic as it came off a feed, before scoring, deduplication or storage.
 * Every source normalises into this shape so nothing downstream has to know
 * whether it came from an RSS item or a JSON API.
 */
final readonly class TopicCandidate
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $topic,
        public TrendingSource $source,
        public int $sourceWeight,
        public ?string $description = null,
        public ?string $sourceUrl = null,
        public ?Carbon $publishedAt = null,
        public ?int $searchVolume = null,
        public ?string $categorySlug = null,
        public array $raw = [],
    ) {}

    public function detectedAt(): Carbon
    {
        // A feed that omits a date, or backdates one, would otherwise look
        // infinitely stale and never score high enough to be written about.
        if (! $this->publishedAt || $this->publishedAt->isFuture()) {
            return now();
        }

        return $this->publishedAt;
    }
}
