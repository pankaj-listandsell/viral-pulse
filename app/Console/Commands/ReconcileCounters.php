<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileCounters extends Command
{
    protected $signature = 'content:reconcile-counters';

    protected $description = 'Recalculate denormalised counters from the source tables';

    /**
     * Counters are incremented in hot paths, and an increment lost to a failed
     * request or a crashed worker drifts forever. Recomputing them periodically
     * is cheaper than making every increment transactional.
     */
    public function handle(): int
    {
        $posts = DB::update('
            UPDATE posts p
            SET p.views_count = (SELECT COUNT(*) FROM post_views v WHERE v.post_id = p.id),
                p.likes_count = (SELECT COUNT(*) FROM post_likes l WHERE l.post_id = p.id)
        ');

        $categories = DB::update('
            UPDATE categories c
            SET c.posts_count = (
                SELECT COUNT(*) FROM posts p
                WHERE p.category_id = c.id AND p.status = ? AND p.deleted_at IS NULL
            )
        ', [PostStatus::Published->value]);

        $tags = DB::update('
            UPDATE tags t
            SET t.posts_count = (
                SELECT COUNT(*) FROM post_tag pt
                JOIN posts p ON p.id = pt.post_id
                WHERE pt.tag_id = t.id AND p.status = ? AND p.deleted_at IS NULL
            )
        ', [PostStatus::Published->value]);

        $this->info("Reconciled {$posts} post(s), {$categories} category(ies), {$tags} tag(s).");

        return self::SUCCESS;
    }
}
