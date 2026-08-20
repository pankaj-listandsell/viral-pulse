@extends('layouts.public')

@php
    $settings = app(\App\Services\SettingsService::class);
    $shareProps = [
        'url' => route('posts.show', $post),
        'title' => $post->title,
    ];
    $likeProps = [
        'endpoint' => route('posts.like', $post),
        'count' => $post->likes_count,
    ];

    $accent = $post->category?->color ?? '#ef4444';

    /**
     * The About page tells readers that an article drafted with AI carries a
     * visible note saying so. Both machine-drafted source types are covered
     * here, because a promise kept only in the settings table is not kept.
     */
    $aiAssisted = in_array($post->source_type, [
        \App\Enums\PostSourceType::Ai,
        \App\Enums\PostSourceType::Trending,
    ], true);

    // A modified date only means something to a reader once it is meaningfully
    // later than publication; otherwise it is noise on every article.
    $showUpdated = $post->published_at
        && $post->updated_at
        && $post->updated_at->gt($post->published_at->copy()->addHours(12));
@endphp

@section('content')
    <div class="fixed inset-x-0 top-16 z-30 h-0.5 bg-transparent">
        <div data-reading-progress class="h-full bg-brand-600 transition-[width] duration-150" style="width: 0"></div>
    </div>

    @unless($post->status->isPubliclyVisible())
        <p class="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-amber-950">
            Preview — this post is {{ $post->status->label() }} and is not visible to visitors.
        </p>
    @endunless

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

        {{-- Three levels here because the BreadcrumbList in the page's JSON-LD
             has three: Google compares the two and expects them to agree. --}}
        <nav aria-label="Breadcrumb" class="mb-6 text-xs font-semibold">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('home') }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">Home</a></li>
                <li aria-hidden="true">&rsaquo;</li>
                <li>
                    <a href="{{ route('categories.show', $post->category) }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">
                        {{ $post->category->name }}
                    </a>
                </li>
                <li aria-hidden="true">&rsaquo;</li>
                <li class="max-w-[22rem] truncate text-gray-900 dark:text-white" aria-current="page">{{ $post->title }}</li>
            </ol>
        </nav>

        <div class="grid gap-10 lg:grid-cols-3">
            <article class="lg:col-span-2">

                {{-- ======================= HEADER ======================= --}}
                <header>
                    <a href="{{ route('categories.show', $post->category) }}"
                       class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white transition hover:opacity-90"
                       style="background-color: {{ $accent }};">
                        {{ $post->category->name }}
                    </a>

                    <h1 class="mt-4 text-3xl font-black leading-[1.15] tracking-tight text-gray-900 sm:text-4xl lg:text-[2.75rem] dark:text-white">
                        {{ $post->title }}
                    </h1>

                    @if($post->excerpt)
                        <p class="mt-4 text-lg leading-relaxed text-gray-600 dark:text-gray-400">{{ $post->excerpt }}</p>
                    @endif

                </header>

                {{-- Every kind of picture is shown, brand cards included.
                     A brand card repeats the headline that is already above it,
                     which is a cost worth naming: it is not an SEO problem - a
                     page saying its own words twice is normal, and the version
                     that counts is the live text - but it is a second reading of
                     the same line. The article looked unfinished without it, and
                     an article with no picture at all is the worse trade. --}}
                @if($post->featured_image)
                    @php
                        // The credit the generator stored with the file: the
                        // photographer for a stock photo, the AI disclosure for
                        // an illustration. A label the page forgets to print is
                        // not a label.
                        $featuredMedia = app(\App\Services\MediaResolver::class)->find($post->featured_image);
                        $credit = $featuredMedia?->caption;
                    @endphp

                    <figure class="mt-8">
                        <x-post.image :post="$post" eager conversion="large"
                                      class="w-full rounded-2xl bg-gray-100 object-cover dark:bg-gray-800" />

                        @if($credit || $post->featured_image_alt)
                            <figcaption class="mt-2 flex flex-wrap items-center justify-center gap-2 text-center text-xs text-gray-500 dark:text-gray-400">
                                @if(str_contains($post->featured_image, '/illustrations/'))
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        AI illustration
                                    </span>
                                @endif
                                <span>{{ $credit ?: $post->featured_image_alt }}</span>
                            </figcaption>
                        @endif
                    </figure>
                @endif

                {{-- No personal byline. Articles here are drafted by AI and
                     reviewed by an editor, so putting one staff member's
                     name on every one of them would credit work they did
                     not do. The Article schema names the publication as the
                     author instead, which is both accurate and what the
                     page shows. --}}
                <div class="mt-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-y border-gray-200 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-brand-600 text-white">
                            <x-icon name="flame" class="size-4" />
                        </span>

                        <div class="text-sm">
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $siteSettings['site_name'] ?? config('app.name') }}
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <time datetime="{{ $post->published_at?->toIso8601String() }}">
                                    {{ $post->published_at?->format('j F Y') }}
                                </time>
                                @if($showUpdated)
                                    <span aria-hidden="true">&middot;</span>
                                    <span>Updated <time datetime="{{ $post->updated_at->toIso8601String() }}">{{ $post->updated_at->format('j F Y') }}</time></span>
                                @endif
                                @if($post->reading_time)
                                    <span aria-hidden="true">&middot;</span>
                                    <span>{{ $post->reading_time }} min read</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <span class="flex items-center gap-2">
                        <span data-island="BookmarkButton" data-island-eager
                              data-props="{{ json_encode(['post' => ['id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'category' => $post->category?->name, 'url' => route('posts.show', $post)]]) }}"></span>

                        @if($settings->bool('likes_enabled', true))
                            <span data-island="LikeButton" data-props="{{ json_encode($likeProps) }}">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-sm text-gray-500 dark:border-gray-800">
                                    <x-icon name="star" class="size-4" />
                                    {{ number_format($post->likes_count) }}
                                </span>
                            </span>
                        @endif

                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <x-icon name="eye" class="size-4" />
                            {{ number_format($post->views_count) }}
                        </span>
                    </span>
                </div>

                {{-- The AI disclosure is not here. It sits with the
                     publisher card at the end of the article: a reader who
                     wants to know who made this and how finds both facts in
                     one place, and the top of the story is the story. --}}

                {{-- Listen to Article Audio Player --}}
                <div data-island="AudioReader" data-island-eager
                     data-props="{{ json_encode(['title' => $post->title]) }}"></div>

                {{-- Already sanitised on write, so it is safe to render as HTML.
                     Nothing untrusted reaches this point. --}}
                <div class="prose prose-lg mt-8 max-w-none dark:prose-invert
                            prose-headings:font-black prose-headings:tracking-tight prose-a:text-brand-600 prose-img:rounded-2xl">
                    {!! $post->content !!}
                </div>

                <x-ads.article />

                @if($post->tags->isNotEmpty())
                    <nav aria-label="Tags" class="mt-8 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Topics</span>
                        @foreach($post->tags as $tag)
                            <a href="{{ route('tags.show', $tag) }}"
                               class="rounded-full border border-gray-200 px-3 py-1 text-xs font-bold text-gray-600 transition hover:-translate-y-0.5 hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-300 dark:hover:border-brand-500 dark:hover:text-brand-400">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                {{-- Table of Contents (populated by JavaScript when the article has 2+ headings) --}}
                <div id="article-toc" class="my-8 hidden rounded-2xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-900/40">
                    <div class="flex cursor-pointer select-none items-center justify-between" id="toc-toggle">
                        <div class="flex items-center gap-2 text-sm font-black text-gray-900 dark:text-white">
                            <span class="text-base" aria-hidden="true">📑</span>
                            <span>In this article</span>
                        </div>
                        <svg id="toc-chevron" class="size-4 text-gray-400 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    <nav id="toc-list" class="mt-4 space-y-2 border-t border-gray-200/60 pt-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <!-- Populated by JavaScript -->
                    </nav>
                </div>

                {{-- Interactive Emoji Reaction Bar --}}
                <div data-island="ReactionsBar" data-island-eager
                     data-props="{{ json_encode(['postId' => $post->id]) }}"></div>

                {{-- Community Pulse Quick Poll --}}
                <div data-island="ArticlePoll" data-island-eager
                     data-props="{{ json_encode(['postId' => $post->id, 'title' => $post->title]) }}"></div>

                <div class="mt-8 rounded-2xl border border-gray-200 p-5 dark:border-gray-800"
                     data-island="ShareBar" data-props="{{ json_encode($shareProps) }}">
                    {{-- Server-rendered share links work without JavaScript. --}}
                    <p class="mb-3 text-sm font-black text-gray-900 dark:text-white">Share this story</p>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', $post)) }}&text={{ urlencode($post->title) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:hover:text-brand-400">X</a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post)) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:hover:text-brand-400">Facebook</a>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($post->title.' '.route('posts.show', $post)) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:hover:text-brand-400">WhatsApp</a>
                    </div>
                </div>

                {{-- Who stands behind the article and how it was made, in one
                     block. The About page promises that an AI-drafted article
                     "carries a visible note saying so", so the note stays -
                     just at the end, where a reader asks the question, rather
                     than across the top of every story. --}}
                <section aria-labelledby="publisher-heading" class="mt-8 flex gap-4 rounded-2xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-900/40">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-brand-600 text-white">
                        <x-icon name="flame" class="size-6" />
                    </span>

                    <div>
                        <h2 id="publisher-heading" class="text-sm font-black text-gray-900 dark:text-white">
                            Published by {{ $siteSettings['site_name'] ?? config('app.name') }}
                        </h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            @if($aiAssisted)
                                This article was drafted with AI assistance and then checked and approved by an editor
                                before publishing.
                            @endif
                            Every story is reviewed by an editor before it goes live. If something here is wrong,
                            <a href="{{ route('contact') }}" class="font-semibold text-brand-600 underline-offset-2 hover:underline dark:text-brand-400">tell us</a>
                            and we will correct it — <a href="{{ route('pages.show', 'about') }}" class="font-semibold text-brand-600 underline-offset-2 hover:underline dark:text-brand-400">how we work</a>.
                        </p>
                    </div>
                </section>

                {{-- Previous / next, so no article is a dead end --}}
                @if($previousPost || $nextPost)
                    <nav aria-label="More stories" class="mt-10 grid gap-4 border-t border-gray-200 pt-8 sm:grid-cols-2 dark:border-gray-800">
                        @if($previousPost)
                            <a href="{{ route('posts.show', $previousPost) }}"
                               class="group rounded-2xl border border-gray-200 p-4 transition hover:-translate-y-0.5 hover:border-brand-400 dark:border-gray-800 dark:hover:border-brand-500">
                                <span class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">&larr; Previous story</span>
                                <p class="mt-1.5 line-clamp-2 text-sm font-bold leading-snug text-gray-900 transition group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                    {{ $previousPost->title }}
                                </p>
                            </a>
                        @endif

                        @if($nextPost)
                            <a href="{{ route('posts.show', $nextPost) }}"
                               class="group rounded-2xl border border-gray-200 p-4 text-right transition hover:-translate-y-0.5 hover:border-brand-400 sm:col-start-2 dark:border-gray-800 dark:hover:border-brand-500">
                                <span class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Next story &rarr;</span>
                                <p class="mt-1.5 line-clamp-2 text-sm font-bold leading-snug text-gray-900 transition group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                    {{ $nextPost->title }}
                                </p>
                            </a>
                        @endif
                    </nav>
                @endif

                @if($related->isNotEmpty())
                    <section aria-labelledby="related-heading" class="mt-12">
                        <h2 id="related-heading" class="mb-6 flex items-center gap-2.5 text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            <span class="block h-6 w-1 rounded-full" style="background-color: {{ $accent }};" aria-hidden="true"></span>
                            Related stories
                        </h2>
                        <div class="grid gap-6 sm:grid-cols-2">
                            @foreach($related as $item)
                                <x-post.card :post="$item" size="sm" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </article>

            <aside class="space-y-8">
                <div class="space-y-8 lg:sticky lg:top-20">
                    @if($popular->isNotEmpty())
                        <section aria-labelledby="popular-heading" class="rounded-2xl border border-gray-200 p-5 dark:border-gray-800">
                            <h2 id="popular-heading" class="mb-5 flex items-center gap-2.5 text-base font-black tracking-tight text-gray-900 dark:text-white">
                                <span class="grid size-7 place-items-center rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400">
                                    <x-icon name="flame" class="size-4" />
                                </span>
                                Most read
                            </h2>
                            <ol class="space-y-4">
                                @foreach($popular as $index => $item)
                                    <li class="group relative flex gap-3">
                                        <span class="text-sm font-black tabular-nums text-gray-300 dark:text-gray-700" aria-hidden="true">
                                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('posts.show', $item) }}"
                                               class="transition after:absolute after:inset-0 after:content-[''] group-hover:text-brand-600 dark:group-hover:text-brand-400">
                                                {{ $item->title }}
                                            </a>
                                        </h3>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    <x-newsletter.form compact />

                    <x-ads.sidebar />
                </div>
            </aside>
        </div>
    </div>

    @if($nextStory = ($related->first() ?? $popular->first()))
        <div data-island="UpNextToast"
             data-props="{{ json_encode(['post' => ['title' => $nextStory->title, 'url' => route('posts.show', $nextStory), 'reading_time' => $nextStory->reading_time]]) }}"></div>
    @endif
@endsection
