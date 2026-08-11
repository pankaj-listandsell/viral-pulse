<?php

namespace App\Console\Commands;

use App\Services\Trending\TrendingTopicService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTrendingTopics extends Command
{
    protected $signature = 'trending:fetch';

    protected $description = 'Pull current topics from every enabled trending source';

    public function handle(TrendingTopicService $topics): int
    {
        $started = microtime(true);

        $candidates = $topics->fetchAll();

        if ($candidates === []) {
            $this->warn('No topics returned. Check the feed URLs and that outbound HTTP is allowed.');

            return self::SUCCESS;
        }

        $result = $topics->ingest($candidates);
        $seconds = round(microtime(true) - $started, 1);

        $this->info(sprintf(
            '%d candidates in %ss: %d new, %d refreshed, %d blocked by the safety list.',
            count($candidates),
            $seconds,
            $result['created'],
            $result['updated'],
            $result['blocked'],
        ));

        Log::info('Trending fetch complete', $result + ['candidates' => count($candidates)]);

        return self::SUCCESS;
    }
}
