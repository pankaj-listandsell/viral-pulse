<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ContentFeedService;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        $heroSlides = $this->feed->heroSlides(5);

        return view('public.home', [
            'heroSlides' => $heroSlides,
            'hero' => $heroSlides->first() ?? $this->feed->hero(),
            'latest' => $this->feed->latest(9),
            'trending' => $this->feed->trending(8),
            'featured' => $this->feed->featured(4),
            'categories' => $this->feed->popularCategories(),
            'seo' => [
                ...$this->seo->forPage(
                    null,
                    app(SettingsService::class)->get('site_description'),
                    route('home'),
                ),
                'schemas' => [
                    $this->seo->websiteSchema(),
                    ['@context' => 'https://schema.org', ...$this->seo->organizationSchema()],
                ],
            ],
        ]);
    }
}
