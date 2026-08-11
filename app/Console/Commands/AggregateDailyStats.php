<?php

namespace App\Console\Commands;

use App\Models\PostDailyStat;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateDailyStats extends Command
{
    protected $signature = 'stats:aggregate {--date= : The day to roll up (defaults to yesterday and today)}';

    protected $description = 'Roll raw post views and likes into the daily stats table';

    public function handle(): int
    {
        $dates = $this->option('date')
            ? [Carbon::parse((string) $this->option('date'))->startOfDay()]
            // Yesterday is re-run because a day only stops receiving views when
            // it is fully over; today is rolled up so the dashboard is not blank
            // until midnight.
            : [today()->subDay(), today()];

        foreach ($dates as $date) {
            $rows = $this->rollUp($date);
            $this->info("{$date->toDateString()}: {$rows} post(s) aggregated.");
        }

        return self::SUCCESS;
    }

    private function rollUp(Carbon $date): int
    {
        $views = DB::table('post_views')
            ->selectRaw('post_id, COUNT(*) as views, COUNT(DISTINCT ip_hash) as unique_views')
            ->whereBetween('viewed_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->groupBy('post_id')
            ->get()
            ->keyBy('post_id');

        $likes = DB::table('post_likes')
            ->selectRaw('post_id, COUNT(*) as likes')
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->groupBy('post_id')
            ->pluck('likes', 'post_id');

        $postIds = $views->keys()->merge($likes->keys())->unique();

        foreach ($postIds as $postId) {
            PostDailyStat::updateOrCreate(
                ['post_id' => $postId, 'date' => $date->toDateString()],
                [
                    'views' => (int) ($views[$postId]->views ?? 0),
                    'unique_views' => (int) ($views[$postId]->unique_views ?? 0),
                    'likes' => (int) ($likes[$postId] ?? 0),
                ]
            );
        }

        return $postIds->count();
    }
}
