<?php

namespace App\Services\Trending\Sources;

use App\Enums\TrendingSource;
use App\Services\Trending\Contracts\TrendingSourceDriver;
use App\Services\Trending\TopicCandidate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * newsapi.org top headlines. Only active when a key is configured.
 */
class NewsApiSource implements TrendingSourceDriver
{
    public function __construct(
        private readonly string $endpoint,
        private readonly ?string $key,
        private readonly int $weight,
        private readonly bool $enabled = true,
    ) {}

    public function name(): string
    {
        return 'News API';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && filled($this->key);
    }

    /**
     * @return array<int, TopicCandidate>
     */
    public function fetch(): array
    {
        $limit = max(1, (int) config('trending.per_source_limit', 25));

        try {
            $response = Http::withHeaders([
                // Header rather than query string: a key in the URL ends up in
                // proxy logs and in any exception that reports the request.
                'X-Api-Key' => (string) $this->key,
                'User-Agent' => (string) config('trending.user_agent'),
            ])
                ->timeout((int) config('trending.timeout', 20))
                ->retry(2, 1000, throw: false)
                ->get($this->endpoint, [
                    'country' => mb_strtolower((string) config('trending.region', 'in')),
                    'pageSize' => min(100, $limit),
                ]);
        } catch (\Throwable $e) {
            Log::warning('News API request failed', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('News API returned an error status', [
                'status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            return [];
        }

        $candidates = [];

        foreach ((array) $response->json('articles', []) as $article) {
            $title = trim((string) ($article['title'] ?? ''));

            if (mb_strlen($title) < 15) {
                continue;
            }

            $candidates[] = new TopicCandidate(
                topic: mb_substr($title, 0, 240),
                source: TrendingSource::NewsApi,
                sourceWeight: $this->weight,
                description: $this->text($article['description'] ?? null),
                sourceUrl: $this->url($article['url'] ?? null),
                publishedAt: $this->date($article['publishedAt'] ?? null),
                raw: ['source' => $article['source']['name'] ?? null],
            );

            if (count($candidates) >= $limit) {
                break;
            }
        }

        return $candidates;
    }

    private function text(mixed $value): ?string
    {
        $text = trim(strip_tags((string) $value));

        return $text === '' ? null : mb_substr($text, 0, 1000);
    }

    private function url(mixed $value): ?string
    {
        $url = (string) $value;

        return filter_var($url, FILTER_VALIDATE_URL) ? mb_substr($url, 0, 2000) : null;
    }

    private function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
