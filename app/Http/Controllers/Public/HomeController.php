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

        // The hero stories are not filtered out of these lists.
        //
        // They were, to avoid printing a headline twice on one screen. The
        // effect was worse than the repetition: publish a story and it
        // vanished from "Latest stories", because being among the five newest
        // put it in the slider instead. A section labelled latest has to show
        // the latest, and the slider is a separate promotion of the same
        // stories - which is how every newsroom front page works.
        $latest = $this->feed->latest(6);
        $trending = $this->feed->trending(6);

        // Section blocks, the thing that makes a front page read like a
        // newsroom rather than one long list: a few busy categories, each
        // showing its own latest. Two queries for all of them.
        $sections = $this->feed->sections(categories: 4, perCategory: 5);

        $signs = $this->horoscope->signs();
        $todayHoroscopes = [];

        foreach ($signs as $slug => $sign) {
            $todayHoroscopes[$slug] = $this->horoscope->daily($slug);
        }

        // The list a reader sees at the top of the page, in the order they see
        // it, so the schema matches the rendering rather than the query. Unique
        // here even though the page repeats a headline between the slider and
        // the feed: an ItemList naming the same URL at two positions describes
        // a list that does not exist.
        $indexed = $heroSlides
            ->concat($latest)
            ->concat($sections->flatMap(fn (array $section) => $section['posts']))
            ->unique('id')
            ->values();

        return view('public.home', [
            'heroSlides' => $heroSlides,
            'hero' => $heroSlides->first() ?? $this->feed->hero(),
            'latest' => $latest,
            'sections' => $sections,
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
