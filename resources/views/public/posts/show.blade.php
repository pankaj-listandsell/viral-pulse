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
        <nav aria-label="Breadcrumb" class="mb-6 text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('home') }}" class="transition hover:text-brand-600">Home</a></li>
                <li aria-hidden="true">/</li>
                <li>
                    <a href="{{ route('categories.show', $post->category) }}" class="transition hover:text-brand-600">
                        {{ $post->category->name }}
                    </a>
                </li>
            </ol>
        </nav>

        <div class="grid gap-10 lg:grid-cols-3">
            <article class="lg:col-span-2">
                <header>
                    <a href="{{ route('categories.show', $post->category) }}"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition hover:text-brand-600 dark:text-gray-300">
                        <span class="size-2 rounded-full" style="background-color: {{ $post->category->color ?? '#94a3b8' }}"></span>
                        {{ $post->category->name }}
                    </a>

                    <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">{{ $post->title }}</h1>

                    @if($post->excerpt)
                        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ $post->excerpt }}</p>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-y border-gray-200 py-4 text-sm dark:border-gray-800">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                            <x-icon name="calendar" class="size-4 text-gray-400" />
                            <time datetime="{{ $post->published_at?->toIso8601String() }}" class="font-medium text-gray-700 dark:text-gray-300">
                                {{ $post->published_at?->format('F j, Y') }}
                            </time>
                            @if($post->reading_time)
                                <span>&middot;</span>
                                <span>{{ $post->reading_time }} min read</span>
                            @endif
                        </div>

                        <span class="ml-auto flex items-center gap-2">
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

                            <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <x-icon name="eye" class="size-4" />
                                {{ number_format($post->views_count) }}
                            </span>
                        </span>
                    </div>

                    {{-- Listen to Article Audio Player --}}
                    <div data-island="AudioReader" data-island-eager
                         data-props="{{ json_encode(['title' => $post->title]) }}"></div>
                </header>

                @if($post->featured_image)
                    <figure class="mt-8">
                        <x-post.image :post="$post" eager conversion="large"
                                      class="w-full rounded-xl bg-gray-100 object-cover dark:bg-gray-800" />
                        @if($post->featured_image_alt)
                            <figcaption class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">
                                {{ $post->featured_image_alt }}
                            </figcaption>
                        @endif
                    </figure>
                @endif

                {{-- Table of Contents (Dynamically Populated when article has 2+ headings) --}}
                <div id="article-toc" class="my-8 hidden rounded-2xl border border-gray-200/80 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-900/40">
                    <div class="flex items-center justify-between cursor-pointer select-none" id="toc-toggle">
                        <div class="flex items-center gap-2 font-bold text-sm text-gray-900 dark:text-white">
                            <span class="text-base">📑</span>
                            <span>In this article (Table of Contents)</span>
                        </div>
                        <svg id="toc-chevron" class="size-4 text-gray-400 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    <nav id="toc-list" class="mt-4 border-t border-gray-200/60 pt-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300 space-y-2">
                        <!-- Populated by JavaScript -->
                    </nav>
                </div>

                {{-- Already sanitised on write, so it is safe to render as HTML.
                     Nothing untrusted reaches this point. --}}
                <div class="prose prose-lg mt-8 max-w-none dark:prose-invert
                            prose-headings:tracking-tight prose-a:text-brand-600 prose-img:rounded-xl">
                    {!! $post->content !!}
                </div>

                <x-ads.article />

                @if($post->tags->isNotEmpty())
                    <div class="mt-8 flex flex-wrap gap-2">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('tags.show', $tag) }}"
                               class="rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-300">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Interactive Emoji Reaction Bar --}}
                <div data-island="ReactionsBar" data-island-eager
                     data-props="{{ json_encode(['postId' => $post->id]) }}"></div>

                {{-- Community Pulse Quick Poll --}}
                <div data-island="ArticlePoll" data-island-eager
                     data-props="{{ json_encode(['postId' => $post->id, 'title' => $post->title]) }}"></div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800"
                     data-island="ShareBar" data-props="{{ json_encode($shareProps) }}">
                    {{-- Server-rendered share links work without JavaScript. --}}
                    <p class="mb-2 text-sm font-medium">Share this story</p>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', $post)) }}&text={{ urlencode($post->title) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-lg border border-gray-200 px-3 py-1.5 transition hover:border-brand-400 dark:border-gray-800">X</a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post)) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-lg border border-gray-200 px-3 py-1.5 transition hover:border-brand-400 dark:border-gray-800">Facebook</a>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($post->title.' '.route('posts.show', $post)) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="rounded-lg border border-gray-200 px-3 py-1.5 transition hover:border-brand-400 dark:border-gray-800">WhatsApp</a>
                    </div>
                </div>

                @if($related->isNotEmpty())
                    <section aria-labelledby="related-heading" class="mt-12">
                        <h2 id="related-heading" class="mb-5 text-lg font-semibold tracking-tight">Related stories</h2>
                        <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2">
                            @foreach($related as $item)
                                <x-post.card :post="$item" size="sm" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </article>

            <aside class="space-y-8">
                @if($popular->isNotEmpty())
                    <section aria-labelledby="popular-heading">
                        <h2 id="popular-heading" class="mb-4 text-lg font-semibold tracking-tight">Most read</h2>
                        <ol class="space-y-4">
                            @foreach($popular as $index => $item)
                                <li class="flex gap-3">
                                    <span class="w-5 shrink-0 text-lg font-semibold tabular-nums text-gray-300 dark:text-gray-700">
                                        {{ $index + 1 }}
                                    </span>
                                    <h3 class="text-sm font-medium leading-snug">
                                        <a href="{{ route('posts.show', $item) }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">
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
            </aside>
        </div>
    </div>

    @if($nextStory = ($related->first() ?? $popular->first()))
        <div data-island="UpNextToast"
             data-props="{{ json_encode(['post' => ['title' => $nextStory->title, 'url' => route('posts.show', $nextStory), 'reading_time' => $nextStory->reading_time]]) }}"></div>
    @endif
@endsection
