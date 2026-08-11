<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Enums\ScheduledPostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostSlugHistory;
use App\Models\ScheduledPost;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly HtmlSanitizer $sanitizer,
        private readonly ActivityLogger $logger,
        private readonly ContentFeedService $feed,
        private readonly SitemapService $sitemap,
        private readonly FeedService $rss,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $author): Post
    {
        return DB::transaction(function () use ($data, $author): Post {
            $tagIds = $this->resolveTags($data['tags'] ?? []);

            $post = new Post($this->prepare($data));
            $post->author_id = $author->id;
            $post->slug = $this->slugs->unique(Post::class, $data['slug'] ?? $data['title']);
            $this->applyStatus($post, $data);
            $post->save();

            $post->tags()->sync($tagIds);
            $this->syncSchedule($post);
            $this->refreshCounters($post);

            $this->logger->log('post.created', $post, "Created \"{$post->title}\"");

            return $post;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data): Post {
            $originalCategory = $post->category_id;
            $tagIds = $this->resolveTags($data['tags'] ?? []);

            $post->fill($this->prepare($data));

            if (! empty($data['slug']) && $data['slug'] !== $post->getOriginal('slug')) {
                $previous = $post->getOriginal('slug');
                $post->slug = $this->slugs->unique(Post::class, $data['slug'], ignoreId: $post->id);

                if ($post->slug !== $previous) {
                    $this->rememberSlug($post, $previous);
                }
            }

            $this->applyStatus($post, $data);
            $post->save();

            $post->tags()->sync($tagIds);
            $this->syncSchedule($post);
            $this->refreshCounters($post, $originalCategory);

            $this->logger->log('post.updated', $post, "Updated \"{$post->title}\"");

            return $post;
        });
    }

    public function publish(Post $post): Post
    {
        $post->forceFill([
            'status' => PostStatus::Published,
            'published_at' => $post->published_at ?? now(),
            'scheduled_at' => null,
        ])->save();

        $post->scheduledPost()
            ->where('status', ScheduledPostStatus::Pending)
            ->update(['status' => ScheduledPostStatus::Cancelled]);

        $this->refreshCounters($post);
        $this->logger->log('post.published', $post, "Published \"{$post->title}\"");

        return $post;
    }

    public function unpublish(Post $post): Post
    {
        $post->forceFill([
            'status' => PostStatus::Draft,
            'published_at' => null,
        ])->save();

        $this->refreshCounters($post);
        $this->logger->log('post.unpublished', $post, "Moved \"{$post->title}\" back to draft");

        return $post;
    }

    public function schedule(Post $post, Carbon $at): Post
    {
        $post->forceFill([
            'status' => PostStatus::Scheduled,
            'scheduled_at' => $at,
            'published_at' => null,
        ])->save();

        $this->syncSchedule($post);
        $this->refreshCounters($post);
        $this->logger->log('post.scheduled', $post, "Scheduled \"{$post->title}\" for {$at->toDayDateTimeString()}");

        return $post;
    }

    public function archive(Post $post): Post
    {
        $post->forceFill(['status' => PostStatus::Archived])->save();

        $this->refreshCounters($post);
        $this->logger->log('post.archived', $post, "Archived \"{$post->title}\"");

        return $post;
    }

    /**
     * Copies everything except identity and publication state: a duplicate is
     * always an unpublished draft with its own slug and zeroed counters.
     */
    public function duplicate(Post $post): Post
    {
        return DB::transaction(function () use ($post): Post {
            $copy = $post->replicate([
                'slug', 'status', 'published_at', 'scheduled_at',
                'views_count', 'likes_count', 'comments_count',
            ]);

            $copy->title = Str::limit($post->title.' (copy)', 250, '');
            $copy->slug = $this->slugs->unique(Post::class, $copy->title);
            $copy->status = PostStatus::Draft;
            $copy->published_at = null;
            $copy->scheduled_at = null;
            $copy->views_count = 0;
            $copy->likes_count = 0;
            $copy->comments_count = 0;
            $copy->save();

            $copy->tags()->sync($post->tags->pluck('id'));
            $this->refreshCounters($copy);

            $this->logger->log('post.duplicated', $copy, "Duplicated \"{$post->title}\"");

            return $copy;
        });
    }

    public function delete(Post $post): void
    {
        $title = $post->title;
        $categoryId = $post->category_id;

        $post->delete();

        $this->refreshCategoryCount($categoryId);
        $this->logger->log('post.deleted', $post, "Deleted \"{$title}\"");
    }

    /**
     * Sanitise, derive and normalise before anything touches the database.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(array $data): array
    {
        $content = $this->sanitizer->clean($data['content'] ?? '');
        $words = $this->sanitizer->wordCount($content);
        $perMinute = max(1, (int) config('site.content.words_per_minute', 200));

        return [
            'title' => trim($data['title']),
            'category_id' => $data['category_id'],
            'content' => $content,
            'excerpt' => $this->sanitizer->plain($data['excerpt'] ?? null, 500)
                ?: $this->sanitizer->plain($content, 200),
            'featured_image' => $data['featured_image'] ?? null,
            'featured_image_alt' => $data['featured_image_alt'] ?? null,
            'language' => $data['language'] ?? 'en',
            'reading_time' => max(1, (int) ceil($words / $perMinute)),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_trending' => (bool) ($data['is_trending'] ?? false),
            'seo_title' => $this->sanitizer->plain($data['seo_title'] ?? null, 255) ?: null,
            'seo_description' => $this->sanitizer->plain($data['seo_description'] ?? null, 500) ?: null,
            'seo_keywords' => $this->sanitizer->plain($data['seo_keywords'] ?? null, 500) ?: null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_image' => $data['og_image'] ?? null,
        ];
    }

    /**
     * Keeps status, published_at and scheduled_at consistent with each other.
     * Without this a post can end up "published" with no date, or "scheduled"
     * for a moment that has already passed.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyStatus(Post $post, array $data): void
    {
        $status = $data['status'] instanceof PostStatus
            ? $data['status']
            : PostStatus::from($data['status'] ?? PostStatus::Draft->value);

        $scheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;
        $publishedAt = ! empty($data['published_at']) ? Carbon::parse($data['published_at']) : null;

        // A future date with "published" selected is a schedule, not a publish.
        if ($status === PostStatus::Published && $publishedAt?->isFuture()) {
            $status = PostStatus::Scheduled;
            $scheduledAt = $publishedAt;
            $publishedAt = null;
        }

        $post->status = $status;

        $post->published_at = match ($status) {
            PostStatus::Published => $publishedAt ?? $post->published_at ?? now(),
            PostStatus::Draft, PostStatus::Scheduled => null,
            PostStatus::Archived => $publishedAt ?? $post->published_at,
        };

        $post->scheduled_at = $status === PostStatus::Scheduled ? $scheduledAt : null;
    }

    /**
     * Accepts a mix of existing tag ids and new tag names, creating whatever
     * does not exist yet.
     *
     * @param  array<int, int|string>  $tags
     * @return array<int, int>
     */
    private function resolveTags(array $tags): array
    {
        return collect($tags)
            ->filter(fn ($tag) => filled($tag))
            ->map(function ($tag): int {
                if (is_numeric($tag)) {
                    return (int) $tag;
                }

                $name = trim((string) $tag);

                return Tag::firstOrCreate(
                    ['slug' => $this->slugs->normalise($name)],
                    ['name' => Str::limit($name, 60, '')]
                )->id;
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Keeps the old URL working after a rename.
     *
     * Silently ignored if the slug is already recorded - the same slug can be
     * given up twice, and the redirect only needs one row either way.
     */
    private function rememberSlug(Post $post, ?string $slug): void
    {
        if (blank($slug)) {
            return;
        }

        PostSlugHistory::updateOrCreate(['slug' => $slug], ['post_id' => $post->id]);
    }

    /**
     * Mirrors the post's schedule into scheduled_posts, which is what the
     * every-minute publisher command actually reads.
     */
    private function syncSchedule(Post $post): void
    {
        $post->scheduledPost()
            ->where('status', ScheduledPostStatus::Pending)
            ->update(['status' => ScheduledPostStatus::Cancelled]);

        if ($post->status === PostStatus::Scheduled && $post->scheduled_at) {
            ScheduledPost::create([
                'post_id' => $post->id,
                'scheduled_at' => $post->scheduled_at,
                'status' => ScheduledPostStatus::Pending,
            ]);
        }
    }

    /**
     * Counter columns are denormalised, so they are recalculated from the
     * source of truth rather than incremented - a missed increment would
     * otherwise drift forever.
     */
    private function refreshCounters(Post $post, ?int $previousCategoryId = null): void
    {
        // A newly published post must appear on the home page immediately, not
        // after the feed cache happens to expire. The sitemap and RSS matter
        // for the same reason: a stale sitemap sends crawlers to URLs that 404
        // and hides the ones worth having.
        $this->feed->flush();
        $this->sitemap->flush();
        $this->rss->flush();

        $this->refreshCategoryCount($post->category_id);

        if ($previousCategoryId && $previousCategoryId !== $post->category_id) {
            $this->refreshCategoryCount($previousCategoryId);
        }

        Tag::whereIn('id', $post->tags()->pluck('tags.id'))
            ->each(fn (Tag $tag) => $tag->forceFill([
                'posts_count' => $tag->posts()->where('status', PostStatus::Published)->count(),
            ])->save());
    }

    private function refreshCategoryCount(?int $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        Category::whereKey($categoryId)->update([
            'posts_count' => Post::where('category_id', $categoryId)
                ->where('status', PostStatus::Published)
                ->count(),
        ]);
    }
}
