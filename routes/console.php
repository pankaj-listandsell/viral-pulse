<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| One cron entry drives all of this:
|
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|
| On Windows, create a Task Scheduler task that runs the same command every
| minute. A queue worker must also be running (`php artisan queue:work`), or
| generation jobs will sit in the queue forever.
|
| withoutOverlapping() everywhere: a run that takes longer than its interval
| must not start a second copy of itself.
|
*/

// Every minute, because a post scheduled for 09:30 should appear at 09:30 and
// not at the top of the next hour.
Schedule::command('posts:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Feeds do not turn over faster than this, and every pull costs bandwidth on
// somebody else's server.
Schedule::command('trending:fetch')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Offset from the fetch so it works on topics that were just ingested rather
// than racing them. Does nothing unless AUTO_GENERATE_ENABLED is true.
Schedule::command('content:generate-trending')
    ->hourlyAt(20)
    ->withoutOverlapping();

Schedule::command('content:reconcile-counters')
    ->hourlyAt(50)
    ->withoutOverlapping();

// After midnight, so "yesterday" is genuinely over.
Schedule::command('stats:aggregate')
    ->dailyAt('00:15')
    ->withoutOverlapping();

Schedule::command('data:cleanup')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Keeps the queue bookkeeping tables from growing without bound. Failed jobs
// are kept a month, which is long enough to investigate a pattern.
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('queue:prune-failed --hours=720')->daily();
