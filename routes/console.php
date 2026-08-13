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
| withoutOverlapping(minutes) everywhere: a run that takes longer than its
| interval must not start a second copy of itself. The argument matters - the
| default lock lasts 24 hours, so a run killed by a closed terminal or a reboot
| would stop the task for a whole day rather than the few minutes it needs.
|
*/

// Every minute, because a post scheduled for 09:30 should appear at 09:30 and
// not at the top of the next hour.
Schedule::command('posts:publish-scheduled')
    ->everyMinute()
    // Bounded, not the 24-hour default. If a run is killed - a closed
    // terminal, a reboot mid-task - the lock outlives it, and an unbounded
    // one would silently stop publishing for a whole day.
    ->withoutOverlapping(5)
    ->runInBackground();

// Feeds do not turn over faster than this, and every pull costs bandwidth on
// somebody else's server.
Schedule::command('trending:fetch')
    ->hourly()
    ->withoutOverlapping(30)
    ->runInBackground();

// Offset from the fetch so it works on topics that were just ingested rather
// than racing them. Does nothing unless AUTO_GENERATE_ENABLED is true.
Schedule::command('content:generate-trending')
    ->hourlyAt(20)
    ->withoutOverlapping(50);

Schedule::command('content:reconcile-counters')
    ->hourlyAt(50)
    ->withoutOverlapping(30);

// After midnight, so "yesterday" is genuinely over.
Schedule::command('stats:aggregate')
    ->dailyAt('00:15')
    ->withoutOverlapping(120);

Schedule::command('data:cleanup')
    ->dailyAt('03:00')
    ->withoutOverlapping(120);

// Keeps the queue bookkeeping tables from growing without bound. Failed jobs
// are kept a month, which is long enough to investigate a pattern.
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('queue:prune-failed --hours=720')->daily();
