<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The read side of the public site.
 *
 * Lists that every visitor sees the same way are cached for a few minutes;
 * anything tied to a specific request is not. The cache is flushed whenever a
 * post changes publication state.
 */
class ContentFeedService
{
    private const TTL = 300;

    private const TAG_KEYS = [
        'feed.hero',
        'feed.trending',
        'feed.featured',
        'feed.popular',
        'feed.categories',
        'feed.navigation',
    ];

    /**
     * Columns the listings actually need. Post bodies are longText, so
     * selecting them for a 12-item grid would move megabytes for nothing.
     *
     * @var array<int, string>
     */
    private const CARD_COLUMNS = [
        'id', 'author_id', 'category_id', 'title', 'slug', 'excerpt',
        'featured_image', 'featured_image_alt', 'published_at', 'reading_time',
        'views_count', 'comments_count', 'ai_generated', 'is_featured', 'is_trending',
    ];

    public function __construct(private readonly MediaResolver $media) {}

    public function base(): Builder
    {
        return Post::query()
            ->published()
            ->with(['category:id,name,slug,color', 'author:id,name'])
            ->select(self::CARD_COLUMNS);
    }

    /**
     * Resolves every featured image in one query so a grid of cards does not
     * hit the database once per thumbnail.
     *
     * @template T
     *
     * @param  T  $posts
     * @return T
     */
    public function withImages(mixed $posts): mixed
    {
        $this->media->preloadForPosts(is_iterable($posts) ? $posts : array_filter([$posts]));

        return $posts;
    }

    public function hero(): ?Post
    {
        return $this->withImages(
            Cache::remember('feed.hero', self::TTL, fn () => $this->base()
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->first())
        );
    }

    /**
     * @return Collection<int, Post>
     */
    public function latest(int $limit = 9, ?int $excludeId = null): Collection
    {
        return $this->withImages(
            $this->base()
                ->when($excludeId, fn (Builder $query) => $query->whereKeyNot($excludeId))
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * @return Collection<int, Post>
     */
    public function trending(int $limit = 5): Collection
    {
        return $this->withImages(
            Cache::remember("feed.trending.{$limit}", self::TTL, fn () => $this->base()
                ->where(fn (Builder $query) => $query
                    ->where('is_trending', true)
                    ->orWhere('published_at', '>=', now()->subDays(7)))
                ->orderByDesc('is_trending')
                ->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get())
        );
    }

    /**
     * @return Collection<int, Post>
     */
    public function featured(int $limit = 4): Collection
    {
        return $this->withImages(
            Cache::remember("feed.featured.{$limit}", self::TTL, fn () => $this->base()
                ->featured()
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get())
        );
    }

    /**
     * @return Collection<int, Post>
     */
    public function popular(int $limit = 5): Collection
    {
        return $this->withImages(
            Cache::remember("feed.popular.{$limit}", self::TTL, fn () => $this->base()
                ->orderByDesc('views_count')
                ->limit($limit)
                ->get())
        );
    }

    /**
     * Posts in the same category, topped up with recent ones if the category
     * is thin, so the section is never almost-empty.
     *
     * @return Collection<int, Post>
     */
    public function related(Post $post, int $limit = 4): Collection
    {
        $related = $this->base()
            ->where('category_id', $post->category_id)
            ->whereKeyNot($post->id)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        if ($related->count() >= $limit) {
            return $related;
        }

        $filler = $this->base()
            ->whereKeyNot($post->id)
            ->whereNotIn('id', $related->pluck('id'))
            ->orderByDesc('published_at')
            ->limit($limit - $related->count())
            ->get();

        return $this->withImages($related->concat($filler));
    }

    /**
     * @return Collection<int, Category>
     */
    public function popularCategories(int $limit = 8): Collection
    {
        return Cache::remember("feed.categories.{$limit}", self::TTL, fn () => Category::query()
            ->active()
            ->where('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'color', 'icon', 'posts_count']));
    }

    /**
     * The header navigation, cached separately because it appears on every
     * single page.
     *
     * @return Collection<int, Category>
     */
    public function navigation(int $limit = 7): Collection
    {
        return Cache::remember('feed.navigation', self::TTL, fn () => Category::query()
            ->active()
            // The header already carries a Trending link to the most-read page,
            // and the fallback category is called Trending too, so both showed
            // up side by side with the same label pointing at different pages.
            // The category is still reachable from /categories and its own URL.
            ->where('slug', '!=', config('trending.fallback_category', 'trending'))
            ->ordered()
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'color']));
    }

    public function paginate(Builder $query, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = $query->paginate($perPage)->withQueryString();

        $this->withImages($paginator->getCollection());

        return $paginator;
    }

    /**
     * Called whenever a post's publication state changes, so a newly published
     * article shows up immediately rather than after the TTL expires.
     */
    public function flush(): void
    {
        foreach (self::TAG_KEYS as $key) {
            Cache::forget($key);
        }

        // The keyed variants are cheap to enumerate and there are only a few.
        foreach ([4, 5, 6, 8, 9, 10, 12] as $limit) {
            Cache::forget("feed.trending.{$limit}");
            Cache::forget("feed.featured.{$limit}");
            Cache::forget("feed.popular.{$limit}");
            Cache::forget("feed.categories.{$limit}");
        }
    }
}
