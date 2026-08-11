<?php

namespace App\Console\Commands;

use App\Services\Trending\TrendingContentPlanner;
use Illuminate\Console\Command;

class GenerateTrendingContent extends Command
{
    protected $signature = 'content:generate-trending
                            {--limit= : How many articles to start, overriding the configured per-run count}
                            {--force : Run even when automatic generation is switched off}';

    protected $description = 'Start AI articles for the highest scoring trending topics';

    public function handle(TrendingContentPlanner $planner): int
    {
        // Every run spends money, so it stays off until it is deliberately
        // switched on. --force exists for testing it by hand.
        if (! config('trending.automation.enabled') && ! $this->option('force')) {
            $this->line('Automatic generation is off. Set AUTO_GENERATE_ENABLED=true, or pass --force for a one-off run.');

            return self::SUCCESS;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $result = $planner->run($limit);

        if ($result['queued'] === 0) {
            $this->warn($result['reason'] ?? 'Nothing queued.');

            return self::SUCCESS;
        }

        $this->info("Queued {$result['queued']} article(s).");

        foreach ($result['slots'] as $slot) {
            $this->line("  publishing slot: {$slot}");
        }

        return self::SUCCESS;
    }
}
