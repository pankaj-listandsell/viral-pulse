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
            'seo' => [
                ...$this->seo->forPage(
                    'Latest stories',
                    'The newest articles across every topic.',
                    route('latest'),
                    $request->integer('page', 1) > 1 ? 'noindex, follow' : null,
                ),
                'schemas' => [$this->seo->itemListSchema($posts->getCollection(), 'Latest stories')],
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
            'seo' => [
                ...$this->seo->forPage(
                    'Trending now',
                    'The most-read stories on the site right now.',
                    route('trending'),
                    $request->integer('page', 1) > 1 ? 'noindex, follow' : null,
                ),
                'schemas' => [$this->seo->itemListSchema($posts->getCollection(), 'Trending now')],
            ],
        ]);
    }

    public function categories(): View
    {
        return view('public.categories', [
            'categories' => Category::active()->ordered()->get(['id', 'name', 'slug', 'color', 'icon', 'description', 'posts_count']),
            'seo' => $this->seo->forPage(
                'Categories',
                'Browse every topic we cover.',
                route('categories.index'),
            ),
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

        return view('public.archive', [
            'heading' => $category->name,
            'subheading' => $category->description,
            'accent' => $category->color,
            'posts' => $posts,
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

        return view('public.archive', [
            'heading' => "#{$tag->name}",
            'subheading' => $tag->description,
            'posts' => $posts,
            'seo' => $seo,
        ]);
    }

    private function perPage(): int
    {
        return max(3, $this->settings->int('posts_per_page', 12));
    }
}
