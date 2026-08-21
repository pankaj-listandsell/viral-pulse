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
                {{-- The placeholder holds the strip's exact height so the news
                     below does not jump down when the island mounts. It has to
                     match the real thing: same avatar size, same gaps, same
                     header row. --}}
                <div class="mb-6" data-island="StoryViewerModal" data-island-eager
                     data-props="{{ json_encode(['stories' => $webStories, 'signs' => $activeSigns, 'todayHoroscopes' => $activeTodayHoroscopes, 'pageUrl' => route('horoscope')]) }}">
                    <div class="mb-2 flex justify-end">
                        <div class="h-4 w-24 rounded bg-gray-100 dark:bg-gray-900"></div>
                    </div>
                    <div class="relative w-full overflow-hidden py-1">
                        <div class="flex items-center gap-4 overflow-x-hidden sm:gap-5">
                            @foreach(range(1, 10) as $i)
                                <div class="flex flex-shrink-0 flex-col items-center gap-2">
                                    <div class="size-20 rounded-full border border-gray-100 bg-gray-50 sm:size-24 dark:border-gray-800 dark:bg-gray-900"></div>
                                    <div class="h-3 w-12 rounded bg-gray-100 dark:bg-gray-900"></div>
                                    <div class="h-2 w-8 rounded bg-gray-100 dark:bg-gray-900"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- ======================= TOP STORIES =======================
                 A front page, not a carousel. The slider showed one headline at
                 a time and hid four; a reader arriving at a news site wants to
                 see what the day holds without waiting six seconds for it to
                 rotate, and the four hidden ones were markup nobody read.
                 No JavaScript, nothing to break, five headlines at once. --}}
            @if($heroSlides->isNotEmpty())
                @php
                    $lead = $heroSlides->first();
                    $secondary = $heroSlides->skip(1)->take(4);
                @endphp

                <section class="mb-14" aria-labelledby="top-stories-heading">
                    <div class="mb-6 flex items-baseline justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <span class="block h-6 w-1 rounded-full bg-brand-600" aria-hidden="true"></span>
                            <h2 id="top-stories-heading" class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                                Top stories
                            </h2>
                        </div>

                        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-emerald-500"></span>
                            </span>
                            <time datetime="{{ now()->toDateString() }}">{{ now()->format('l, j F') }}</time>
                        </p>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-12">
                        {{-- The lead. Its picture is the LCP element on this
                             page, so it is the one image that loads eagerly. --}}
                        <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 lg:col-span-7 dark:border-gray-800">
                            <a href="{{ route('posts.show', $lead) }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                                <x-post.image :post="$lead" eager conversion="large"
                                              class="aspect-16/9 w-full object-cover transition duration-700 group-hover:scale-105" />
                            </a>

                            <div class="flex flex-1 flex-col p-5 sm:p-6">
                                @if($lead->category)
                                    <a href="{{ route('categories.show', $lead->category) }}"
                                       class="relative z-10 mb-3 inline-flex w-fit items-center rounded px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white"
                                       style="background-color: {{ $lead->category->color ?? '#ef4444' }};">
                                        {{ $lead->category->name }}
                                    </a>
                                @endif

                                {{-- The page's H1. A front page needs one, and
                                     the lead story's headline is the largest,
                                     most prominent text on it - which is what
                                     an H1 is for. --}}
                                <h1 class="text-2xl font-black leading-tight tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                                    <a href="{{ route('posts.show', $lead) }}"
                                       class="transition after:absolute after:inset-0 after:content-[''] hover:text-brand-600 dark:hover:text-brand-400">
                                        {{ $lead->title }}
                                    </a>
                                </h1>

                                @if($lead->excerpt)
                                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-600 sm:text-base dark:text-gray-400">
                                        {{ $lead->excerpt }}
                                    </p>
                                @endif

                                <p class="mt-4 flex flex-wrap items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <time datetime="{{ $lead->published_at?->toDateString() }}">{{ $lead->published_at?->diffForHumans() }}</time>
                                    @if($lead->reading_time)
                                        <span aria-hidden="true">&middot;</span>
                                        <span>{{ $lead->reading_time }} min read</span>
                                    @endif
                                </p>
                            </div>
                        </article>

                        @if($secondary->isNotEmpty())
                            <ol class="divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 lg:col-span-5 dark:divide-gray-800 dark:border-gray-800">
                                @foreach($secondary as $index => $post)
                                    <li class="group relative flex gap-4 p-4 transition hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg text-[11px] font-black text-white"
                                              style="background-color: {{ $post->category?->color ?? '#ef4444' }};" aria-hidden="true">
                                            {{ $index + 2 }}
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            @if($post->category)
                                                <span class="text-[10px] font-black uppercase tracking-wider"
                                                      style="color: {{ $post->category->color ?? '#ef4444' }};">
                                                    {{ $post->category->name }}
                                                </span>
                                            @endif

                                            <h3 class="mt-1 line-clamp-3 text-sm font-bold leading-snug text-gray-900 dark:text-white">
                                                <a href="{{ route('posts.show', $post) }}"
                                                   class="transition after:absolute after:inset-0 after:content-[''] group-hover:text-brand-600 dark:group-hover:text-brand-400">
                                                    {{ $post->title }}
                                                </a>
                                            </h3>

                                            <p class="mt-1.5 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->diffForHumans() }}</time>
                                            </p>
                                        </div>

                                        <a href="{{ route('posts.show', $post) }}" class="block shrink-0" tabindex="-1" aria-hidden="true">
                                            <x-post.image :post="$post"
                                                          class="size-16 rounded-xl object-cover transition duration-500 group-hover:scale-105" />
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
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
                            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($latest as $index => $post)
                                    <x-post.card :post="$post" size="sm" :eager="$index < 3" />
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- ======================= SECTION BLOCKS =======================
                         What makes a front page read like a newsroom instead of
                         one long list: each busy section shows its own lead and
                         the headlines under it, and links on to its own page.
                         Every headline here is a link a crawler can follow into
                         a part of the archive the home page never reached. --}}
                    @foreach($sections as $section)
                        @php
                            $sectionCategory = $section['category'];
                            $sectionPosts = $section['posts'];
                            $sectionLead = $sectionPosts->first();
                            $sectionRest = $sectionPosts->skip(1);
                            $sectionAccent = $sectionCategory->color ?? '#ef4444';
                        @endphp

                        <section aria-labelledby="section-{{ $sectionCategory->slug }}">
                            <div class="mb-6 flex items-baseline justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-800">
                                <div class="flex items-center gap-2.5">
                                    <span class="block h-6 w-1 rounded-full" style="background-color: {{ $sectionAccent }};" aria-hidden="true"></span>
                                    <h2 id="section-{{ $sectionCategory->slug }}" class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                                        {{ $sectionCategory->name }}
                                    </h2>
                                </div>

                                <a href="{{ route('categories.show', $sectionCategory) }}"
                                   class="group inline-flex shrink-0 items-center gap-1 text-sm font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                    All {{ $sectionCategory->name }}
                                    <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
                                </a>
                            </div>

                            <div class="grid gap-6 lg:grid-cols-2">
                                <x-post.card :post="$sectionLead" />

                                @if($sectionRest->isNotEmpty())
                                    <ol class="divide-y divide-gray-200 dark:divide-gray-800">
                                        @foreach($sectionRest as $item)
                                            <li class="group relative flex gap-3.5 py-3 first:pt-0 last:pb-0">
                                                <a href="{{ route('posts.show', $item) }}" class="block shrink-0" tabindex="-1" aria-hidden="true">
                                                    <x-post.image :post="$item"
                                                                  class="size-20 rounded-xl object-cover transition duration-500 group-hover:scale-105 sm:size-24" />
                                                </a>

                                                <div class="min-w-0 self-center">
                                                    <h3 class="line-clamp-3 text-sm font-bold leading-snug text-gray-900 dark:text-white">
                                                        <a href="{{ route('posts.show', $item) }}"
                                                           class="transition after:absolute after:inset-0 after:content-[''] group-hover:text-brand-600 dark:group-hover:text-brand-400">
                                                            {{ $item->title }}
                                                        </a>
                                                    </h3>

                                                    <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                        <time datetime="{{ $item->published_at?->toDateString() }}">{{ $item->published_at?->diffForHumans() }}</time>
                                                        @if($item->reading_time)
                                                            <span aria-hidden="true">&middot;</span>
                                                            <span>{{ $item->reading_time }} min read</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>
                        </section>
                    @endforeach

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
