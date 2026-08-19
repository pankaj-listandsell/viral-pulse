@extends('layouts.public')

@php
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $tagline = $siteSettings['site_description'] ?? null;

    $settingsService = app(\App\Services\SettingsService::class);
    $webStoriesEnabled = $settingsService->bool('web_stories_enabled', true);
    $horoscopeEnabled = $settingsService->bool('horoscope_enabled', true);

    $webStories = $webStoriesEnabled ? $trending->map(fn ($p) => [
        'id' => $p->id,
        'title' => $p->title,
        'excerpt' => $p->excerpt,
        'category' => $p->category?->name,
        'image' => $p->featured_image ? Storage::disk(config('site.media.disk'))->url($p->featured_image) : null,
        'url' => route('posts.show', $p),
        'reading_time' => $p->reading_time,
    ])->values()->all() : [];

    $activeSigns = $horoscopeEnabled ? ($signs ?? []) : [];
    $activeTodayHoroscopes = $horoscopeEnabled ? ($todayHoroscopes ?? []) : [];
@endphp

@section('content')
    <div class="bg-white dark:bg-gray-950">

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">

            {{-- Web stories & horoscope strip, first thing on the page --}}
            @if(!empty($webStories) || !empty($activeSigns))
                <div class="mb-8" data-island="StoryViewerModal" data-island-eager
                     data-props="{{ json_encode(['stories' => $webStories, 'signs' => $activeSigns, 'todayHoroscopes' => $activeTodayHoroscopes, 'pageUrl' => route('horoscope')]) }}"></div>
            @endif

            {{-- ======================= HERO SLIDER ======================= --}}
            @if($heroSlides->isNotEmpty())
                <section class="mb-14" aria-label="Top stories" data-hero-slider>
                    <div class="relative overflow-hidden rounded-3xl border border-gray-200 dark:border-gray-800">
                        <div class="relative min-h-[24rem] sm:min-h-[22rem]">
                            @foreach($heroSlides as $index => $slide)
                                @php
                                    $slideAccent = $slide->category?->color ?? '#ef4444';

                                    // The lead story's headline is the page's H1.
                                    // The home page needs one, and this is the
                                    // largest, most prominent text on it - so it
                                    // carries the heading rather than a separate
                                    // masthead line sitting above the slider.
                                    $slideHeading = $index === 0 ? 'h1' : 'h2';
                                @endphp

                                <div data-slide="{{ $index }}"
                                     @class([
                                         'absolute inset-0 flex flex-col justify-between overflow-hidden bg-[#0f1421] p-6 text-white transition-opacity duration-500 sm:p-10',
                                         'opacity-100' => $index === 0,
                                         'opacity-0' => $index !== 0,
                                     ])
                                     aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                                     @if($index !== 0) hidden @endif>

                                    <span class="absolute inset-x-0 top-0 h-1.5" style="background-color: {{ $slideAccent }};" aria-hidden="true"></span>
                                    <span class="absolute inset-0 opacity-20"
                                          style="background-image: linear-gradient(to top right, {{ $slideAccent }}, transparent 65%);" aria-hidden="true"></span>

                                    <div class="relative flex items-start justify-between gap-4">
                                        <span class="flex items-center gap-2 text-sm font-bold text-white/70">
                                            <span class="grid size-5 place-items-center rounded bg-brand-600 text-white">
                                                <x-icon name="flame" class="size-3" />
                                            </span>
                                            {{ $siteName }}
                                        </span>

                                        <span class="flex items-center gap-3">
                                            @if($slide->category)
                                                <span class="rounded px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white"
                                                      style="background-color: {{ $slideAccent }};">
                                                    {{ $slide->category->name }}
                                                </span>
                                            @endif
                                            <span class="font-mono text-xs font-bold text-white/40">
                                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($heroSlides->count(), 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                        </span>
                                    </div>

                                    <div class="relative max-w-3xl">
                                        <{{ $slideHeading }} class="text-2xl font-black leading-tight tracking-tight sm:text-3xl lg:text-4xl">
                                            <a href="{{ route('posts.show', $slide) }}"
                                               class="transition hover:text-white/90"
                                               tabindex="{{ $index === 0 ? '0' : '-1' }}">
                                                {{ $slide->title }}
                                            </a>
                                        </{{ $slideHeading }}>

                                        @if($slide->excerpt)
                                            <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-white/60 sm:text-base">
                                                {{ $slide->excerpt }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="relative flex flex-wrap items-center justify-between gap-4">
                                        <p class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-white/45">
                                            <time datetime="{{ $slide->published_at?->toDateString() }}">{{ $slide->published_at?->format('j M Y') }}</time>
                                            @if($slide->reading_time)
                                                <span aria-hidden="true">&middot;</span>
                                                <span>{{ $slide->reading_time }} min read</span>
                                            @endif
                                        </p>

                                        <a href="{{ route('posts.show', $slide) }}"
                                           class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-2.5 text-xs font-black text-gray-900 transition hover:bg-brand-500 hover:text-white active:scale-95"
                                           tabindex="{{ $index === 0 ? '0' : '-1' }}">
                                            Read article <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($heroSlides->count() > 1)
                            {{-- Controls live in their own strip below the slides
                                 rather than floating over them: overlaid, they
                                 landed on the date and the read button, and the
                                 dots had no contrast against a dark slide. --}}
                            <div class="flex items-center justify-between gap-4 border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex items-center gap-1.5" aria-label="Choose a story">
                                    @foreach($heroSlides as $index => $slide)
                                        <button type="button"
                                                data-slider-dot="{{ $index }}"
                                                aria-label="Story {{ $index + 1 }}"
                                                aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                                @class([
                                                    'h-2 rounded-full transition-all duration-300',
                                                    'w-8 bg-brand-600 dark:bg-brand-400' => $index === 0,
                                                    'w-2 bg-gray-300 dark:bg-gray-700' => $index !== 0,
                                                ])></button>
                                    @endforeach
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" data-slider-prev aria-label="Previous story"
                                            class="grid size-9 place-items-center rounded-full border border-gray-200 text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-300 dark:hover:text-brand-400">
                                        <span aria-hidden="true">&larr;</span>
                                    </button>
                                    <button type="button" data-slider-next aria-label="Next story"
                                            class="grid size-9 place-items-center rounded-full border border-gray-200 text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-300 dark:hover:text-brand-400">
                                        <span aria-hidden="true">&rarr;</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <x-ads.header />

            {{-- ======================= FEED + SIDEBAR ======================= --}}
            <div class="grid gap-12 lg:grid-cols-3">
                <div class="space-y-16 lg:col-span-2">

                    <section aria-labelledby="latest-heading">
                        <div class="mb-6 flex items-baseline justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
                            <div class="flex items-center gap-2.5">
                                <span class="block h-6 w-1 rounded-full bg-indigo-600" aria-hidden="true"></span>
                                <h2 id="latest-heading" class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Latest stories</h2>
                            </div>
                            <a href="{{ route('latest') }}" class="group inline-flex items-center gap-1 text-sm font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                View all
                                <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
                            </a>
                        </div>

                        @if($latest->isEmpty())
                            <p class="rounded-3xl border border-dashed border-gray-300 px-6 py-16 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                Nothing published yet. Check back soon.
                            </p>
                        @else
                            <div class="grid gap-6 sm:grid-cols-2">
                                @foreach($latest as $post)
                                    <x-post.card :post="$post" />
                                @endforeach
                            </div>
                        @endif
                    </section>

                    @if($featured->isNotEmpty())
                        <section aria-labelledby="featured-heading">
                            <div class="mb-6 flex items-baseline gap-2.5 border-b border-gray-200 pb-4 dark:border-gray-800">
                                <span class="block h-6 w-1 rounded-full bg-amber-500" aria-hidden="true"></span>
                                <h2 id="featured-heading" class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Editor's choice</h2>
                            </div>
                            <div class="grid gap-6 sm:grid-cols-2">
                                @foreach($featured as $post)
                                    <x-post.card :post="$post" size="sm" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-8">
                    <div class="space-y-8 lg:sticky lg:top-20">

                        @if($trending->isNotEmpty())
                            <section aria-labelledby="trending-heading" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900/40">
                                <h2 id="trending-heading" class="mb-5 flex items-center gap-2.5 text-base font-black tracking-tight text-gray-900 dark:text-white">
                                    <span class="grid size-7 place-items-center rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400">
                                        <x-icon name="flame" class="size-4" />
                                    </span>
                                    Trending now
                                </h2>

                                <ol class="space-y-4">
                                    @foreach($trending as $index => $post)
                                        <li class="group relative flex gap-3">
                                            <span class="text-sm font-black tabular-nums text-gray-300 dark:text-gray-700" aria-hidden="true">
                                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <div class="min-w-0">
                                                <h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 dark:text-gray-100">
                                                    <a href="{{ route('posts.show', $post) }}"
                                                       class="transition after:absolute after:inset-0 after:content-[''] group-hover:text-brand-600 dark:group-hover:text-brand-400">
                                                        {{ $post->title }}
                                                    </a>
                                                </h3>
                                                <p class="mt-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                    <span style="color: {{ $post->category?->color ?? '#ef4444' }};">{{ $post->category?->name }}</span>
                                                    <span aria-hidden="true">&middot;</span>
                                                    {{ $post->published_at?->diffForHumans() }}
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        @endif

                        @if($horoscopeEnabled && !empty($activeSigns))
                            <section aria-labelledby="horoscope-heading"
                                     class="relative overflow-hidden rounded-2xl bg-[#0b0a1e] p-5 text-white">
                                <div class="pointer-events-none absolute -right-16 -top-16 size-48 rounded-full bg-violet-600/30 blur-3xl" aria-hidden="true"></div>

                                <h2 id="horoscope-heading" class="relative text-base font-black tracking-tight">
                                    <span aria-hidden="true">🔮</span> Today's horoscope
                                </h2>
                                <p class="relative mt-1.5 text-xs leading-relaxed text-white/60">
                                    Free rashifal for all 12 signs — lucky number, colour, love and career for
                                    <time datetime="{{ now()->toDateString() }}">{{ now()->format('j F') }}</time>.
                                </p>

                                <div class="relative mt-4 flex -space-x-2">
                                    @foreach(array_slice($activeSigns, 0, 6) as $sign)
                                        <img src="{{ $sign['image'] }}" alt="{{ $sign['name'] }}"
                                             class="size-9 rounded-full border-2 border-[#0b0a1e] object-cover"
                                             width="36" height="36" loading="lazy">
                                    @endforeach
                                    <span class="grid size-9 place-items-center rounded-full border-2 border-[#0b0a1e] bg-white/10 text-[10px] font-black">
                                        +6
                                    </span>
                                </div>

                                <a href="{{ route('horoscope') }}"
                                   class="relative mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-2.5 text-xs font-black transition hover:from-violet-500 hover:to-fuchsia-500 active:scale-95">
                                    Read my sign <span aria-hidden="true">&rarr;</span>
                                </a>
                            </section>
                        @endif

                        <div class="relative overflow-hidden rounded-2xl bg-slate-950 p-5 text-white shadow-xl dark:border dark:border-gray-800 dark:bg-black/40">
                            <div class="pointer-events-none absolute -bottom-20 -right-20 size-48 rounded-full bg-brand-600/20 blur-3xl" aria-hidden="true"></div>
                            <div class="relative">
                                <x-newsletter.form />
                            </div>
                        </div>

                        <x-ads.sidebar />
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
