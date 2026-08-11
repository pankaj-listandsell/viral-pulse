<?php

namespace App\Services\Trending;

use App\Enums\TrendingSource;
use App\Services\Trending\Contracts\TrendingSourceDriver;
use App\Services\Trending\Sources\NewsApiSource;
use App\Services\Trending\Sources\RssSource;

/**
 * Builds the enabled source drivers from config. Keeping this in one place
 * means adding a feed is a config change, not a code change.
 */
class SourceRegistry
{
    public function __construct(private readonly FeedParser $parser) {}

    /**
     * @return array<int, TrendingSourceDriver>
     */
    public function drivers(): array
    {
        $drivers = [];

        foreach ((array) config('trending.sources', []) as $key => $definition) {
            $driver = $this->build((string) $key, (array) $definition);

            if ($driver && $driver->isEnabled()) {
                $drivers[] = $driver;
            }
        }

        foreach ($this->customFeeds() as $driver) {
            if ($driver->isEnabled()) {
                $drivers[] = $driver;
            }
        }

        return $drivers;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function build(string $key, array $definition): ?TrendingSourceDriver
    {
        $enabled = (bool) ($definition['enabled'] ?? false);
        $weight = (int) ($definition['weight'] ?? 10);

        if ($key === 'news_api') {
            return new NewsApiSource(
                endpoint: (string) ($definition['url'] ?? ''),
                key: $definition['key'] ?? null,
                weight: $weight,
                enabled: $enabled,
            );
        }

        return new RssSource(
            parser: $this->parser,
            label: $this->label($key),
            url: $this->expand((string) ($definition['url'] ?? '')),
            source: TrendingSource::tryFrom((string) ($definition['source'] ?? 'rss')) ?? TrendingSource::Rss,
            weight: $weight,
            categorySlug: $definition['category'] ?? null,
            enabled: $enabled,
        );
    }

    /**
     * @return array<int, TrendingSourceDriver>
     */
    private function customFeeds(): array
    {
        $weight = (int) config('trending.custom_feed_weight', 12);
        $drivers = [];

        foreach ((array) config('trending.custom_feeds', []) as $index => $entry) {
            // "https://example.com/feed.xml|technology" pins a feed to a category.
            [$url, $category] = array_pad(explode('|', (string) $entry, 2), 2, null);
            $url = trim((string) $url);

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $drivers[] = new RssSource(
                parser: $this->parser,
                label: 'Custom feed #'.($index + 1),
                url: $url,
                source: TrendingSource::Rss,
                weight: $weight,
                categorySlug: $category ? trim($category) : null,
            );
        }

        return $drivers;
    }

    private function label(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    private function expand(string $url): string
    {
        return str_replace(
            ['{region}', '{language}'],
            [(string) config('trending.region', 'IN'), (string) config('trending.language', 'en')],
            $url
        );
    }
}
