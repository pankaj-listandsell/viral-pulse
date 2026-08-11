<?php

namespace App\Console\Commands;

use App\Enums\ScheduledPostStatus;
use App\Enums\TrendingTopicStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldData extends Command
{
    protected $signature = 'data:cleanup {--dry-run : Report what would be removed without deleting anything}';

    protected $description = 'Prune analytics, logs and spent pipeline rows past their retention window';

    /** Deleted in batches so a large backlog never holds a long table lock. */
    private const BATCH = 1000;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $analyticsDays = max(1, (int) config('site.retention.analytics_days', 90));
        $logDays = max(1, (int) config('site.retention.activity_log_days', 180));

        $targets = [
            // Raw views are already rolled up into post_daily_stats by the
            // nightly aggregate, so the detail is safe to drop.
            'post_views' => DB::table('post_views')->where('viewed_at', '<', now()->subDays($analyticsDays)),

            'activity_logs' => DB::table('activity_logs')->where('created_at', '<', now()->subDays($logDays)),

            'trending_topics' => DB::table('trending_topics')
                ->whereIn('status', [TrendingTopicStatus::Ignored->value, TrendingTopicStatus::Generated->value])
                ->where('created_at', '<', now()->subDays(30)),

            // Failed generations keep their error message for a month, which is
            // long enough to notice a pattern.
            'ai_generations' => DB::table('ai_generations')
                ->where('status', 'failed')
                ->where('created_at', '<', now()->subDays(30)),

            'scheduled_posts' => DB::table('scheduled_posts')
                ->whereIn('status', [ScheduledPostStatus::Published->value, ScheduledPostStatus::Cancelled->value])
                ->where('updated_at', '<', now()->subDays(30)),

            'failed_jobs' => DB::table('failed_jobs')->where('failed_at', '<', now()->subDays(30)),
        ];

        foreach ($targets as $table => $query) {
            $count = (clone $query)->count();

            if ($count === 0) {
                continue;
            }

            if ($dry) {
                $this->line("{$table}: {$count} row(s) would be deleted.");

                continue;
            }

            $deleted = 0;

            do {
                $batch = (clone $query)->limit(self::BATCH)->delete();
                $deleted += $batch;
            } while ($batch > 0);

            $this->info("{$table}: {$deleted} row(s) deleted.");
        }

        return self::SUCCESS;
    }
}
