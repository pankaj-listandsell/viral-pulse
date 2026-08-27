<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ContentFeedService;
use App\Services\HoroscopeService;
use App\Services\LocaleService;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HoroscopeController extends Controller
{
    public function __construct(
        private readonly HoroscopeService $horoscope,
        private readonly SeoService $seo,
        private readonly ContentFeedService $feed,
        private readonly SettingsService $settings,
        private readonly LocaleService $locales,
    ) {}

    /**
     * The hub: all twelve signs for today.
     */
    public function index(): View
    {
        abort_unless($this->settings->bool('horoscope_enabled', true), 404);

        $today = Carbon::today();
        $signs = $this->horoscope->signs();
        $todayHoroscopes = $this->horoscope->dailyForAll($today);
        $faqs = $this->horoscope->faqs();

        $canonical = $this->locales->horoscopeUrl('hub');

        // Both forms are offered to every string, and each language's file
        // picks the one that reads well in it. English wants "25 Aug 2026" in
        // a title; Hindi's abbreviated month is "अग." with a full stop in the
        // middle of a headline, so the Hindi file uses :longdate instead.
        $dateParts = [
            'date' => $today->translatedFormat('j M Y'),
            'longdate' => $today->translatedFormat('j F Y'),
        ];

        return view('public.horoscope', [
            'signs' => $signs,
            'elements' => $this->horoscope->elements(),
            'todayHoroscopes' => $todayHoroscopes,
            'faqs' => $faqs,
            'today' => $today,
            'trending' => $this->feed->trending(3),
            'switcher' => $this->locales->switcher('hub'),
            'seo' => [
                ...$this->seo->forPage(
                    // The date in the title is what tells a search engine this
                    // page was rewritten today rather than left to go stale.
                    __('horoscope.seo.hub_title', $dateParts),
                    __('horoscope.seo.hub_description', $dateParts),
                    $canonical,
                ),
                'keywords' => __('horoscope.seo.hub_keywords'),
                'alternates' => $this->locales->alternates('hub'),
                'schemas' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'CollectionPage',
                        'name' => __('horoscope.seo.hub_title', $dateParts),
                        'description' => __('horoscope.seo.hub_description', $dateParts),
                        'url' => $canonical,
                        'inLanguage' => $this->locales->hreflang(),
                        'datePublished' => $today->toIso8601String(),
                        'dateModified' => $today->toIso8601String(),
                        'isPartOf' => ['@type' => 'WebSite', 'name' => $this->seo->siteName(), 'url' => url('/')],
                        'publisher' => $this->seo->organizationSchema(),
                        'about' => ['@type' => 'Thing', 'name' => 'Astrology'],
                    ],
                    $this->seo->breadcrumbSchema([
                        ['name' => __('horoscope.seo.breadcrumb_home'), 'url' => route('home')],
                        ['name' => __('horoscope.seo.breadcrumb_horoscope'), 'url' => $canonical],
                    ]),
                    // The twelve readings are a list, and saying so lets a
                    // crawler read the order without parsing the markup. Each
                    // item points at that sign's own page rather than at an
                    // anchor, so the link equity lands on the page that has to
                    // rank for it.
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'ItemList',
                        'name' => __('horoscope.ui.all_signs'),
                        'numberOfItems' => count($signs),
                        'itemListElement' => collect($signs)->values()
                            ->map(fn (array $sign, int $i): array => [
                                '@type' => 'ListItem',
                                'position' => $i + 1,
                                'name' => __('horoscope.seo.sign_heading', ['name' => $sign['name']]),
                                'url' => $this->locales->horoscopeUrl('sign', $sign['slug']),
                            ])->all(),
                    ],
                    $this->seo->faqSchema($faqs),
                ],
            ],
        ]);
    }

    /**
     * One sign's own page: today, this week, this month, plus the evergreen
     * profile and every pairing it has.
     *
     * This is the page built to answer "aries horoscope today" - a query the
     * hub can only ever answer twelfth-best, because the hub is answering
     * eleven other questions on the same URL.
     */
    public function sign(string $sign): View|RedirectResponse
    {
        abort_unless($this->settings->bool('horoscope_enabled', true), 404);

        $locale = $this->locales->current();
        $slug = $this->locales->signFromSlug($sign, $locale);

        abort_if($slug === null, 404);

        // The slug is a sign, but written the way another language writes it -
        // /horoscope/mesh rather than /horoscope/aries. Normalise it inside
        // the language the path already declared rather than switching the
        // reader's language on them, and 301 so only one URL is ever indexed.
        $canonicalSlug = $this->locales->signSlug($slug, $locale);

        if ($canonicalSlug !== $sign) {
            return redirect()->to($this->locales->horoscopeUrl('sign', $slug, $locale), 301);
        }

        $today = Carbon::today();
        $data = $this->horoscope->sign($slug);

        abort_if($data === null, 404);

        $daily = $this->horoscope->daily($slug, $today);
        $weekly = $this->horoscope->weekly($slug, $today);
        $monthly = $this->horoscope->monthly($slug, $today);
        $faqs = $this->horoscope->signFaqs($slug);
        $matches = $this->horoscope->matchesFor($slug);

        $canonical = $this->locales->horoscopeUrl('sign', $slug);
        $hubUrl = $this->locales->horoscopeUrl('hub');

        $replacements = [
            'name' => $data['name'],
            'lowername' => mb_strtolower($data['name']),
            'dates' => $data['dates'],
            'date' => $today->translatedFormat('j M Y'),
            'longdate' => $today->translatedFormat('j F Y'),
        ];

        $title = __('horoscope.seo.sign_title', $replacements);
        $description = __('horoscope.seo.sign_description', $replacements);

        return view('public.horoscope-sign', [
            'sign' => $data,
            'signs' => $this->horoscope->signs(),
            'elements' => $this->horoscope->elements(),
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'matches' => $matches,
            'faqs' => $faqs,
            'today' => $today,
            'hubUrl' => $hubUrl,
            'compatibilityUrl' => $this->locales->horoscopeUrl('compatibility'),
            'trending' => $this->feed->trending(3),
            'switcher' => $this->locales->switcher('sign', $slug),
            'seo' => [
                ...$this->seo->forPage($title, $description, $canonical),
                'image' => url($data['image']),
                'keywords' => __('horoscope.seo.sign_keywords', $replacements),
                'alternates' => $this->locales->alternates('sign', $slug),
                'schemas' => [
                    // Article rather than WebPage: the reading is rewritten
                    // every morning, and Article is the type whose date fields
                    // Google actually uses to judge how fresh a page is.
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Article',
                        'headline' => $title,
                        'description' => $description,
                        'inLanguage' => $this->locales->hreflang(),
                        'datePublished' => $today->toIso8601String(),
                        'dateModified' => $today->toIso8601String(),
                        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
                        'image' => url($data['image']),
                        'author' => $this->seo->organizationSchema(),
                        'publisher' => $this->seo->organizationSchema(),
                        'about' => [
                            '@type' => 'Thing',
                            'name' => $data['name'],
                            'description' => $data['about'],
                        ],
                        'articleSection' => __('horoscope.seo.breadcrumb_horoscope'),
                    ],
                    $this->seo->breadcrumbSchema([
                        ['name' => __('horoscope.seo.breadcrumb_home'), 'url' => route('home')],
                        ['name' => __('horoscope.seo.breadcrumb_horoscope'), 'url' => $hubUrl],
                        ['name' => $data['name'], 'url' => $canonical],
                    ]),
                    $this->seo->faqSchema($faqs),
                ],
            ],
        ]);
    }

    /**
     * The compatibility calculator, and every pair it can be asked for.
     */
    public function compatibility(): View
    {
        abort_unless($this->settings->bool('horoscope_enabled', true), 404);

        $signs = $this->horoscope->signs();
        $locale = $this->locales->current();

        // The query string carries the pair in the current language's slugs,
        // so aries+leo and mesh+singh both resolve rather than one of them
        // silently falling back to the calculator's default view.
        $requested1 = $this->locales->signFromSlug((string) request()->query('sign1'), $locale);
        $requested2 = $this->locales->signFromSlug((string) request()->query('sign2'), $locale);

        // A pair page only exists when both halves were asked for and both are
        // real signs. Anything else falls back to the calculator's default view
        // rather than minting a URL for a typo.
        $isPair = $requested1 !== null && $requested2 !== null;

        $sign1 = $isPair ? $requested1 : 'aries';
        $sign2 = $isPair ? $requested2 : 'leo';

        $match = $this->horoscope->compatibility($sign1, $sign2);
        $faqs = $this->horoscope->compatibilityFaqs();

        $s1Name = $signs[$sign1]['name'];
        $s2Name = $signs[$sign2]['name'];

        $pairReplacements = [
            's1' => $s1Name,
            's2' => $s2Name,
            'lows1' => mb_strtolower($s1Name),
            'lows2' => mb_strtolower($s2Name),
            'score' => $match['score'],
            'love' => $match['scores']['love'],
            'friendship' => $match['scores']['friendship'],
            'communication' => $match['scores']['communication'],
        ];

        $title = $isPair
            ? __('horoscope.seo.compat_pair_title', $pairReplacements)
            : __('horoscope.seo.compat_title');

        $description = $isPair
            ? __('horoscope.seo.compat_pair_description', $pairReplacements)
            : __('horoscope.seo.compat_description');

        // One canonical per pair. Compatibility is symmetric, so Leo + Aries is
        // the same reading as Aries + Leo: both point at the zodiac-order URL
        // rather than being indexed as two pages of identical content. The
        // reader still sees the pair in the order they asked for.
        $order = array_keys($signs);
        $canonicalPair = array_search($sign1, $order, true) <= array_search($sign2, $order, true)
            ? [$sign1, $sign2]
            : [$sign2, $sign1];

        $pairQuery = $isPair
            ? [
                'sign1' => $this->locales->signSlug($canonicalPair[0], $locale),
                'sign2' => $this->locales->signSlug($canonicalPair[1], $locale),
            ]
            : [];

        $canonical = $this->locales->horoscopeUrl('compatibility', null, $locale, $pairQuery);

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
            'horoscopeUrl' => $this->locales->horoscopeUrl('hub'),
            'switcher' => $this->compatibilitySwitcher($isPair ? $canonicalPair : null),
            'seo' => [
                ...$this->seo->forPage($title, $description, $canonical),
                // Fourth argument of forPage() is the robots directive, not an
                // image: the share card belongs in its own key.
                'image' => url('/images/zodiac/zodiac_love_hero.webp'),
                'keywords' => $isPair
                    ? __('horoscope.seo.compat_pair_keywords', $pairReplacements)
                    : __('horoscope.seo.compat_keywords'),
                'alternates' => $this->compatibilityAlternates($isPair ? $canonicalPair : null),
                'schemas' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => __('horoscope.seo.compat_title'),
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'browserRequirements' => 'Requires JavaScript for live results; every pairing is also readable as a plain page.',
                        'description' => __('horoscope.seo.compat_description'),
                        'inLanguage' => $this->locales->hreflang(),
                        'url' => $this->locales->horoscopeUrl('compatibility'),
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
                        'publisher' => $this->seo->organizationSchema(),
                    ],
                    $this->seo->breadcrumbSchema(array_values(array_filter([
                        ['name' => __('horoscope.seo.breadcrumb_home'), 'url' => route('home')],
                        ['name' => __('horoscope.seo.breadcrumb_horoscope'), 'url' => $this->locales->horoscopeUrl('hub')],
                        ['name' => __('horoscope.seo.breadcrumb_compatibility'), 'url' => $this->locales->horoscopeUrl('compatibility')],
                        // Named in canonical order, so the crumb and the URL it
                        // points at describe the same pair.
                        $isPair ? [
                            'name' => $signs[$canonicalPair[0]]['name'].' + '.$signs[$canonicalPair[1]]['name'],
                            'url' => $canonical,
                        ] : null,
                    ]))),
                    $this->seo->faqSchema($faqs),
                ],
            ],
        ]);
    }

    /**
     * hreflang for the compatibility page, with each language naming the pair
     * in its own slugs.
     *
     * @param  array<int, string>|null  $pair
     * @return array<int, array{hreflang: string, href: string}>
     */
    private function compatibilityAlternates(?array $pair): array
    {
        if ($pair === null) {
            return $this->locales->alternates('compatibility');
        }

        // Each language names the same pair in its own slugs, so the Hindi
        // alternate of aries+leo is mesh+singh rather than a URL Hindi does
        // not use. An hreflang pointing at something that is not a translation
        // of the page is worse than none at all.
        $urlFor = fn (string $code): string => $this->locales->horoscopeUrl('compatibility', null, $code, [
            'sign1' => $this->locales->signSlug($pair[0], $code),
            'sign2' => $this->locales->signSlug($pair[1], $code),
        ]);

        $links = [];

        foreach ($this->locales->codes() as $code) {
            $links[] = [
                'hreflang' => $this->locales->hreflang($code),
                'href' => $urlFor($code),
            ];
        }

        $links[] = [
            'hreflang' => 'x-default',
            'href' => $urlFor($this->locales->default()),
        ];

        return $links;
    }

    /**
     * @param  array<int, string>|null  $pair
     * @return array<int, array<string, mixed>>
     */
    private function compatibilitySwitcher(?array $pair): array
    {
        $current = $this->locales->current();

        return collect($this->locales->supported())
            ->map(fn (array $meta, string $code): array => [
                'code' => $code,
                'native' => $meta['native'] ?? $code,
                'name' => $meta['name'] ?? $code,
                'url' => $this->locales->horoscopeUrl('compatibility', null, $code, $pair === null ? [] : [
                    'sign1' => $this->locales->signSlug($pair[0], $code),
                    'sign2' => $this->locales->signSlug($pair[1], $code),
                ]),
                'current' => $code === $current,
            ])
            ->values()
            ->all();
    }
}
