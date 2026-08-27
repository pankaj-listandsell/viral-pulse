@extends('layouts.public')

@php
    $s1 = $initialMatch['sign1'];
    $s2 = $initialMatch['sign2'];

    /*
     * Locale-aware URLs. route('horoscope') resolves to the English path in
     * every language, so every internal link here goes through LocaleService
     * instead - otherwise the Hindi page links its whole reader base back into
     * English on the first click.
     */
    $locales = app(\App\Services\LocaleService::class);
    $hubUrl = $locales->horoscopeUrl('hub');
    $compatibilityUrl = $locales->horoscopeUrl('compatibility');
    $order = array_keys($signs);

    // A sign now has its own page, so what used to be an anchor back to the
    // hub is a link to that page. It is a better answer to the click and it
    // puts the internal link where it does some good.
    $signUrl = fn (string $slug): string => $locales->horoscopeUrl('sign', $slug);

    // Always the zodiac-order pairing, which is the one the page canonicalises
    // to. Linking the other ordering would point at a URL that redirects.
    $pairUrl = fn (string $a, string $b): string => $locales->horoscopeUrl('compatibility', null, null, [
        'sign1' => $locales->signSlug(array_search($a, $order, true) <= array_search($b, $order, true) ? $a : $b),
        'sign2' => $locales->signSlug(array_search($a, $order, true) <= array_search($b, $order, true) ? $b : $a),
    ]);

    /** The calculator only needs the fields it renders. */
    $islandSigns = collect($signs)
        ->map(fn (array $sign) => \Illuminate\Support\Arr::only($sign, [
            // The untranslated element comes along because the island matches
            // pairings on it; the *_label fields are what it prints.
            'slug', 'name', 'vedic', 'symbol', 'dates', 'element', 'quality', 'planet', 'color', 'image',
            'element_label', 'quality_label', 'planet_label',
        ]))
        ->all();

    $scoreTone = function (int $score): array {
        return match (true) {
            $score >= 94 => ['text-emerald-600 dark:text-emerald-400', 'bg-emerald-500', 'bg-emerald-50 dark:bg-emerald-500/10'],
            $score >= 90 => ['text-violet-600 dark:text-violet-400', 'bg-violet-500', 'bg-violet-50 dark:bg-violet-500/10'],
            $score >= 85 => ['text-sky-600 dark:text-sky-400', 'bg-sky-500', 'bg-sky-50 dark:bg-sky-500/10'],
            default => ['text-amber-600 dark:text-amber-400', 'bg-amber-500', 'bg-amber-50 dark:bg-amber-500/10'],
        };
    };

    [$pairText, $pairBar, $pairTint] = $scoreTone($initialMatch['score']);

    $meters = [
        ['key' => 'love', 'label' => __('horoscope.ui.compat_love'), 'icon' => '💖', 'bar' => 'bg-rose-500'],
        ['key' => 'friendship', 'label' => __('horoscope.ui.compat_friendship'), 'icon' => '🤝', 'bar' => 'bg-violet-500'],
        ['key' => 'communication', 'label' => __('horoscope.ui.compat_communication'), 'icon' => '💬', 'bar' => 'bg-sky-500'],
        ['key' => 'trust', 'label' => __('horoscope.ui.compat_trust'), 'icon' => '🔐', 'bar' => 'bg-emerald-500'],
        ['key' => 'values', 'label' => __('horoscope.ui.compat_values'), 'icon' => '🧭', 'bar' => 'bg-amber-500'],
    ];
@endphp

@push('head')
    <style>
        .vp-sky {
            background:
                radial-gradient(1.5px 1.5px at 14% 24%, rgba(255,255,255,.85), transparent 60%),
                radial-gradient(1.5px 1.5px at 74% 16%, rgba(255,255,255,.7), transparent 60%),
                radial-gradient(1px 1px at 36% 70%, rgba(255,255,255,.6), transparent 60%),
                radial-gradient(1.5px 1.5px at 64% 78%, rgba(255,255,255,.75), transparent 60%),
                radial-gradient(1px 1px at 86% 54%, rgba(255,255,255,.55), transparent 60%),
                radial-gradient(1px 1px at 24% 86%, rgba(255,255,255,.5), transparent 60%),
                radial-gradient(1.5px 1.5px at 46% 34%, rgba(255,255,255,.6), transparent 60%);
        }
        .vp-twinkle { animation: vp-twinkle 6s ease-in-out infinite; }
        @keyframes vp-twinkle { 0%, 100% { opacity: .35; } 50% { opacity: 1; } }

        .vp-pulse { animation: vp-pulse 3.2s ease-in-out infinite; }
        @keyframes vp-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.06); } }

        @media (prefers-reduced-motion: reduce) {
            .vp-twinkle, .vp-pulse { animation: none; }
        }

        .vp-rail { scrollbar-width: thin; }
    </style>
@endpush

@section('content')

    {{-- ======================= HERO ======================= --}}
    <section class="relative overflow-hidden bg-[#0d0618] text-white">
        <div class="vp-sky vp-twinkle pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-40 left-1/3 size-[32rem] rounded-full bg-rose-600/25 blur-[150px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-48 -right-10 size-[32rem] rounded-full bg-violet-600/25 blur-[150px]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-6xl px-4 pt-6 pb-14 sm:px-6 sm:pt-8">

            <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs font-semibold text-white/50">
                    <a href="{{ route('home') }}" class="transition hover:text-white">{{ __('horoscope.seo.breadcrumb_home') }}</a>
                    <span aria-hidden="true">&rsaquo;</span>
                    <a href="{{ $hubUrl }}" class="transition hover:text-white">{{ __('horoscope.seo.breadcrumb_horoscope') }}</a>
                    <span aria-hidden="true">&rsaquo;</span>
                    @if($isPair)
                        <a href="{{ $compatibilityUrl }}" class="transition hover:text-white">{{ __('horoscope.seo.breadcrumb_compatibility') }}</a>
                        <span aria-hidden="true">&rsaquo;</span>
                        <span class="text-white/90">{{ $s1['name'] }} &amp; {{ $s2['name'] }}</span>
                    @else
                        <span class="text-white/90">{{ __('horoscope.seo.breadcrumb_compatibility') }}</span>
                    @endif
                </nav>

                {{-- The switcher keeps the pair, so a reader comparing two signs
                     lands on the same two signs in the other language rather
                     than back at the calculator's default view. --}}
                <x-language-switcher :links="$switcher" tone="dark" />
            </div>

            <div class="grid items-center gap-10 lg:grid-cols-[1fr_auto]">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-rose-400/25 bg-rose-500/15 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] text-rose-200 backdrop-blur">
                        <span aria-hidden="true">💖</span> {{ __('horoscope.ui.compat_eyebrow') }}
                    </p>

                    @if($isPair)
                        <h1 class="mt-5 text-4xl font-black leading-[1.05] tracking-tight sm:text-5xl">
                            {{ $s1['name'] }} {{ __('horoscope.ui.compat_and') }} {{ $s2['name'] }}
                            <span class="mt-2 block bg-gradient-to-r from-rose-300 via-fuchsia-200 to-violet-200 bg-clip-text text-transparent">
                                {{ __('horoscope.ui.compat_pair_subtitle_format', ['score' => $initialMatch['score']]) }}
                            </span>
                        </h1>
                        <p class="mt-5 max-w-xl text-base leading-relaxed text-white/70">
                            {{ $initialMatch['summary'] }}
                        </p>
                    @else
                        <h1 class="mt-5 text-4xl font-black leading-[1.05] tracking-tight sm:text-5xl">
                            {{ __('horoscope.ui.compat_h1') }}
                            <span class="mt-2 block bg-gradient-to-r from-rose-300 via-fuchsia-200 to-violet-200 bg-clip-text text-transparent">
                                {{ __('horoscope.ui.compat_h1_accent') }}
                            </span>
                        </h1>
                        <p class="mt-5 max-w-xl text-base leading-relaxed text-white/70">
                            {{ __('horoscope.ui.compat_hero_intro') }}
                        </p>
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#calculator"
                           class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-rose-500 to-violet-600 px-6 py-3.5 text-sm font-black shadow-lg shadow-rose-900/40 transition hover:from-rose-400 hover:to-violet-500 active:scale-95">
                            {{ $isPair ? __('horoscope.ui.compat_retry_btn') : __('horoscope.ui.compat_check_btn') }}
                            <span aria-hidden="true">&darr;</span>
                        </a>
                        <a href="{{ $hubUrl }}"
                           class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/10">
                            <span aria-hidden="true">✨</span> {{ __('horoscope.ui.todays_horoscope') }}
                        </a>
                    </div>
                </div>

                {{-- The pair, as a picture --}}
                <div class="flex items-center justify-center gap-4 sm:gap-6">
                    <div class="text-center">
                        <img src="{{ $s1['image'] }}" alt="{{ $s1['name'] }} zodiac sign"
                             class="size-24 rounded-full border-2 object-cover shadow-2xl sm:size-32"
                             style="border-color: {{ $s1['color'] }}80; box-shadow: 0 0 40px -12px {{ $s1['color'] }};"
                             width="128" height="128">
                        <p class="mt-2 text-sm font-black">{{ $s1['name'] }}</p>
                        <p class="text-[11px] text-white/50">{{ $s1['element_label'] }}</p>
                    </div>

                    <div class="vp-pulse flex size-16 shrink-0 items-center justify-center rounded-full border border-rose-400/30 bg-rose-500/20 text-2xl backdrop-blur sm:size-20 sm:text-3xl" aria-hidden="true">
                        💞
                    </div>

                    <div class="text-center">
                        <img src="{{ $s2['image'] }}" alt="{{ $s2['name'] }} zodiac sign"
                             class="size-24 rounded-full border-2 object-cover shadow-2xl sm:size-32"
                             style="border-color: {{ $s2['color'] }}80; box-shadow: 0 0 40px -12px {{ $s2['color'] }};"
                             width="128" height="128">
                        <p class="mt-2 text-sm font-black">{{ $s2['name'] }}</p>
                        <p class="text-[11px] text-white/50">{{ $s2['element_label'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="bg-white dark:bg-gray-950">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-16">

            {{-- ======================= CALCULATOR ======================= --}}
            <section id="calculator" class="scroll-mt-20" aria-labelledby="calculator-heading">
                <h2 id="calculator-heading" class="sr-only">Compatibility calculator</h2>
                <div
                    data-island="ZodiacCompatibilityCalculator"
                    data-island-eager
                    data-props="{{ json_encode([
                        'signs' => $islandSigns,
                        'types' => $types,
                        'initialSign1' => $initialSign1,
                        'initialSign2' => $initialSign2,
                        'baseUrl' => $compatibilityUrl,
                        'labels' => [
                            'yourSign' => __('horoscope.ui.your_sign'),
                            'theirSign' => __('horoscope.ui.their_sign'),
                            'fullBreakdown' => __('horoscope.ui.full_breakdown'),
                            'match' => __('horoscope.ui.compat_match'),
                            'love' => __('horoscope.ui.compat_love'),
                            'friendship' => __('horoscope.ui.compat_friendship'),
                            'communication' => __('horoscope.ui.compat_communication'),
                            'preview' => __('horoscope.ui.compat_preview'),
                            'openPair' => __('horoscope.ui.compat_open_pair'),
                            'shareWhatsApp' => __('horoscope.ui.share_whatsapp'),
                            'copyLink' => __('horoscope.ui.copy_link'),
                            'linkCopied' => __('horoscope.ui.link_copied'),
                        ],
                        'slugs' => config('horoscope.slugs.'.app()->getLocale(), []),
                    ]) }}"
                >
                    <p class="rounded-3xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        {{ __('horoscope.ui.compat_picker_fallback') }}
                    </p>
                </div>
            </section>

            {{-- ======================= THE PAIR, SERVER-RENDERED ======================= --}}
            {{-- The calculator is an island; this section is the page. It carries
                 the same reading in plain HTML so a crawler - and a reader with
                 no JavaScript - gets the full result for this URL. --}}
            <section class="mt-14" aria-labelledby="result-heading">
                <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900/40">

                    <div class="flex flex-wrap items-center justify-between gap-6 border-b border-gray-200 p-6 sm:p-8 dark:border-gray-800">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('horoscope.ui.compat_reading_header') }}</p>
                            <h2 id="result-heading" class="mt-1 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                                {{ $s1['name'] }} <span class="text-gray-400 dark:text-gray-600">&amp;</span> {{ $s2['name'] }}
                            </h2>
                            <p class="mt-1.5 text-sm font-bold {{ $pairText }}">{{ $initialMatch['title'] }}</p>
                        </div>

                        <div class="flex items-center gap-5">
                            <div class="text-right">
                                <div class="text-5xl font-black {{ $pairText }}">{{ $initialMatch['score'] }}<span class="text-2xl">%</span></div>
                                <p class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('horoscope.ui.compat_overall_match') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1.1fr_1fr]">
                        <div>
                            <p class="text-base leading-relaxed text-gray-800 dark:text-gray-200">{{ $initialMatch['summary'] }}</p>
                            <p class="mt-4 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $initialMatch['detail'] }}</p>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ __('horoscope.ui.compat_what_works') }}</h3>
                                    <ul class="mt-2 space-y-1.5">
                                        @foreach($initialMatch['strengths'] as $item)
                                            <li class="flex gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <span class="text-emerald-500" aria-hidden="true">✓</span> {{ $item }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">{{ __('horoscope.ui.compat_what_to_watch') }}</h3>
                                    <ul class="mt-2 space-y-1.5">
                                        @foreach($initialMatch['challenges'] as $item)
                                            <li class="flex gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <span class="text-amber-500" aria-hidden="true">!</span> {{ $item }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <p class="mt-5 rounded-2xl border-l-4 border-violet-500 bg-violet-50/70 py-3.5 pl-4 pr-3 text-sm leading-relaxed text-gray-700 dark:bg-violet-500/10 dark:text-gray-300">
                                <strong class="font-black text-violet-700 dark:text-violet-300">{{ __('horoscope.ui.compat_advice_label') }}:</strong> {{ $initialMatch['advice'] }}
                            </p>
                        </div>

                        <div>
                            <dl class="space-y-3.5">
                                @foreach($meters as $meter)
                                    @php $value = $initialMatch['scores'][$meter['key']]; @endphp
                                    <div>
                                        <dt class="flex items-center justify-between gap-3 text-xs font-black uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                            <span class="flex items-center gap-1.5"><span aria-hidden="true">{{ $meter['icon'] }}</span> {{ $meter['label'] }}</span>
                                            <span class="text-gray-900 dark:text-white">{{ $value }}%</span>
                                        </dt>
                                        <dd class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                            <span class="block h-full rounded-full {{ $meter['bar'] }}" style="width: {{ $value }}%;"></span>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>

                            {{-- Both charts side by side: this is the part that makes
                                 each pair page genuinely about those two signs. --}}
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                @foreach([$s1, $s2] as $profile)
                                    <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                        <div class="flex items-center gap-2.5">
                                            <img src="{{ $profile['image'] }}" alt="{{ $profile['name'] }}"
                                                 class="size-10 rounded-full object-cover" width="40" height="40" loading="lazy">
                                            <div>
                                                <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $profile['name'] }}</h3>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $profile['vedic'] }}</p>
                                            </div>
                                        </div>
                                        <dl class="mt-3 space-y-1 text-[11px]">
                                            <div class="flex justify-between gap-2">
                                                <dt class="text-gray-400">{{ __('horoscope.ui.element') }}</dt>
                                                <dd class="font-bold text-gray-700 dark:text-gray-300">{{ $profile['element_label'] }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <dt class="text-gray-400">{{ __('horoscope.ui.quality') }}</dt>
                                                <dd class="font-bold text-gray-700 dark:text-gray-300">{{ $profile['quality_label'] }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <dt class="text-gray-400">{{ __('horoscope.ui.ruling_planet') }}</dt>
                                                <dd class="font-bold text-gray-700 dark:text-gray-300">{{ $profile['planet_label'] }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-2">
                                                <dt class="text-gray-400">{{ __('horoscope.ui.date_range') }}</dt>
                                                <dd class="font-bold text-gray-700 dark:text-gray-300">{{ $profile['dates'] }}</dd>
                                            </div>
                                        </dl>
                                        {{-- The evergreen profile: what keeps each
                                             pair page about these two signs rather
                                             than about the pairing type. --}}
                                        <p class="mt-2.5 text-[11px] leading-relaxed text-gray-600 dark:text-gray-400">
                                            {{ $profile['about'] }}
                                        </p>
                                        <p class="mt-2 text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                                            {{ $profile['traits'] }}. {{ __('horoscope.ui.best_matches') }}:
                                            @foreach(array_slice($profile['best_matches'], 0, 2) as $bm)
                                                @continue(! isset($signs[$bm]))
                                                <a href="{{ $pairUrl($profile['slug'], $bm) }}"
                                                   class="font-bold text-violet-600 hover:underline dark:text-violet-400">{{ $signs[$bm]['name'] }}</a>@if(! $loop->last), @endif
                                            @endforeach.
                                        </p>
                                        <a href="{{ $signUrl($profile['slug']) }}"
                                           class="mt-2.5 inline-block text-[11px] font-black text-gray-500 underline-offset-4 hover:text-violet-600 hover:underline dark:text-gray-400">
                                            {{ $profile['name'] }} {{ __('horoscope.ui.horoscope_today_suffix') }} &rarr;
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <x-ads.header class="my-14" />

            {{-- ======================= FULL MATRIX ======================= --}}
            <section class="mt-14" aria-labelledby="matrix-heading">
                <h2 id="matrix-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    {{ __('horoscope.ui.compat_chart_heading') }}
                </h2>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ __('horoscope.ui.compat_chart_intro') }}
                </p>

                <div class="vp-rail mt-6 overflow-x-auto rounded-3xl border border-gray-200 dark:border-gray-800">
                    <table class="w-full min-w-[46rem] border-collapse text-center text-xs">
                        <caption class="sr-only">Compatibility score for every combination of two zodiac signs</caption>
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900">
                                <th scope="col" class="sticky left-0 z-10 bg-gray-50 p-2.5 text-left text-[11px] font-black uppercase tracking-wider text-gray-400 dark:bg-gray-900">{{ __('horoscope.ui.sign_label') }}</th>
                                @foreach($signs as $colSign)
                                    <th scope="col" class="p-2 text-[11px] font-black text-gray-600 dark:text-gray-300">
                                        <span class="block text-base" aria-hidden="true">{{ $colSign['symbol'] }}</span>
                                        <span class="sr-only sm:not-sr-only">{{ Str::limit($colSign['name'], 4, '') }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($signs as $rowSlug => $rowSign)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <th scope="row" class="sticky left-0 z-10 bg-white p-2.5 text-left dark:bg-gray-950">
                                        <a href="{{ $signUrl($rowSlug) }}" class="flex items-center gap-2 font-black text-gray-800 hover:text-violet-600 dark:text-gray-200 dark:hover:text-violet-400">
                                            <span class="text-base" aria-hidden="true">{{ $rowSign['symbol'] }}</span>
                                            {{ $rowSign['name'] }}
                                        </a>
                                    </th>
                                    @foreach($signs as $colSlug => $colSign)
                                        @php
                                            $cell = $matrix[$rowSlug][$colSlug];
                                            [$cellText, , $cellTint] = $scoreTone($cell);
                                        @endphp
                                        <td class="p-1">
                                            <a href="{{ $pairUrl($rowSlug, $colSlug) }}"
                                               class="block rounded-lg px-1.5 py-2 font-black transition {{ $cellTint }} {{ $cellText }} hover:ring-2 hover:ring-violet-400"
                                               title="{{ __('horoscope.ui.compat_matrix_title', ['s1' => $rowSign['name'], 's2' => $colSign['name'], 'score' => $cell]) }}">
                                                {{ $cell }}
                                            </a>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <ul class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[11px] font-bold text-gray-500 dark:text-gray-400">
                    @foreach($types as $type)
                        @php [$legendText, , $legendTint] = $scoreTone($type['score']); @endphp
                        <li class="flex items-center gap-1.5">
                            <span class="inline-block size-3 rounded {{ $legendTint }} {{ $legendText }}" aria-hidden="true"></span>
                            {{ $type['score'] }} · {{ $type['title'] }}
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- ======================= HOW IT WORKS ======================= --}}
            <section class="mt-16" aria-labelledby="how-heading">
                <h2 id="how-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    {{ __('horoscope.ui.compat_read_heading') }}
                </h2>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ __('horoscope.ui.compat_read_intro') }}
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($elements as $element)
                        <div class="rounded-3xl border {{ $element['ring'] }} {{ $element['tint'] }} p-5">
                            <span class="text-2xl" aria-hidden="true">{{ $element['icon'] }}</span>
                            <h3 class="mt-2 text-lg font-black {{ $element['accent'] }}">{{ __('horoscope.ui.element_signs', ['element' => $element['name']]) }}</h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-gray-600 dark:text-gray-300">{{ $element['traits'] }}</p>
                            <p class="mt-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $element['pairs'] }}</p>
                            <ul class="mt-3 flex flex-wrap gap-1.5">
                                @foreach($element['signs'] as $memberSlug)
                                    @continue(! isset($signs[$memberSlug]))
                                    <li>
                                        <a href="{{ $pairUrl($memberSlug, $initialSign2) }}"
                                           class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-bold text-gray-700 transition hover:text-violet-600 dark:bg-gray-900/70 dark:text-gray-300 dark:hover:text-violet-400">
                                            <span aria-hidden="true">{{ $signs[$memberSlug]['symbol'] }}</span>
                                            {{ $signs[$memberSlug]['name'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($types as $type)
                        @php [$typeText, , $typeTint] = $scoreTone($type['score']); @endphp
                        <div class="rounded-3xl border border-gray-200 p-5 dark:border-gray-800">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black {{ $typeTint }} {{ $typeText }}">
                                {{ $type['score'] }}% · {{ $type['title'] }}
                            </span>
                            <p class="mt-3 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                {{ strtr($type['detail'], ['{e1}' => 'One element', '{e2}' => 'the other', '{s1}' => 'One sign', '{s2}' => 'the other']) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ======================= POPULAR PAIRS ======================= --}}
            <section class="mt-16" aria-labelledby="popular-heading">
                <h2 id="popular-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    {{ __('horoscope.ui.popular_pairings') }}
                </h2>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([['aries','leo'],['taurus','virgo'],['gemini','libra'],['cancer','scorpio'],['leo','sagittarius'],['virgo','capricorn'],['libra','aquarius'],['scorpio','pisces'],['capricorn','taurus']] as [$a, $b])
                        @continue(! isset($signs[$a], $signs[$b]))
                        @php
                            $pairScore = $matrix[$a][$b];
                            [$pText, , $pTint] = $scoreTone($pairScore);
                        @endphp
                        <a href="{{ $pairUrl($a, $b) }}"
                           class="flex items-center gap-3 rounded-2xl border border-gray-200 p-3.5 transition hover:-translate-y-0.5 hover:border-violet-400 hover:shadow-lg dark:border-gray-800 dark:hover:border-violet-500">
                            <span class="flex -space-x-3">
                                <img src="{{ $signs[$a]['image'] }}" alt="" class="size-10 rounded-full border-2 border-white object-cover dark:border-gray-950" width="40" height="40" loading="lazy">
                                <img src="{{ $signs[$b]['image'] }}" alt="" class="size-10 rounded-full border-2 border-white object-cover dark:border-gray-950" width="40" height="40" loading="lazy">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-black text-gray-900 dark:text-white">
                                    {{ $signs[$a]['name'] }} &amp; {{ $signs[$b]['name'] }}
                                </span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('horoscope.ui.compat_reading_label') }}</span>
                            </span>
                            <span class="rounded-lg px-2 py-1 text-xs font-black {{ $pTint }} {{ $pText }}">{{ $pairScore }}%</span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- ======================= FAQ ======================= --}}
            @if(! empty($faqs))
                <section class="mt-16" aria-labelledby="faq-heading">
                    <h2 id="faq-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                        {{ __('horoscope.ui.compat_faq_heading') }}
                    </h2>
                    <div class="mt-6 divide-y divide-gray-200 overflow-hidden rounded-3xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        @foreach($faqs as $faq)
                            <details class="group bg-white open:bg-gray-50/70 dark:bg-gray-900/40 dark:open:bg-gray-900/70">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-sm font-bold text-gray-900 transition hover:text-rose-600 dark:text-white dark:hover:text-rose-400">
                                    {{ $faq['question'] }}
                                    <span class="shrink-0 text-lg transition group-open:rotate-45" aria-hidden="true">+</span>
                                </summary>
                                <p class="px-5 pb-5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                    {{ $faq['answer'] }}
                                </p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ======================= BACK TO HOROSCOPE ======================= --}}
            <aside class="relative mt-16 overflow-hidden rounded-3xl bg-[#0b0a1e] p-8 text-white sm:p-10">
                <div class="vp-sky vp-twinkle pointer-events-none absolute inset-0" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -left-20 -top-24 size-80 rounded-full bg-violet-600/25 blur-[110px]" aria-hidden="true"></div>

                <div class="relative flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="max-w-xl">
                        <h2 class="text-2xl font-black tracking-tight sm:text-3xl">{{ __('horoscope.ui.compat_footer_heading') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">
                            {{ __('horoscope.ui.compat_footer_intro') }}
                        </p>
                    </div>
                    <a href="{{ $hubUrl }}"
                       class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-7 py-4 text-sm font-black shadow-xl shadow-violet-900/40 transition hover:from-violet-500 hover:to-fuchsia-500 active:scale-95">
                        {{ __('horoscope.ui.compat_footer_button') }}
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </aside>

            <x-newsletter.form class="mx-auto mt-16 max-w-xl" />

            <p class="mx-auto mt-10 max-w-2xl text-center text-xs leading-relaxed text-gray-400 dark:text-gray-500">
                {{ __('horoscope.ui.compat_disclaimer') }}
            </p>
        </div>
    </div>
@endsection
