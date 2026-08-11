<?php

namespace App\Services;

use App\Enums\AiGenerationStatus;
use App\Enums\CommentStatus;
use App\Enums\SubscriberStatus;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\Comment;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PostDailyStat;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard figures are read live rather than cached: an admin who just
 * published a post should see the count change immediately.
 */
class DashboardService
{
    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $postsByStatus = Post::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_posts' => (int) $postsByStatus->sum(),
            'published_posts' => (int) ($postsByStatus['published'] ?? 0),
            'draft_posts' => (int) ($postsByStatus['draft'] ?? 0),
            'scheduled_posts' => (int) ($postsByStatus['scheduled'] ?? 0),
            'total_views' => (int) Post::sum('views_count'),
            'users' => User::count(),
            'subscribers' => NewsletterSubscriber::where('status', SubscriberStatus::Subscribed)->count(),
            'ai_posts' => Post::where('ai_generated', true)->count(),
            'pending_comments' => Comment::where('status', CommentStatus::Pending)->count(),
            'ai_generations' => AiGeneration::where('status', AiGenerationStatus::Completed)->count(),
        ];
    }

    /**
     * @return Collection<int, array{date: string, total: int}>
     */
    public function postsPerDay(int $days = 30): Collection
    {
        $rows = Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->subDays($days)->startOfDay())
            ->select(DB::raw('date(published_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->fillMissingDays($rows, $days);
    }

    /**
     * Reads the nightly rollup rather than the raw post_views table, which
     * keeps this query flat no matter how much traffic the site takes.
     *
     * @return Collection<int, array{date: string, total: int}>
     */
    public function viewsPerDay(int $days = 30): Collection
    {
        $rows = PostDailyStat::query()
            ->where('date', '>=', now()->subDays($days)->startOfDay())
            ->select('date', DB::raw('sum(views) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        return $this->fillMissingDays($rows, $days);
    }

    /**
     * @return Collection<int, Category>
     */
    public function topCategories(int $limit = 6): Collection
    {
        return Category::query()
            ->withCount(['posts' => fn ($query) => $query->published()])
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'color']);
    }

    /**
     * @return Collection<int, Post>
     */
    public function topPosts(int $limit = 5): Collection
    {
        return Post::query()
            ->published()
            ->with('category:id,name,color')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'category_id', 'views_count', 'published_at']);
    }

    /**
     * @return Collection<int, Post>
     */
    public function recentPosts(int $limit = 8): Collection
    {
        return Post::query()
            ->with(['author:id,name', 'category:id,name,color'])
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'status', 'author_id', 'category_id', 'updated_at', 'ai_generated']);
    }

    /**
     * Charts need a point for every day, including the quiet ones, otherwise
     * the x-axis silently compresses and misrepresents the trend.
     *
     * @param  Collection<string, mixed>  $rows
     * @return Collection<int, array{date: string, total: int}>
     */
    private function fillMissingDays(Collection $rows, int $days): Collection
    {
        $keyed = $rows->mapWithKeys(fn ($total, $day): array => [
            Carbon::parse($day)->toDateString() => (int) $total,
        ]);

        return collect(range($days - 1, 0))->map(function (int $offset) use ($keyed): array {
            $date = now()->subDays($offset)->toDateString();

            return ['date' => $date, 'total' => $keyed[$date] ?? 0];
        });
    }
}
