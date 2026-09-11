<?php

use App\Services\Trending\PublishWindow;
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
// Every minute, not hourly: in immediate mode the command has to notice the
// exact minute one of the configured times arrives. It returns immediately on
// every other minute, and in scheduled mode it only acts on the hour it would
// have run anyway.
Schedule::command('content:generate-trending')
    ->everyMinute()
    ->when(fn () => app(PublishWindow::class)->publishesImmediately()
        ? app(PublishWindow::class)->isSlotTimeNow()
        : now()->minute === 20)
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

// The morning horoscope, tried repeatedly between 05:00 and 10:00 rather than
// pinned to 05:00 exactly.
//
// dailyAt() only fires if the scheduler happens to wake during that one
// minute. Shared hosting throttles cron - on this host a */5 entry was
// observed running twice in 45 minutes - so a task pinned to a single minute
// is quietly skipped for the whole day. Repeating across a window means the
// next time cron does wake, the article gets written; the command's own
// once-a-day guard stops it writing a second one.
Schedule::command('content:generate-daily-horoscope')
    ->everyTenMinutes()
    ->between('05:00', '10:00')
    ->withoutOverlapping(30);

// Morning Horoscope Push Notification to all subscribed users
Schedule::command('push:daily-horoscope')
    ->dailyAt('08:00')
    ->withoutOverlapping(15);

/*
 * The readings on the horoscope pages themselves, in every language.
 *
 * Earlier than the article and across the same kind of window, for the same
 * cron-throttling reason. These have to be in place before the morning's
 * readers arrive: a page with no row for today falls back to its static pool,
 * which is complete but not fresh, and freshness is the whole reason twelve
 * sign pages are worth having.
 *
 * The command's own guard makes repeating harmless - the first run of the day
 * writes, the rest see the rows and stop - and a run that half-failed is
 * finished off by the next tick rather than left until tomorrow.
 */
Schedule::command('content:generate-horoscope-readings --period=daily')
    ->everyTenMinutes()
    ->between('03:30', '09:00')
    ->withoutOverlapping(45);

// Monday's window, so the weekly reading is in place before the week it
// describes has really started.
Schedule::command('content:generate-horoscope-readings --period=weekly')
    ->everyThirtyMinutes()
    ->mondays()
    ->between('03:30', '11:00')
    ->withoutOverlapping(45);

// The 1st. `->monthlyOn(1, ...)` would pin it to one minute again, so this is
// a window too, filtered to the first of the month.
Schedule::command('content:generate-horoscope-readings --period=monthly')
    ->everyThirtyMinutes()
    ->between('03:30', '11:00')
    ->when(fn (): bool => now()->day === 1)
    ->withoutOverlapping(45);

// Keeps the queue bookkeeping tables from growing without bound. Failed jobs
// are kept a month, which is long enough to investigate a pattern.
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('queue:prune-failed --hours=720')->daily();
