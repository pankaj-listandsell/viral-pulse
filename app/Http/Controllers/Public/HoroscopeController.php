<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ContentFeedService;
use App\Services\HoroscopeService;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HoroscopeController extends Controller
{
    public function __construct(
        private readonly HoroscopeService $horoscope,
        private readonly SeoService $seo,
        private readonly ContentFeedService $feed,
        private readonly SettingsService $settings,
    ) {}

    public function index(): View
    {
        abort_unless($this->settings->bool('horoscope_enabled', true), 404);

        $today = Carbon::today();
        $signs = $this->horoscope->signs();
        $todayHoroscopes = [];

        foreach ($signs as $slug => $sign) {
            $todayHoroscopes[$slug] = $this->horoscope->daily($slug, $today);
        }

        $faqs = $this->horoscope->faqs();
        $trending = $this->feed->trending(3);

        return view('public.horoscope', [
            'signs' => $signs,
            'elements' => $this->horoscope->elements(),
            'todayHoroscopes' => $todayHoroscopes,
            'faqs' => $faqs,
            'today' => $today,
            'trending' => $trending,
            'seo' => [
                ...$this->seo->forPage(
                    // The date in the title is what tells a search engine this
                    // page was rewritten today rather than left to go stale.
                    'Daily Horoscope Today ('.$today->format('j M Y').') – Rashifal for All 12 Signs',
                    // Kept short enough that no date in the calendar pushes it
                    // past what Google renders and gets it cut mid-word.
                    "Free daily horoscope for {$today->format('j F Y')}: today's prediction for all 12 zodiac signs, from Aries to Pisces, with lucky number, colour, love and career.",
                    route('horoscope'),
                ),
                'keywords' => 'daily horoscope, horoscope today, aaj ka rashifal, rashifal, zodiac signs, astrology prediction, lucky number today, love compatibility, aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces',
                'schemas' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'CollectionPage',
                        'name' => 'Daily Horoscope & Rashifal for All 12 Zodiac Signs',
                        'description' => "Daily astrological predictions for {$today->format('j F Y')}, covering love, career, money and health for every zodiac sign.",
                        'url' => route('horoscope'),
                        'inLanguage' => str_replace('_', '-', app()->getLocale()),
                        'datePublished' => $today->toIso8601String(),
                        'dateModified' => $today->toIso8601String(),
                        'isPartOf' => ['@type' => 'WebSite', 'name' => $this->seo->siteName(), 'url' => url('/')],
                        'publisher' => $this->seo->organizationSchema(),
                        'about' => ['@type' => 'Thing', 'name' => 'Astrology'],
                    ],
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Horoscope', 'url' => route('horoscope')],
                    ]),
                    // The 12 readings are a list, and saying so lets a crawler
                    // read the order without parsing the markup.
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'ItemList',
                        'name' => "Today's horoscope for all 12 zodiac signs",
                        'numberOfItems' => count($signs),
                        'itemListElement' => collect($signs)->values()
                            ->map(fn (array $sign, int $i): array => [
                                '@type' => 'ListItem',
                                'position' => $i + 1,
                                'name' => "{$sign['name']} Horoscope Today",
                                'url' => route('horoscope')."#{$sign['slug']}",
                            ])->all(),
                    ],
                    $this->seo->faqSchema($faqs),
                ],
            ],
        ]);
    }

    public function compatibility(): View
    {
        abort_unless($this->settings->bool('horoscope_enabled', true), 404);

        $signs = $this->horoscope->signs();

        $requested1 = request()->query('sign1');
        $requested2 = request()->query('sign2');

        // A pair page only exists when both halves were asked for and both are
        // real signs. Anything else falls back to the calculator's default view
        // rather than minting a URL for a typo.
        $isPair = isset($signs[$requested1], $signs[$requested2]);

        $sign1 = $isPair ? $requested1 : 'aries';
        $sign2 = $isPair ? $requested2 : 'leo';

        $match = $this->horoscope->compatibility($sign1, $sign2);
        $faqs = $this->horoscope->compatibilityFaqs();

        $s1Name = $signs[$sign1]['name'];
        $s2Name = $signs[$sign2]['name'];

        $title = $isPair
            ? "{$s1Name} and {$s2Name} Compatibility: {$match['score']}% Love Match"
            : 'Zodiac Love Compatibility Calculator – All 12 Signs';

        $description = $isPair
            ? "How well do {$s1Name} and {$s2Name} match? {$match['score']}% overall — love {$match['scores']['love']}%, friendship {$match['scores']['friendship']}%, communication {$match['scores']['communication']}% — plus the friction to expect."
            : 'Free zodiac compatibility calculator for all 144 sign pairs. Check love, friendship and communication scores for any two signs, plus the advice each needs.';

        // One canonical per pair. Compatibility is symmetric, so Leo + Aries is
        // the same reading as Aries + Leo: both point at the zodiac-order URL
        // rather than being indexed as two pages of identical content. The
        // reader still sees the pair in the order they asked for.
        $order = array_keys($signs);
        $canonicalPair = array_search($sign1, $order, true) <= array_search($sign2, $order, true)
            ? [$sign1, $sign2]
            : [$sign2, $sign1];

        $canonical = $isPair
            ? route('horoscope.compatibility', ['sign1' => $canonicalPair[0], 'sign2' => $canonicalPair[1]])
            : route('horoscope.compatibility');

        return view('public.zodiac-compatibility', [
            'signs' => $signs,
            'elements' => $this->horoscope->elements(),
            'matrix' => $this->horoscope->compatibilityMatrix(),
            'types' => $this->horoscope->compatibilityTypes(),
            'initialSign1' => $sign1,
            'initialSign2' => $sign2,
            'initialMatch' => $match,
            'isPair' => $isPair,
            // Rendered on the page as well as in the schema: Google drops FAQ
            // rich results whose answers a reader cannot actually see.
            'faqs' => $faqs,
            'seo' => [
                ...$this->seo->forPage($title, $description, $canonical),
                // Fourth argument of forPage() is the robots directive, not an
                // image: the share card belongs in its own key.
                'image' => url('/images/zodiac/zodiac_love_hero.webp'),
                'keywords' => $isPair
                    ? strtolower("{$s1Name} and {$s2Name} compatibility, {$s1Name} {$s2Name} love match, zodiac compatibility, rashi match, astrology love calculator")
                    : 'zodiac compatibility, love compatibility calculator, zodiac love match, rashi match, astrology compatibility, star sign compatibility',
                'schemas' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Zodiac Love Compatibility Calculator',
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'browserRequirements' => 'Requires JavaScript for live results; every pairing is also readable as a plain page.',
                        'description' => 'Free astrological love match and friendship compatibility calculator for all 12 zodiac signs.',
                        'url' => route('horoscope.compatibility'),
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
                        'publisher' => $this->seo->organizationSchema(),
                    ],
                    $this->seo->breadcrumbSchema(array_values(array_filter([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Horoscope', 'url' => route('horoscope')],
                        ['name' => 'Love Compatibility', 'url' => route('horoscope.compatibility')],
                        // Named in canonical order, so the crumb and the URL it
                        // points at describe the same pair.
                        $isPair ? [
                            'name' => $signs[$canonicalPair[0]]['name'].' and '.$signs[$canonicalPair[1]]['name'],
                            'url' => $canonical,
                        ] : null,
                    ]))),
                    $this->seo->faqSchema($faqs),
                ],
            ],
        ]);
    }
}
