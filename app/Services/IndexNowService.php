<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Pings the IndexNow API with the given post or URL list.
     *
     * @param  Post|string|array<int, string>  $target
     */
    public function ping(Post|string|array $target): bool
    {
        $key = config('services.indexnow.key') ?: $this->settings->get('indexnow_api_key');

        $urls = match (true) {
            $target instanceof Post => [route('posts.show', $target->slug)],
            is_string($target) => [$target],
            default => array_values($target),
        };

        if (empty($urls)) {
            return false;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'viralpulse.in';

        $apiKey = $key ?: substr(hash('sha256', (config('app.key') ?: 'viralpulse').$host), 0, 32);

        try {
            $response = Http::timeout(5)
                ->asJson()
                ->post(self::ENDPOINT, [
                    'host' => $host,
                    'key' => $apiKey,
                    'urlList' => $urls,
                ]);

            if ($response->successful()) {
                Log::info('IndexNow: Successfully submitted URLs', ['urls' => $urls, 'status' => $response->status()]);
                return true;
            }

            Log::warning('IndexNow: Response code', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('IndexNow: Ping failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
