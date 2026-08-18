@extends('layouts.public')

@php
    /**
     * The ring is laid out here rather than in CSS so the twelve signs are
     * plain anchors in the HTML: a crawler reads twelve internal links to the
     * twelve readings below, and a reader with no JavaScript still gets the
     * whole page.
     */
    $ring = [];
    $i = 0;
    foreach ($signs as $slug => $sign) {
        $angle = deg2rad(-90 + ($i * 30));
        $ring[] = [
            'sign' => $sign,
            'slug' => $slug,
            'x' => round(50 + (41 * cos($angle)), 3),
            'y' => round(50 + (41 * sin($angle)), 3),
        ];
        $i++;
    }

    /**
     * The picker gets only the fields it actually renders. Everything else -
     * the evergreen personality copy, the strengths, the gemstone - is already
     * in the HTML below, and shipping it twice would double the page for no
     * reader benefit.
     */
    $islandSigns = collect($signs)
        ->map(fn (array $sign) => \Illuminate\Support\Arr::only($sign, [
            'slug', 'name', 'vedic', 'symbol', 'dates', 'range',
            'element', 'quality', 'planet', 'color', 'image', 'best_matches',
        ]))
        ->all();

    $islandReadings = collect($todayHoroscopes)
        ->map(fn (array $reading) => \Illuminate\Support\Arr::only($reading, [
            'date', 'overview', 'love', 'career', 'health', 'money',
            'lucky_number', 'lucky_color', 'lucky_time', 'mood', 'score', 'scores',
        ]))
        ->all();

    $elementBadge = [
        'Fire' => 'bg-orange-500/15 text-orange-300 border-orange-400/30',
        'Earth' => 'bg-emerald-500/15 text-emerald-300 border-emerald-400/30',
        'Air' => 'bg-sky-500/15 text-sky-300 border-sky-400/30',
        'Water' => 'bg-indigo-500/15 text-indigo-300 border-indigo-400/30',
    ];
@endphp

@push('head')
    <style>
        /* One small stylesheet instead of a dozen arbitrary Tailwind values:
           the starfield and the slow orbit are the two things this page owns. */
        .vp-sky {
            background:
                radial-gradient(1.5px 1.5px at 12% 22%, rgba(255,255,255,.85), transparent 60%),
                radial-gradient(1.5px 1.5px at 78% 14%, rgba(255,255,255,.7), transparent 60%),
                radial-gradient(1px 1px at 33% 68%, rgba(255,255,255,.6), transparent 60%),
                radial-gradient(1.5px 1.5px at 62% 76%, rgba(255,255,255,.75), transparent 60%),
                radial-gradient(1px 1px at 88% 52%, rgba(255,255,255,.55), transparent 60%),
                radial-gradient(1px 1px at 22% 88%, rgba(255,255,255,.5), transparent 60%),
                radial-gradient(1.5px 1.5px at 48% 36%, rgba(255,255,255,.6), transparent 60%),
                radial-gradient(1px 1px at 92% 84%, rgba(255,255,255,.45), transparent 60%);
            background-size: 100% 100%;
        }
        .vp-twinkle { animation: vp-twinkle 6s ease-in-out infinite; }
        @keyframes vp-twinkle { 0%, 100% { opacity: .35; } 50% { opacity: 1; } }

        .vp-orbit { animation: vp-spin 90s linear infinite; }
        .vp-orbit-slow { animation: vp-spin 140s linear infinite reverse; }
        @keyframes vp-spin { to { transform: rotate(360deg); } }

        @media (prefers-reduced-motion: reduce) {
            .vp-twinkle, .vp-orbit, .vp-orbit-slow { animation: none; }
        }

        /* Hides the scrollbar on the sign rail without hiding the scrolling. */
        .vp-rail { scrollbar-width: none; }
        .vp-rail::-webkit-scrollbar { display: none; }
    </style>
@endpush

@section('content')

    {{-- ======================= HERO: NIGHT SKY ======================= --}}
    <section class="relative overflow-hidden bg-[#07061a] text-white">
        <div class="vp-sky vp-twinkle pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-48 left-1/4 size-[36rem] rounded-full bg-violet-600/25 blur-[150px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-56 right-0 size-[34rem] rounded-full bg-fuchsia-600/20 blur-[150px]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-6xl px-4 pt-6 pb-14 sm:px-6 sm:pt-8 sm:pb-18">

            {{-- Breadcrumb --}}
            <nav aria-label="Breadcrumb" class="mb-8 flex items-center gap-2 text-xs font-semibold text-white/50">
                <a href="{{ route('home') }}" class="transition hover:text-white">Home</a>
                <span aria-hidden="true">&rsaquo;</span>
                <span class="text-white/90">Horoscope</span>
            </nav>

            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_1fr]">

                {{-- Headline column --}}
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] text-violet-200 backdrop-blur">
                        <span class="relative flex size-1.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-1.5 rounded-full bg-emerald-400"></span>
                        </span>
                        Updated <time datetime="{{ $today->toDateString() }}">{{ $today->format('l, j F Y') }}</time>
                    </p>

                    <h1 class="mt-5 text-4xl font-black leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                        Daily Horoscope & Rashifal
                        <span class="mt-2 block bg-gradient-to-r from-violet-300 via-fuchsia-200 to-amber-200 bg-clip-text text-transparent">
                            for all 12 zodiac signs
                        </span>
                    </h1>

                    <p class="mt-5 max-w-xl text-base leading-relaxed text-white/70">
                        Today's reading for every sign — from <strong class="font-semibold text-white">Aries</strong> to
                        <strong class="font-semibold text-white">Pisces</strong> — with the mood of the day, your lucky
                        number and colour, and what the stars say about love, career, money and health. Free, and rewritten
                        every midnight.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#pick-your-sign"
                           class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-3.5 text-sm font-black shadow-lg shadow-violet-900/50 transition hover:from-violet-500 hover:to-fuchsia-500 active:scale-95">
                            Read my sign
                            <span aria-hidden="true">&darr;</span>
                        </a>
                        <a href="{{ route('horoscope.compatibility') }}"
                           class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/10">
                            <span aria-hidden="true">💖</span> Love match calculator
                        </a>
                    </div>

                    <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-white/10 pt-6 text-center">
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-white/45">Signs</dt>
                            <dd class="mt-1 text-2xl font-black">12</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-white/45">Refreshed</dt>
                            <dd class="mt-1 text-2xl font-black">Daily</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-white/45">Cost</dt>
                            <dd class="mt-1 text-2xl font-black">Free</dd>
                        </div>
                    </dl>
                </div>

                {{-- Zodiac wheel: twelve real links, arranged in a circle --}}
                <div class="relative mx-auto hidden aspect-square w-full max-w-[30rem] md:block">
                    <div class="vp-orbit absolute inset-[6%] rounded-full border border-dashed border-white/15" aria-hidden="true"></div>
                    <div class="vp-orbit-slow absolute inset-[22%] rounded-full border border-white/10" aria-hidden="true"></div>
                    <div class="absolute inset-[30%] rounded-full bg-gradient-to-br from-violet-600/30 to-fuchsia-600/20 blur-2xl" aria-hidden="true"></div>

                    {{-- Wheel hub --}}
                    <div class="absolute left-1/2 top-1/2 flex size-32 -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center rounded-full border border-white/15 bg-white/5 text-center backdrop-blur-xl">
                        <span class="text-2xl" aria-hidden="true">🔮</span>
                        <span class="mt-1 text-[10px] font-black uppercase tracking-[0.2em] text-white/50">Today</span>
                        <span class="text-sm font-black">{{ $today->format('j M') }}</span>
                    </div>

                    @foreach($ring as $node)
                        <a href="#{{ $node['slug'] }}"
                           style="left: {{ $node['x'] }}%; top: {{ $node['y'] }}%;"
                           class="group absolute flex -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-1"
                           title="{{ $node['sign']['name'] }} horoscope today">
                            <span class="size-14 overflow-hidden rounded-full border border-white/20 bg-[#0d0b24] p-0.5 shadow-lg transition duration-300 group-hover:scale-110 lg:size-16"
                                  style="box-shadow: 0 0 22px -6px {{ $node['sign']['color'] }};">
                                <img src="{{ $node['sign']['image'] }}" alt="{{ $node['sign']['name'] }} zodiac sign"
                                     class="size-full rounded-full object-cover" width="64" height="64" loading="lazy" decoding="async">
                            </span>
                            <span class="text-[10px] font-bold text-white/60 transition group-hover:text-white">{{ $node['sign']['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ======================= STICKY SIGN RAIL ======================= --}}
    <nav aria-label="Jump to a zodiac sign"
         class="sticky top-16 z-30 border-b border-gray-200 bg-white/85 backdrop-blur dark:border-gray-800 dark:bg-gray-950/85">
        <div class="vp-rail mx-auto flex max-w-6xl items-center gap-1.5 overflow-x-auto px-4 py-2.5 sm:px-6">
            <span class="shrink-0 pr-1 text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Jump to</span>
            @foreach($signs as $slug => $sign)
                <a href="#{{ $slug }}"
                   class="flex shrink-0 items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-gray-700 transition hover:border-violet-400 hover:text-violet-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-violet-500 dark:hover:text-violet-400">
                    <span class="text-sm" aria-hidden="true">{{ $sign['symbol'] }}</span>
                    {{ $sign['name'] }}
                </a>
            @endforeach
        </div>
    </nav>

    <div class="bg-white dark:bg-gray-950">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-16">

            {{-- ======================= INTERACTIVE PICKER ======================= --}}
            <section id="pick-your-sign" class="scroll-mt-32" aria-labelledby="picker-heading">
                <h2 id="picker-heading" class="sr-only">Find and read your zodiac sign</h2>
                <div
                    data-island="ZodiacHoroscopeWidget"
                    data-island-eager
                    data-props="{{ json_encode([
                        'signs' => $islandSigns,
                        'todayHoroscopes' => $islandReadings,
                        'pageUrl' => route('horoscope'),
                        'compatibilityUrl' => route('horoscope.compatibility'),
                        'today' => $today->format('l, j F Y'),
                    ]) }}"
                >
                    {{-- Server-rendered fallback: the picker is an enhancement,
                         the twelve readings below are the page. --}}
                    <p class="rounded-3xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        Choose your sign from the list below to read today's forecast.
                    </p>
                </div>
            </section>

            {{-- ======================= ALL 12 READINGS ======================= --}}
            <section class="mt-16" aria-labelledby="all-signs-heading">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-800">
                    <div>
                        <h2 id="all-signs-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            Today's horoscope, sign by sign
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                            Every reading below is written for
                            <time datetime="{{ $today->toDateString() }}" class="font-semibold text-gray-900 dark:text-white">{{ $today->format('l, j F Y') }}</time>
                            and covers love, career, money and health, along with the lucky details for the day.
                        </p>
                    </div>
                    <span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-black text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                        12 signs · updated daily
                    </span>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach($signs as $slug => $sign)
                        @php $h = $todayHoroscopes[$slug] ?? null; @endphp
                        @continue(! $h)

                        <article id="{{ $slug }}"
                                 class="group scroll-mt-32 overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm transition hover:shadow-xl hover:shadow-gray-200/60 dark:border-gray-800 dark:bg-gray-900/40 dark:hover:shadow-none"
                                 aria-labelledby="{{ $slug }}-heading">

                            {{-- Sign header, tinted with the sign's own colour --}}
                            <header class="relative flex items-center gap-4 overflow-hidden bg-[#0b0a1e] p-5 text-white sm:p-6">
                                <div class="pointer-events-none absolute -right-16 -top-16 size-48 rounded-full blur-3xl"
                                     style="background-color: {{ $sign['color'] }}40;" aria-hidden="true"></div>

                                <img src="{{ $sign['image'] }}" alt="{{ $sign['name'] }} ({{ $sign['vedic'] }}) zodiac sign"
                                     class="relative size-16 shrink-0 rounded-2xl border object-cover sm:size-[4.5rem]"
                                     style="border-color: {{ $sign['color'] }}66;" width="72" height="72" loading="lazy" decoding="async">

                                <div class="relative min-w-0 flex-1">
                                    <h3 id="{{ $slug }}-heading" class="truncate text-xl font-black tracking-tight sm:text-2xl">
                                        {{ $sign['name'] }} <span class="text-white/50">Horoscope Today</span>
                                    </h3>
                                    <p class="mt-1 text-xs font-medium text-white/60">
                                        {{ $sign['vedic'] }} · {{ $sign['symbol_name'] }} · {{ $sign['dates'] }}
                                    </p>
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $elementBadge[$sign['element']] ?? 'border-white/20 bg-white/10 text-white' }}">
                                            {{ $sign['element'] }}
                                        </span>
                                        <span class="rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-white/70">
                                            {{ $sign['quality'] }}
                                        </span>
                                        <span class="rounded-full border border-white/15 bg-white/5 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-white/70">
                                            {{ $sign['planet'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="relative hidden shrink-0 text-right sm:block">
                                    <div class="text-3xl font-black" style="color: {{ $sign['color'] }};">{{ $h['score'] }}<span class="text-lg">%</span></div>
                                    <div class="text-[10px] font-black uppercase tracking-wider text-white/45">Energy</div>
                                </div>
                            </header>

                            <div class="p-5 sm:p-6">
                                {{-- Mood strip --}}
                                <p class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    <span class="font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Mood today</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $h['mood'] }}</span>
                                    <span class="text-gray-300 dark:text-gray-700" aria-hidden="true">·</span>
                                    <span class="font-semibold text-gray-500 sm:hidden dark:text-gray-400">{{ $h['score'] }}% energy</span>
                                </p>

                                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                    {{ $h['overview'] }}
                                </p>

                                {{-- Four life areas --}}
                                <dl class="mt-5 space-y-3">
                                    @foreach([
                                        ['key' => 'love', 'label' => 'Love', 'icon' => '💖', 'bar' => 'bg-rose-500'],
                                        ['key' => 'career', 'label' => 'Career', 'icon' => '💼', 'bar' => 'bg-indigo-500'],
                                        ['key' => 'money', 'label' => 'Money', 'icon' => '💰', 'bar' => 'bg-amber-500'],
                                        ['key' => 'health', 'label' => 'Health', 'icon' => '🌿', 'bar' => 'bg-emerald-500'],
                                    ] as $area)
                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3.5 dark:border-gray-800 dark:bg-gray-900/50">
                                            <dt class="flex items-center justify-between gap-3">
                                                <span class="flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200">
                                                    <span aria-hidden="true">{{ $area['icon'] }}</span> {{ $area['label'] }}
                                                </span>
                                                <span class="flex items-center gap-2">
                                                    <span class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800" aria-hidden="true">
                                                        <span class="block h-full rounded-full {{ $area['bar'] }}" style="width: {{ $h['scores'][$area['key']] }}%;"></span>
                                                    </span>
                                                    <span class="w-8 text-right text-xs font-black text-gray-900 dark:text-white">{{ $h['scores'][$area['key']] }}%</span>
                                                </span>
                                            </dt>
                                            <dd class="mt-1.5 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                                {{ $h[$area['key']] }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>

                                {{-- Lucky details --}}
                                <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-gray-200 bg-gray-200 sm:grid-cols-3 dark:border-gray-800 dark:bg-gray-800">
                                    @foreach([
                                        ['Lucky number', '#'.$h['lucky_number']],
                                        ['Lucky colour', $h['lucky_color']],
                                        ['Lucky time', $h['lucky_time']],
                                        ['Lucky day', $sign['lucky_day']],
                                        ['Direction', $h['lucky_direction']],
                                        ['Gemstone', $sign['gemstone']],
                                    ] as [$label, $value])
                                        <div class="bg-white px-3 py-2.5 dark:bg-gray-900">
                                            <dt class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $label }}</dt>
                                            <dd class="mt-0.5 truncate text-xs font-bold text-gray-900 dark:text-white">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>

                                {{-- Mantra --}}
                                <blockquote class="mt-5 rounded-2xl border-l-4 bg-gray-50 py-3 pl-4 pr-3 text-sm italic text-gray-700 dark:bg-gray-900/60 dark:text-gray-300"
                                            style="border-color: {{ $sign['color'] }};">
                                    “{{ $h['mantra'] }}”
                                </blockquote>

                                {{-- Evergreen sign description: the part that ranks between --}}
                                <details class="group/details mt-5">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-black uppercase tracking-wider text-gray-500 transition hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400">
                                        About the {{ $sign['name'] }} personality
                                        <span class="transition group-open/details:rotate-180" aria-hidden="true">⌄</span>
                                    </summary>
                                    <div class="mt-3 space-y-3">
                                        <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $sign['about'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            <strong class="font-black text-emerald-600 dark:text-emerald-400">Strengths:</strong>
                                            {{ implode(', ', $sign['strengths']) }}
                                            <span class="mx-1 text-gray-300 dark:text-gray-700" aria-hidden="true">|</span>
                                            <strong class="font-black text-rose-600 dark:text-rose-400">Watch out for:</strong>
                                            {{ implode(', ', $sign['weaknesses']) }}
                                        </p>
                                    </div>
                                </details>

                                {{-- Best matches, linked into the compatibility calculator --}}
                                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Best matches</span>
                                    @foreach($sign['best_matches'] as $match)
                                        @continue(! isset($signs[$match]))
                                        <a href="{{ route('horoscope.compatibility', ['sign1' => $slug, 'sign2' => $match]) }}"
                                           class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700 transition hover:bg-violet-100 hover:text-violet-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-violet-500/15 dark:hover:text-violet-300">
                                            <span aria-hidden="true">{{ $signs[$match]['symbol'] }}</span>
                                            {{ $signs[$match]['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <x-ads.header class="my-14" />

            {{-- ======================= ELEMENTS GUIDE ======================= --}}
            <section class="mt-16" aria-labelledby="elements-heading">
                <h2 id="elements-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    The four elements, and why they decide compatibility
                </h2>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Each of the 12 zodiac signs belongs to one of four elements. The element sets the temperature of a
                    sign — how it loves, argues, spends and recovers — and it is the first thing an astrologer looks at
                    when checking whether two people will get along.
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($elements as $element)
                        <div class="rounded-3xl border {{ $element['ring'] }} {{ $element['tint'] }} p-5">
                            <span class="text-2xl" aria-hidden="true">{{ $element['icon'] }}</span>
                            <h3 class="mt-2 text-lg font-black {{ $element['accent'] }}">{{ $element['name'] }} signs</h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-gray-600 dark:text-gray-300">{{ $element['traits'] }}</p>
                            <p class="mt-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $element['pairs'] }}</p>
                            <ul class="mt-3 flex flex-wrap gap-1.5">
                                @foreach($element['signs'] as $memberSlug)
                                    @continue(! isset($signs[$memberSlug]))
                                    <li>
                                        <a href="#{{ $memberSlug }}"
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
            </section>

            {{-- ======================= LOVE MATCH CTA ======================= --}}
            <aside class="relative mt-16 overflow-hidden rounded-3xl bg-[#0b0a1e] p-8 text-white sm:p-10">
                <div class="vp-sky vp-twinkle pointer-events-none absolute inset-0" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -left-20 -top-24 size-80 rounded-full bg-rose-600/25 blur-[110px]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-24 -right-16 size-80 rounded-full bg-violet-600/25 blur-[110px]" aria-hidden="true"></div>

                <div class="relative flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-400/30 bg-rose-500/15 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-rose-200">
                            <span aria-hidden="true">💖</span> Love compatibility
                        </span>
                        <h2 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">
                            How well do your two signs actually match?
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">
                            Pick your sign and your partner's to get a match score with love chemistry, friendship,
                            and the one piece of advice that keeps the pairing working.
                        </p>
                    </div>
                    <a href="{{ route('horoscope.compatibility') }}"
                       class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-gradient-to-r from-rose-500 to-violet-600 px-7 py-4 text-sm font-black shadow-xl shadow-rose-900/40 transition hover:from-rose-400 hover:to-violet-500 active:scale-95">
                        Open the calculator
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </aside>

            {{-- ======================= FAQ ======================= --}}
            <section class="mt-16" aria-labelledby="faq-heading">
                <h2 id="faq-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    Horoscope questions, answered
                </h2>

                <div class="mt-6 divide-y divide-gray-200 overflow-hidden rounded-3xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                    @foreach($faqs as $faq)
                        <details class="group bg-white open:bg-gray-50/70 dark:bg-gray-900/40 dark:open:bg-gray-900/70">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-sm font-bold text-gray-900 transition hover:text-violet-600 dark:text-white dark:hover:text-violet-400">
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

            {{-- ======================= RELATED READING ======================= --}}
            @if($trending->isNotEmpty())
                <section class="mt-16" aria-labelledby="trending-heading">
                    <h2 id="trending-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                        Trending on the site right now
                    </h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($trending as $post)
                            <x-post.card :post="$post" size="sm" />
                        @endforeach
                    </div>
                </section>
            @endif

            <x-newsletter.form class="mx-auto mt-16 max-w-xl" />

            {{-- Astrology is entertainment, and saying so plainly is both honest
                 and what Google's quality guidelines expect on this topic. --}}
            <p class="mx-auto mt-10 max-w-2xl text-center text-xs leading-relaxed text-gray-400 dark:text-gray-500">
                Horoscopes on this page are generated for entertainment and general guidance. They are not a substitute
                for professional medical, legal or financial advice.
            </p>
        </div>
    </div>
@endsection
