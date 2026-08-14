<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ContentFeedService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SeoService $seo,
    ) {}

    public function __invoke(Request $request): View
    {
        $term = trim($request->string('q')->toString());
        $term = mb_substr($term, 0, 100);

        $results = $term === ''
            ? null
            : $this->feed->paginate($this->feed->base()->search($term)->orderByDesc('published_at'));

        return view('public.search', [
            'term' => $term,
            'results' => $results,
            'popular' => $this->feed->popular(),
            'seo' => $this->seo->forPage(
                $term === '' ? 'Search' : "Search: {$term}",
                'Search every article on the site.',
                route('search'),
                // Search result pages are thin and infinitely variable; keeping
                // them out of the index avoids a crawl-budget sink.
                'noindex, follow',
            ),
        ]);
    }

    public function live(Request $request): \Illuminate\Http\JsonResponse
    {
        $term = trim($request->string('q')->toString());
        $term = mb_substr($term, 0, 100);

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $posts = $this->feed->base()
            ->search($term)
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return response()->json($posts->map(fn ($post) => [
            'id' => $post->id,
            'title' => $post->title,
            'url' => route('posts.show', $post),
            'category' => $post->category?->name,
            'category_color' => $post->category?->color,
            'published_at' => $post->published_at?->format('M j, Y'),
            'reading_time' => $post->reading_time,
        ]));
    }
}
