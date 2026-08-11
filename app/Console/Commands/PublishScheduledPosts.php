<?php

namespace App\Console\Commands;

use App\Enums\ScheduledPostStatus;
use App\Models\ScheduledPost;
use App\Services\PostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled {--limit=25 : Maximum posts to publish in one run}';

    protected $description = 'Publish posts whose scheduled time has arrived';

    /** After this many failures a row is parked for a human rather than retried forever. */
    private const MAX_ATTEMPTS = 3;

    public function handle(PostService $posts): int
    {
        $due = ScheduledPost::due()
            ->with('post')
            ->orderBy('scheduled_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        $published = 0;
        $failed = 0;

        foreach ($due as $row) {
            // Claim the row first. Two overlapping runs would otherwise publish
            // the same post twice and log it twice.
            $claimed = ScheduledPost::whereKey($row->id)
                ->where('status', ScheduledPostStatus::Pending)
                ->update([
                    'status' => ScheduledPostStatus::Processing,
                    'attempts' => DB::raw('attempts + 1'),
                ]);

            if ($claimed === 0) {
                continue;
            }

            $post = $row->post;

            if (! $post) {
                // The post was deleted after it was scheduled.
                $row->forceFill([
                    'status' => ScheduledPostStatus::Cancelled,
                    'last_error' => 'The post no longer exists.',
                ])->save();

                continue;
            }

            try {
                $posts->publish($post);

                $row->forceFill([
                    'status' => ScheduledPostStatus::Published,
                    'published_at' => now(),
                    'last_error' => null,
                ])->save();

                $published++;
                $this->line("Published: {$post->title}");
            } catch (\Throwable $e) {
                $row->refresh();

                $row->forceFill([
                    'status' => $row->attempts >= self::MAX_ATTEMPTS
                        ? ScheduledPostStatus::Failed
                        : ScheduledPostStatus::Pending,
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                ])->save();

                $failed++;

                Log::error('Scheduled publish failed', [
                    'scheduled_post' => $row->id,
                    'post' => $post->id,
                    'attempt' => $row->attempts,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Published {$published}, failed {$failed}.");

        return self::SUCCESS;
    }
}
