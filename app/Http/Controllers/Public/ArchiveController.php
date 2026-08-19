<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use App\Services\ContentFeedService;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SeoService $seo,
        private readonly SettingsService $settings,
    ) {}

    public function latest(Request $request): View
    {
        $posts = $this->feed->paginate(
            $this->feed->base()->orderByDesc('published_at'),
            $this->perPage()
        );

        return view('public.archive', [
            'heading' => 'Latest stories',
            'subheading' => 'Everything we have published, newest first.',
            'posts' => $posts,
            'crumbs' => [['name' => 'Latest', 'url' => route('latest')]],
            'seo' => [
                ...$this->seo->forPage(
                    'Latest stories',
                    'Every article we have published, newest first — trending news, technology, entertainment, sport and explainers, updated throughout the day.',
                    route('latest'),
                    $request->integer('page', 1) > 1 ? 'noindex, follow' : null,
                ),
                'schemas' => [
                    $this->seo->itemListSchema($posts->getCollection(), 'Latest stories'),
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Latest', 'url' => route('latest')],
                    ]),
                ],
            ],
        ]);
    }

    public function trending(Request $request): View
    {
        $posts = $this->feed->paginate(
            $this->feed->base()
                ->orderByDesc('is_trending')
                ->orderByDesc('views_count')
                ->orderByDesc('published_at'),
            $this->perPage()
        );

        return view('public.archive', [
            'heading' => 'Trending now',
            'subheading' => 'The stories people are reading right now.',
            'posts' => $posts,
            'crumbs' => [['name' => 'Trending', 'url' => route('trending')]],
            'seo' => [
                ...$this->seo->forPage(
                    'Trending now',
                    'The stories being read the most right now, ranked by what readers are actually opening rather than by what was published most recently.',
                    route('trending'),
                    $request->integer('page', 1) > 1 ? 'noindex, follow' : null,
                ),
                'schemas' => [
                    $this->seo->itemListSchema($posts->getCollection(), 'Trending now'),
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Trending', 'url' => route('trending')],
                    ]),
                ],
            ],
        ]);
    }

    public function categories(): View
    {
        // A section with nothing in it is a dead link to an empty page, and
        // that page is now noindex - listing it here would contradict it. It
        // returns the moment it has a story.
        //
        // Counted from the posts themselves rather than the denormalised
        // posts_count column, for the reason SitemapService gives: an import
        // that writes posts directly leaves that counter at zero, which would
        // silently hide a section that is in fact full.
        $published = fn ($query) => $query->where('status', \App\Enums\PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        $categories = Category::active()
            ->ordered()
            ->whereHas('posts', $published)
            ->withCount(['posts as posts_count' => $published])
            ->get(['id', 'name', 'slug', 'color', 'icon', 'description']);

        return view('public.categories', [
            'categories' => $categories,
            'seo' => [
                ...$this->seo->forPage(
                    'All categories — every topic we cover',
                    'Every topic covered on the site, from breaking news and technology to entertainment, sport, health and travel. Pick a section to see its latest stories.',
                    route('categories.index'),
                ),
                'schemas' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'CollectionPage',
                        'name' => 'All categories',
                        'url' => route('categories.index'),
                        'inLanguage' => str_replace('_', '-', app()->getLocale()),
                        'isPartOf' => ['@type' => 'WebSite', 'name' => $this->seo->siteName(), 'url' => url('/')],
                        'publisher' => $this->seo->organizationSchema(),
                    ],
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Categories', 'url' => route('categories.index')],
                    ]),
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'ItemList',
                        'name' => 'Categories',
                        'numberOfItems' => $categories->count(),
                        'itemListElement' => $categories->values()
                            ->map(fn (Category $category, int $i): array => [
                                '@type' => 'ListItem',
                                'position' => $i + 1,
                                'name' => $category->name,
                                'url' => route('categories.show', $category),
                            ])->all(),
                    ],
                ],
            ],
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $posts = $this->feed->paginate(
            $this->feed->base()->where('category_id', $category->id)->orderByDesc('published_at'),
            $this->perPage()
        );

        $seo = $this->seo->forCategory($category, $request->integer('page', 1));
        $seo['schemas'][] = $this->seo->itemListSchema($posts->getCollection(), $category->name);
        $seo['feed'] = route('feed.category', $category->slug);
        $seo['robots'] = $this->archiveRobots($posts->total(), $seo['robots'] ?? null);

        return view('public.archive', [
            'heading' => $category->name,
            'subheading' => $category->description,
            'accent' => $category->color,
            'posts' => $posts,
            'crumbs' => [
                ['name' => 'Categories', 'url' => route('categories.index')],
                ['name' => $category->name, 'url' => route('categories.show', $category)],
            ],
            'seo' => $seo,
        ]);
    }

    public function tag(Request $request, Tag $tag): View
    {
        $posts = $this->feed->paginate(
            $this->feed->base()
                ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
                ->orderByDesc('published_at'),
            $this->perPage()
        );

        $seo = $this->seo->forTag($tag, $request->integer('page', 1));
        $seo['schemas'][] = $this->seo->itemListSchema($posts->getCollection(), $tag->name);
        $seo['robots'] = $this->archiveRobots($posts->total(), $seo['robots'] ?? null);

        return view('public.archive', [
            'heading' => "#{$tag->name}",
            'subheading' => $tag->description,
            'posts' => $posts,
            'crumbs' => [['name' => "#{$tag->name}", 'url' => route('tags.show', $tag)]],
            'seo' => $seo,
        ]);
    }

    /**
     * An archive listing nothing has no content to rank and spends crawl
     * budget saying so. `follow` still passes the reader on to the navigation,
     * and the page returns to the index by itself once it has a story.
     */
    private function archiveRobots(int $total, ?string $existing): ?string
    {
        return $total === 0 ? 'noindex, follow' : $existing;
    }

    private function perPage(): int
    {
        return max(3, $this->settings->int('posts_per_page', 12));
    }
}
