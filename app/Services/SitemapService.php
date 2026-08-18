<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Builds sitemap XML directly rather than through a view.
 *
 * A Blade template starting with "<?xml" is a short-open-tag hazard, and the
 * escaping rules for XML are not Blade's escaping rules. Generating the string
 * here keeps both explicit.
 */
class SitemapService
{
    /** The spec allows 50 000 URLs per file; 5 000 keeps each response small. */
    public const CHUNK = 5000;

    private const TTL_MINUTES = 60;

    private const CACHE_KEYS = [
        'sitemap.index',
        'sitemap.categories',
        'sitemap.tags',
        'sitemap.pages',
    ];

    public function index(): string
    {
        return Cache::remember('sitemap.index', now()->addMinutes(self::TTL_MINUTES), function (): string {
            $entries = [];

            for ($page = 1; $page <= max(1, $this->postPageCount()); $page++) {
                $entries[] = [
                    'loc' => route('sitemap.posts', ['page' => $page]),
                    'lastmod' => $this->latestPostUpdate(),
                ];
            }

            $entries[] = ['loc' => route('sitemap.categories'), 'lastmod' => null];
            $entries[] = ['loc' => route('sitemap.tags'), 'lastmod' => null];
            $entries[] = ['loc' => route('sitemap.pages'), 'lastmod' => null];

            return $this->render($entries, 'sitemapindex', 'sitemap');
        });
    }

    public function posts(int $page): string
    {
        return Cache::remember("sitemap.posts.{$page}", now()->addMinutes(self::TTL_MINUTES), function () use ($page): string {
            $entries = $this->publishedPosts()
                ->orderBy('id')
                ->forPage($page, self::CHUNK)
                ->get(['slug', 'updated_at', 'published_at'])
                ->map(fn (Post $post) => [
                    'loc' => route('posts.show', $post->slug),
                    'lastmod' => $post->updated_at ?? $post->published_at,
                ])
                ->all();

            return $this->render($entries);
        });
    }

    public function categories(): string
    {
        return Cache::remember('sitemap.categories', now()->addMinutes(self::TTL_MINUTES), function (): string {
            $entries = Category::query()
                ->where('is_active', true)
                // A category with nothing in it is a soft 404 to a crawler.
                //
                // Checked against the posts themselves rather than the cached
                // posts_count column. That counter is denormalised, and a
                // seeder or an import that writes posts directly leaves it at
                // zero - which silently drops a real category out of the
                // sitemap, where nobody would notice for months.
                ->whereHas('posts', fn ($query) => $query->where('status', PostStatus::Published)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()))
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->map(fn (Category $category) => [
                    'loc' => route('categories.show', $category->slug),
                    'lastmod' => $category->updated_at,
                ])
                ->all();

            array_unshift($entries, ['loc' => route('categories.index'), 'lastmod' => null]);

            return $this->render($entries);
        });
    }

    public function tags(): string
    {
        return Cache::remember('sitemap.tags', now()->addMinutes(self::TTL_MINUTES), function (): string {
            $entries = Tag::query()
                ->whereHas('posts', fn ($query) => $query->where('status', PostStatus::Published)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()))
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->get(['slug', 'updated_at'])
                ->map(fn (Tag $tag) => [
                    'loc' => route('tags.show', $tag->slug),
                    'lastmod' => $tag->updated_at,
                ])
                ->all();

            return $this->render($entries);
        });
    }

    public function pages(): string
    {
        return Cache::remember('sitemap.pages', now()->addMinutes(self::TTL_MINUTES), function (): string {
            $entries = [
                ['loc' => route('home'), 'lastmod' => $this->latestPostUpdate()],
                ['loc' => route('latest'), 'lastmod' => null],
                ['loc' => route('trending'), 'lastmod' => null],
                ['loc' => route('horoscope'), 'lastmod' => now()],
                ['loc' => route('horoscope.compatibility'), 'lastmod' => now()],
                ['loc' => route('contact'), 'lastmod' => null],
                ['loc' => route('sitemap.page'), 'lastmod' => null],
            ];

            foreach (['about', 'privacy', 'terms', 'disclaimer'] as $page) {
                $entries[] = ['loc' => route('pages.show', $page), 'lastmod' => null];
            }

            return $this->render($entries);
        });
    }

    public function postPageCount(): int
    {
        return (int) ceil($this->publishedPosts()->count() / self::CHUNK);
    }

    /**
     * Called whenever a post's publication state changes. A sitemap that still
     * lists yesterday's set is worse than no sitemap: it sends crawlers to URLs
     * that 404 and hides the ones that matter.
     */
    public function flush(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }

        // One extra page beyond the current count, so shrinking the archive
        // still clears the page that just disappeared.
        for ($page = 1; $page <= $this->postPageCount() + 1; $page++) {
            Cache::forget("sitemap.posts.{$page}");
        }
    }

    /**
     * @return Builder<Post>
     */
    private function publishedPosts(): Builder
    {
        return Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    private function latestPostUpdate(): ?Carbon
    {
        $value = $this->publishedPosts()->max('updated_at');

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * @param  array<int, array{loc: string, lastmod: Carbon|string|null}>  $entries
     */
    private function render(array $entries, string $root = 'urlset', string $item = 'url'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<'.$root.' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= '  <'.$item.'>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'."\n";

            if (! empty($entry['lastmod'])) {
                $lastmod = $entry['lastmod'] instanceof Carbon
                    ? $entry['lastmod']
                    : Carbon::parse($entry['lastmod']);

                $xml .= '    <lastmod>'.$lastmod->toAtomString().'</lastmod>'."\n";
            }

            $xml .= '  </'.$item.'>'."\n";
        }

        return $xml.'</'.$root.'>'."\n";
    }
}
