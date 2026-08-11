<?php

namespace App\Services\Trending\Sources;

use App\Enums\TrendingSource;
use App\Services\Trending\Contracts\TrendingSourceDriver;
use App\Services\Trending\FeedParser;
use App\Services\Trending\TopicCandidate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Any RSS, Atom or Google Trends feed. One instance per configured URL.
 */
class RssSource implements TrendingSourceDriver
{
    public function __construct(
        private readonly FeedParser $parser,
        private readonly string $label,
        private readonly string $url,
        private readonly TrendingSource $source,
        private readonly int $weight,
        private readonly ?string $categorySlug = null,
        private readonly bool $enabled = true,
    ) {}

    public function name(): string
    {
        return $this->label;
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->url !== '';
    }

    /**
     * @return array<int, TopicCandidate>
     */
    public function fetch(): array
    {
        $body = $this->request();

        if ($body === null) {
            return [];
        }

        $limit = max(1, (int) config('trending.per_source_limit', 25));
        $candidates = [];

        foreach ($this->parser->parse($body, $this->label) as $item) {
            $topic = $this->normaliseTitle($item['title']);

            if ($topic === null) {
                continue;
            }

            $candidates[] = new TopicCandidate(
                topic: $topic,
                source: $this->source,
                sourceWeight: $this->weight,
                description: $item['description'],
                sourceUrl: $item['link'],
                publishedAt: $item['published_at'],
                searchVolume: $item['volume'],
                categorySlug: $this->categorySlug,
                raw: ['feed' => $this->label, 'url' => $this->url],
            );

            if (count($candidates) >= $limit) {
                break;
            }
        }

        return $candidates;
    }

    private function request(): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => (string) config('trending.user_agent')])
                ->timeout((int) config('trending.timeout', 20))
                ->retry(2, 1000, throw: false)
                ->get($this->url);
        } catch (\Throwable $e) {
            // One dead feed must not stop the run: the other sources are still
            // worth reading.
            Log::warning('Trending feed request failed', [
                'feed' => $this->label,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Trending feed returned an error status', [
                'feed' => $this->label,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->body();
    }

    /**
     * Aggregator feeds append the publisher to every headline
     * ("Metro line opens today - The Times of India"). Left in place it would
     * end up in the article title and in the dedupe hash, so the same story
     * from two publishers would look like two topics.
     */
    private function normaliseTitle(string $title): ?string
    {
        foreach ([' - ', ' | ', ' — '] as $separator) {
            $position = mb_strrpos($title, $separator);

            if ($position === false) {
                continue;
            }

            $head = trim(mb_substr($title, 0, $position));
            $tail = trim(mb_substr($title, $position + mb_strlen($separator)));

            // A publisher name is a handful of capitalised words with no
            // sentence punctuation. A real subtitle ("- what it means for
            // farmers this season") fails the word count, which is the test
            // that actually separates the two.
            $words = preg_split('/\s+/u', $tail) ?: [];

            if (mb_strlen($head) >= 20
                && count($words) <= 4
                && mb_strlen($tail) <= 40
                && ! preg_match('/[.!?,;:]/u', $tail)) {
                $title = $head;
            }
        }

        $title = trim($title, " \t\n\r\0\x0B-|—:");

        if (mb_strlen($title) < 15) {
            return null;
        }

        return mb_substr($title, 0, 240);
    }
}
