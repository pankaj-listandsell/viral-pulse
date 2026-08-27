@extends('layouts.public')

@php
    use App\Services\LocaleService;

    $locales = app(LocaleService::class);
    $order = array_keys($signs);

    /**
     * The canonical URL for a pairing.
     *
     * Compatibility is symmetric, so the link always points at the pair in
     * zodiac order - the same URL the compatibility page canonicalises to.
     * Linking to the other ordering would send readers and crawlers to a page
     * that immediately points somewhere else.
     */
    $pairUrl = function (string $a, string $b) use ($locales, $order): string {
        $pair = array_search($a, $order, true) <= array_search($b, $order, true) ? [$a, $b] : [$b, $a];

        return $locales->horoscopeUrl('compatibility', null, null, [
            'sign1' => $locales->signSlug($pair[0]),
            'sign2' => $locales->signSlug($pair[1]),
        ]);
    };

    $signUrl = fn (string $slug): string => $locales->horoscopeUrl('sign', $slug);

    $elementBadge = [
        'Fire' => 'bg-orange-500/15 text-orange-200 border-orange-400/30',
        'Earth' => 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
        'Air' => 'bg-sky-500/15 text-sky-200 border-sky-400/30',
        'Water' => 'bg-indigo-500/15 text-indigo-200 border-indigo-400/30',
    ];

    /*
     * The four life areas, as data. The daily, weekly and monthly sections all
     * render the same four, and writing the markup once means a change to a
     * card lands in all three rather than in whichever one was remembered.
     */
    $areas = [
        ['key' => 'love', 'label' => __('horoscope.ui.love'), 'icon' => '💖', 'accent' => 'rose'],
        ['key' => 'career', 'label' => __('horoscope.ui.career'), 'icon' => '💼', 'accent' => 'amber'],
        ['key' => 'health', 'label' => __('horoscope.ui.health'), 'icon' => '🌿', 'accent' => 'emerald'],
        ['key' => 'money', 'label' => __('horoscope.ui.money'), 'icon' => '💰', 'accent' => 'sky'],
    ];

    $accentClasses = [
        'rose' => 'border-rose-200 bg-rose-50/60 dark:border-rose-500/20 dark:bg-rose-500/5',
        'amber' => 'border-amber-200 bg-amber-50/60 dark:border-amber-500/20 dark:bg-amber-500/5',
        'emerald' => 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-500/20 dark:bg-emerald-500/5',
        'sky' => 'border-sky-200 bg-sky-50/60 dark:border-sky-500/20 dark:bg-sky-500/5',
    ];

    $barColors = [
        'rose' => 'bg-rose-500',
        'amber' => 'bg-amber-500',
        'emerald' => 'bg-emerald-500',
        'sky' => 'bg-sky-500',
    ];

    $facts = [
        ['label' => __('horoscope.ui.date_range'), 'value' => $sign['dates']],
        ['label' => __('horoscope.ui.rashi_letters'), 'value' => $sign['letters'] ?? '—'],
        ['label' => __('horoscope.ui.symbol'), 'value' => $sign['symbol'].' '.$sign['symbol_name']],
        ['label' => __('horoscope.ui.element'), 'value' => $sign['element_label']],
        ['label' => __('horoscope.ui.quality'), 'value' => $sign['quality_label']],
        ['label' => __('horoscope.ui.ruling_planet'), 'value' => $sign['planet_label']],
        ['label' => __('horoscope.ui.gemstone'), 'value' => $sign['gemstone_label']],
        ['label' => __('horoscope.ui.lucky_day'), 'value' => $sign['lucky_day_label']],
    ];

    $luckyItems = [
        ['label' => __('horoscope.ui.lucky_number'), 'value' => $daily['lucky_number'], 'icon' => '🔢'],
        ['label' => __('horoscope.ui.lucky_color'), 'value' => $daily['lucky_color'], 'icon' => '🎨'],
        ['label' => __('horoscope.ui.lucky_time'), 'value' => $daily['lucky_time'], 'icon' => '⏰'],
        ['label' => __('horoscope.ui.lucky_direction'), 'value' => $daily['lucky_direction'], 'icon' => '🧭'],
        ['label' => __('horoscope.ui.mood'), 'value' => $daily['mood'], 'icon' => '✨'],
    ];
@endphp

@push('head')
    <style>
        /* The starfield behind the hero. One small stylesheet rather than a
           dozen arbitrary Tailwind values, and shared with the hub page. */
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
        }
        .vp-twinkle { animation: vp-twinkle 6s ease-in-out infinite; }
        @keyframes vp-twinkle { 0%, 100% { opacity: .35; } 50% { opacity: 1; } }

        .vp-rail { scrollbar-width: none; }
        .vp-rail::-webkit-scrollbar { display: none; }

        /* The open/closed marker is drawn with a rotation so the FAQ needs no
           JavaScript and still animates. */
        .vp-faq[open] .vp-faq-mark { transform: rotate(45deg); }

        @media (prefers-reduced-motion: reduce) {
            .vp-twinkle { animation: none; }
            .vp-faq-mark { transition: none; }
        }
    </style>
@endpush

@section('content')

    {{-- ======================= HERO ======================= --}}
    <section class="relative overflow-hidden bg-[#07061a] text-white">
        <div class="vp-sky vp-twinkle pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-40 left-1/3 size-[32rem] rounded-full blur-[150px]"
             style="background-color: {{ $sign['color'] }}33;" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-48 right-0 size-[30rem] rounded-full bg-violet-600/20 blur-[150px]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">

            {{-- Breadcrumb and language, on one line. Both are real links: the
                 crumb is the internal path back to the hub, and the language
                 links are how a crawler discovers the other translation. --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav aria-label="Breadcrumb" class="min-w-0">
                    <ol class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-white/50">
                        <li><a href="{{ route('home') }}" class="transition hover:text-white">{{ __('horoscope.seo.breadcrumb_home') }}</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ $hubUrl }}" class="transition hover:text-white">{{ __('horoscope.seo.breadcrumb_horoscope') }}</a></li>
                        <li aria-hidden="true">/</li>
                        <li><span class="text-white/90">{{ $sign['name'] }}</span></li>
                    </ol>
                </nav>

                <x-language-switcher :links="$switcher" tone="dark" />
            </div>

            <div class="mt-7 flex flex-col gap-6 sm:flex-row sm:items-center">

                <img src="{{ $sign['image'] }}"
                     alt="{{ $sign['name'] }} ({{ $sign['vedic'] }}) — {{ $sign['symbol_name'] }}"
                     class="size-24 shrink-0 rounded-3xl border object-cover sm:size-32"
                     style="border-color: {{ $sign['color'] }}66;"
                     width="128" height="128" fetchpriority="high" decoding="async">

                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-sm font-black tracking-wide text-white/60">
                        <span class="text-2xl leading-none" aria-hidden="true">{{ $sign['symbol'] }}</span>
                        {{ $sign['vedic'] }} · {{ $sign['symbol_name'] }}
                    </p>

                    <h1 class="mt-1.5 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
                        {{ __('horoscope.seo.sign_heading', ['name' => $sign['name']]) }}
                    </h1>

                    <p class="mt-2 text-sm font-semibold text-white/70">
                        <time datetime="{{ $daily['date_iso'] }}">{{ $today->translatedFormat('l, j F Y') }}</time>
                        <span class="text-white/30" aria-hidden="true"> · </span>
                        {{ $sign['dates'] }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="rounded-full border px-2.5 py-1 text-[11px] font-black uppercase tracking-wide {{ $elementBadge[$sign['element']] ?? 'border-white/20 bg-white/10 text-white' }}">
                            {{ $sign['element_label'] }}
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-white/70">
                            {{ $sign['quality_label'] }}
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-white/70">
                            {{ $sign['planet_label'] }}
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-white/70">
                            {{ $sign['gemstone_label'] }}
                        </span>
                    </div>
                </div>

                {{-- The day's score, drawn as a ring. The number is repeated in
                     text inside it, so it is readable to a screen reader and
                     survives the SVG failing to paint. --}}
                <div class="shrink-0 self-start sm:self-center">
                    <div class="relative grid size-28 place-items-center">
                        <svg viewBox="0 0 36 36" class="absolute inset-0 size-full -rotate-90" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="3"></circle>
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $sign['color'] }}" stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $daily['score'] }} 100"></circle>
                        </svg>
                        <div class="text-center">
                            <div class="text-2xl font-black leading-none">{{ $daily['score'] }}<span class="text-sm">%</span></div>
                            <div class="mt-1 text-[10px] font-black uppercase tracking-wider text-white/45">{{ __('horoscope.ui.score') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-10 lg:py-14">

        {{-- ======================= MAIN COLUMN ======================= --}}
        <div class="min-w-0 space-y-12">

            {{-- ---------- TODAY ---------- --}}
            <section aria-labelledby="today-heading">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="today-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                        {{ __('horoscope.ui.today') }}
                    </h2>
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500">
                        {{ __('horoscope.ui.updated_at', ['date' => $today->translatedFormat('j F Y')]) }}
                    </span>
                </div>

                <p class="mt-4 rounded-3xl border border-violet-200 bg-violet-50/60 p-5 text-base leading-relaxed text-gray-800 sm:p-6 dark:border-violet-500/20 dark:bg-violet-500/5 dark:text-gray-200">
                    {{ $daily['overview'] }}
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach($areas as $area)
                        <article class="rounded-3xl border p-5 {{ $accentClasses[$area['accent']] }}">
                            <header class="flex items-center justify-between gap-3">
                                <h3 class="flex items-center gap-2 text-sm font-black uppercase tracking-wide text-gray-700 dark:text-gray-200">
                                    <span aria-hidden="true">{{ $area['icon'] }}</span>
                                    {{ $area['label'] }}
                                </h3>
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $daily['scores'][$area['key']] ?? '—' }}%</span>
                            </header>

                            @if(isset($daily['scores'][$area['key']]))
                                <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-white/70 dark:bg-white/10"
                                     role="img"
                                     aria-label="{{ $area['label'] }}: {{ $daily['scores'][$area['key']] }}%">
                                    <div class="h-full rounded-full {{ $barColors[$area['accent']] }}"
                                         style="width: {{ $daily['scores'][$area['key']] }}%;"></div>
                                </div>
                            @endif

                            <p class="mt-3 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                {{ $daily[$area['key']] }}
                            </p>
                        </article>
                    @endforeach
                </div>

                {{-- Share, right where the reader has just finished today's
                     reading. What travels is the reading itself, not the
                     headline: a horoscope link with only a title attached is
                     one nobody forwards. --}}
                @php
                    $shareMessage = __('horoscope.ui.share_message', [
                        'name' => $sign['name'],
                        'overview' => $daily['overview'],
                        'number' => $daily['lucky_number'],
                        'color' => $daily['lucky_color'],
                        'score' => $daily['score'],
                    ]);
                @endphp

                <div class="mt-5 rounded-3xl border border-gray-200 p-5 dark:border-gray-800"
                     data-island="ShareBar"
                     data-props="{{ json_encode([
                         'url' => $seo['canonical'],
                         'title' => __('horoscope.seo.sign_heading', ['name' => $sign['name']]),
                         'message' => $shareMessage,
                     ]) }}">

                    {{-- Server-rendered, so sharing works before the island
                         mounts and without JavaScript at all. --}}
                    <p class="mb-3 text-sm font-black text-gray-900 dark:text-white">
                        {{ __('horoscope.ui.share_heading') }}
                    </p>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($shareMessage."\n\n".$seo['canonical']) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-gray-800 dark:hover:text-emerald-400">WhatsApp</a>
                        <a href="https://t.me/share/url?url={{ urlencode($seo['canonical']) }}&text={{ urlencode($shareMessage) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-sky-400 hover:text-sky-600 dark:border-gray-800 dark:hover:text-sky-400">Telegram</a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($seo['canonical']) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:hover:text-brand-400">Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($seo['canonical']) }}&text={{ urlencode(__('horoscope.seo.sign_heading', ['name' => $sign['name']])) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:hover:text-brand-400">X</a>
                    </div>
                </div>

                @if(! empty($daily['mantra']))
                    <p class="mt-5 rounded-3xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-900/40">
                        <span class="block text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            {{ __('horoscope.ui.mantra') }}
                        </span>
                        <span class="mt-1.5 block text-lg font-bold italic text-gray-900 dark:text-white">
                            “{{ $daily['mantra'] }}”
                        </span>
                    </p>
                @endif
            </section>

            {{-- ---------- THIS WEEK / THIS MONTH ---------- --}}
            {{-- Rendered as plain sections rather than tabs: a tab panel that is
                 hidden until clicked is content a crawler discounts, and these
                 two are here precisely to rank for "weekly" and "monthly". --}}
            @foreach([
                ['reading' => $weekly, 'heading' => __('horoscope.ui.this_week'), 'id' => 'weekly',
                 'range' => __('horoscope.ui.week_of', [
                     'from' => \Illuminate\Support\Carbon::parse($weekly['date_iso'])->translatedFormat('j M'),
                     'to' => \Illuminate\Support\Carbon::parse($weekly['period_end_iso'])->translatedFormat('j M Y'),
                 ])],
                ['reading' => $monthly, 'heading' => __('horoscope.ui.this_month'), 'id' => 'monthly',
                 'range' => \Illuminate\Support\Carbon::parse($monthly['date_iso'])->translatedFormat('F Y')],
            ] as $block)
                <section aria-labelledby="{{ $block['id'] }}-heading">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="{{ $block['id'] }}-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            {{ $block['heading'] }}
                        </h2>
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500">{{ $block['range'] }}</span>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-3xl border border-gray-200 dark:border-gray-800">
                        <p class="bg-gray-50 p-5 text-base leading-relaxed text-gray-800 sm:p-6 dark:bg-gray-900/40 dark:text-gray-200">
                            {{ $block['reading']['overview'] }}
                        </p>
                        <dl class="grid gap-px bg-gray-200 sm:grid-cols-2 dark:bg-gray-800">
                            @foreach($areas as $area)
                                <div class="bg-white p-5 dark:bg-gray-950">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                        <span aria-hidden="true">{{ $area['icon'] }}</span>
                                        {{ $area['label'] }}
                                    </dt>
                                    <dd class="mt-1.5 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                        {{ $block['reading'][$area['key']] }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </section>
            @endforeach

            {{-- ---------- ABOUT THE SIGN ---------- --}}
            <section aria-labelledby="about-heading">
                <h2 id="about-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    {{ __('horoscope.ui.about_sign', ['name' => $sign['name']]) }}
                </h2>

                <p class="mt-4 text-base leading-relaxed text-gray-700 dark:text-gray-300">
                    {{ $sign['about'] }}
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                        <h3 class="text-xs font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                            {{ __('horoscope.ui.strengths') }}
                        </h3>
                        <ul class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach($sign['strengths'] as $strength)
                                <li class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ $strength }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rounded-3xl border border-rose-200 bg-rose-50/60 p-5 dark:border-rose-500/20 dark:bg-rose-500/5">
                        <h3 class="text-xs font-black uppercase tracking-wider text-rose-700 dark:text-rose-400">
                            {{ __('horoscope.ui.weaknesses') }}
                        </h3>
                        <ul class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach($sign['weaknesses'] as $weakness)
                                <li class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ $weakness }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <h3 class="mt-8 text-lg font-black tracking-tight text-gray-900 dark:text-white">
                    {{ __('horoscope.ui.sign_profile', ['name' => $sign['name']]) }}
                </h3>

                <dl class="mt-3 grid gap-px overflow-hidden rounded-3xl border border-gray-200 bg-gray-200 sm:grid-cols-2 dark:border-gray-800 dark:bg-gray-800">
                    @foreach($facts as $fact)
                        <div class="flex items-baseline justify-between gap-4 bg-white px-5 py-3.5 dark:bg-gray-950">
                            <dt class="text-xs font-black uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $fact['label'] }}</dt>
                            <dd class="text-right text-sm font-bold text-gray-900 dark:text-white">{{ $fact['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- ---------- COMPATIBILITY ---------- --}}
            <section aria-labelledby="compat-heading">
                <h2 id="compat-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    {{ __('horoscope.ui.compatibility_heading', ['name' => $sign['name']]) }}
                </h2>
                <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                    {{ __('horoscope.ui.compatibility_intro', ['name' => $sign['name']]) }}
                </p>

                <ul class="mt-5 grid gap-2.5 sm:grid-cols-2">
                    @foreach($matches as $match)
                        <li>
                            <a href="{{ $pairUrl($sign['slug'], $match['sign']['slug']) }}"
                               class="group flex items-center gap-3 rounded-2xl border border-gray-200 bg-white p-3.5 transition
                                      hover:border-violet-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/40 dark:hover:border-violet-500/40">

                                <span class="grid size-11 shrink-0 place-items-center rounded-xl text-xl"
                                      style="background-color: {{ $match['sign']['color'] }}1a; color: {{ $match['sign']['color'] }};"
                                      aria-hidden="true">{{ $match['sign']['symbol'] }}</span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="truncate text-sm font-black text-gray-900 dark:text-white">{{ $match['sign']['name'] }}</span>
                                        @if($match['is_best'])
                                            <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                                {{ __('horoscope.ui.best_match') }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                        <span class="block h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500"
                                              style="width: {{ $match['score'] }}%;"></span>
                                    </span>
                                    <span class="mt-1 block truncate text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ $match['title'] }}</span>
                                </span>

                                <span class="shrink-0 text-sm font-black text-gray-900 dark:text-white">{{ $match['score'] }}%</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ $compatibilityUrl }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-full bg-violet-600 px-5 py-2.5 text-sm font-black text-white transition hover:bg-violet-700">
                    💖 {{ __('horoscope.ui.open_calculator') }}
                </a>
            </section>

            {{-- ---------- FAQ ---------- --}}
            @if(! empty($faqs))
                <section aria-labelledby="faq-heading">
                    <h2 id="faq-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                        {{ __('horoscope.ui.sign_faq_heading', ['name' => $sign['name']]) }}
                    </h2>

                    <div class="mt-5 divide-y divide-gray-200 overflow-hidden rounded-3xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        @foreach($faqs as $faq)
                            {{-- Open by default. Google will not award an FAQ rich
                                 result for an answer a reader cannot see, and a
                                 collapsed <details> counts as visible only when
                                 the markup is there - which it is either way, so
                                 the first one is opened for the reader's sake. --}}
                            <details class="vp-faq group bg-white dark:bg-gray-950" @if($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-sm font-black text-gray-900 transition hover:bg-gray-50 dark:text-white dark:hover:bg-gray-900/60">
                                    {{ $faq['question'] }}
                                    <span class="vp-faq-mark shrink-0 text-lg font-normal text-gray-400 transition-transform duration-200" aria-hidden="true">+</span>
                                </summary>
                                <p class="px-5 pb-5 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                    {{ $faq['answer'] }}
                                </p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ---------- OTHER SIGNS ---------- --}}
            <section aria-labelledby="others-heading">
                <h2 id="others-heading" class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    {{ __('horoscope.ui.other_signs') }}
                </h2>

                <ul class="mt-5 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($signs as $slug => $other)
                        @continue($slug === $sign['slug'])
                        <li>
                            <a href="{{ $signUrl($slug) }}"
                               class="flex items-center gap-2.5 rounded-2xl border border-gray-200 bg-white px-3 py-2.5 transition
                                      hover:border-violet-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/40 dark:hover:border-violet-500/40">
                                <span class="text-xl" style="color: {{ $other['color'] }};" aria-hidden="true">{{ $other['symbol'] }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-black text-gray-900 dark:text-white">{{ $other['name'] }}</span>
                                    <span class="block truncate text-[10px] font-semibold text-gray-500 dark:text-gray-400">{{ $other['dates'] }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ $hubUrl }}"
                   class="mt-5 inline-flex items-center gap-2 text-sm font-black text-violet-700 transition hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300">
                    ← {{ __('horoscope.ui.back_to_all') }}
                </a>
            </section>

            <p class="rounded-2xl bg-gray-50 p-4 text-xs leading-relaxed text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                {{ __('horoscope.ui.disclaimer') }}
            </p>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="mt-12 space-y-6 lg:mt-0">

            {{-- Lucky panel. Sticky on desktop: it is the part readers come
                 back to during the day, and it is short enough to stay pinned
                 without ever running past the viewport. --}}
            <div class="lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-3xl border border-gray-200 dark:border-gray-800">
                    <h2 class="bg-[#0b0a1e] px-5 py-3.5 text-sm font-black uppercase tracking-wider text-white">
                        {{ $sign['name'] }} · {{ __('horoscope.ui.today') }}
                    </h2>
                    <dl class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($luckyItems as $item)
                            <div class="flex items-center justify-between gap-3 bg-white px-5 py-3 dark:bg-gray-950">
                                <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    <span aria-hidden="true">{{ $item['icon'] }}</span>
                                    {{ $item['label'] }}
                                </dt>
                                <dd class="text-right text-sm font-black text-gray-900 dark:text-white">{{ $item['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <a href="{{ $compatibilityUrl }}"
                   class="mt-6 block overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 to-fuchsia-600 p-5 text-white transition hover:from-violet-700 hover:to-fuchsia-700">
                    <span class="text-2xl" aria-hidden="true">💖</span>
                    <span class="mt-1.5 block text-base font-black">{{ __('horoscope.seo.compat_title') }}</span>
                    <span class="mt-1 block text-xs text-white/75">{{ __('horoscope.ui.open_calculator') }} →</span>
                </a>

                @if(! empty($trending))
                    <div class="mt-6 rounded-3xl border border-gray-200 p-5 dark:border-gray-800">
                        <h2 class="text-xs font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Trending</h2>
                        <ul class="mt-3 space-y-3">
                            @foreach($trending as $post)
                                <li>
                                    <a href="{{ route('posts.show', $post->slug) }}"
                                       class="line-clamp-2 text-sm font-bold text-gray-800 transition hover:text-violet-700 dark:text-gray-200 dark:hover:text-violet-400">
                                        {{ $post->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </aside>
    </div>

@endsection
