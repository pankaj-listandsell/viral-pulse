<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ContentFeedService;
use App\Services\HoroscopeService;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SeoService $seo,
        private readonly HoroscopeService $horoscope,
        private readonly SettingsService $settings,
    ) {}

    public function index(): View
    {
        $heroSlides = $this->feed->heroSlides(5);
        $heroIds = $heroSlides->pluck('id');

        // Over-fetch, then drop whatever is already in the top-stories block.
        // Printing the same headline twice on one screen made the page look
        // thinner than the archive actually is.
        $latest = $this->feed->latest(16)
            ->reject(fn ($post) => $heroIds->contains($post->id))
            ->take(8)
            ->values();

        $trending = $this->feed->trending(10)
            ->reject(fn ($post) => $heroIds->contains($post->id))
            ->take(6)
            ->values();

        $signs = $this->horoscope->signs();
        $todayHoroscopes = [];

        foreach ($signs as $slug => $sign) {
            $todayHoroscopes[$slug] = $this->horoscope->daily($slug);
        }

        // The list a reader sees at the top of the page, in the order they see
        // it, so the schema matches the rendering rather than the query.
        $indexed = $heroSlides->concat($latest);

        return view('public.home', [
            'heroSlides' => $heroSlides,
            'hero' => $heroSlides->first() ?? $this->feed->hero(),
            'latest' => $latest,
            'trending' => $trending,
            'featured' => $this->feed->featured(4),
            'signs' => $signs,
            'todayHoroscopes' => $todayHoroscopes,
            'seo' => [
                ...$this->seo->forPage(
                    null,
                    $this->settings->get('site_description'),
                    route('home'),
                ),
                'schemas' => [
                    $this->seo->websiteSchema(),
                    ['@context' => 'https://schema.org', ...$this->seo->organizationSchema()],
                    $this->seo->itemListSchema($indexed, 'Latest stories'),
                ],
            ],
        ]);
    }
}
