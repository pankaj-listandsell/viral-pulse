@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">

        @if($hero)
            <section aria-labelledby="hero-heading" class="mb-12">
                <h2 id="hero-heading" class="sr-only">Featured story</h2>

                <div class="grid gap-6 lg:grid-cols-2 lg:items-center">
                    <a href="{{ route('posts.show', $hero) }}" class="group block overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-800">
                        <x-post.image :post="$hero" eager
                                      class="aspect-16/10 w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                    </a>

                    <div>
                        @if($hero->category)
                            <a href="{{ route('categories.show', $hero->category) }}"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition hover:text-brand-600 dark:text-gray-300">
                                <span class="size-2 rounded-full" style="background-color: {{ $hero->category->color ?? '#94a3b8' }}"></span>
                                {{ $hero->category->name }}
                            </a>
                        @endif

                        <h1 class="mt-3 text-2xl font-semibold leading-tight tracking-tight sm:text-4xl">
                            <a href="{{ route('posts.show', $hero) }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">
                                {{ $hero->title }}
                            </a>
                        </h1>

                        @if($hero->excerpt)
                            <p class="mt-4 text-base text-gray-600 dark:text-gray-400">{{ $hero->excerpt }}</p>
                        @endif

                        <p class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $hero->author?->name }}</span>
                            <span aria-hidden="true">&middot;</span>
                            <time datetime="{{ $hero->published_at?->toDateString() }}">{{ $hero->published_at?->format('M j, Y') }}</time>
                            @if($hero->reading_time)
                                <span aria-hidden="true">&middot;</span>
                                <span>{{ $hero->reading_time }} min read</span>
                            @endif
                        </p>
                    </div>
                </div>
            </section>
        @endif

        <x-ads.header />

        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <section aria-labelledby="latest-heading">
                    <div class="mb-5 flex items-baseline justify-between">
                        <h2 id="latest-heading" class="text-lg font-semibold tracking-tight">Latest stories</h2>
                        <a href="{{ route('latest') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                            View all
                        </a>
                    </div>

                    @if($latest->isEmpty())
                        <p class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Nothing published yet. Check back soon.
                        </p>
                    @else
                        <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2">
                            @foreach($latest as $post)
                                <x-post.card :post="$post" />
                            @endforeach
                        </div>
                    @endif
                </section>

                @if($featured->isNotEmpty())
                    <section aria-labelledby="featured-heading" class="mt-12">
                        <h2 id="featured-heading" class="mb-5 text-lg font-semibold tracking-tight">Editor's picks</h2>
                        <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2">
                            @foreach($featured as $post)
                                <x-post.card :post="$post" size="sm" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-8">
                @if($trending->isNotEmpty())
                    <section aria-labelledby="trending-heading">
                        <h2 id="trending-heading" class="mb-4 flex items-center gap-2 text-lg font-semibold tracking-tight">
                            <x-icon name="flame" class="size-5 text-brand-600" />
                            Trending
                        </h2>

                        <ol class="space-y-4">
                            @foreach($trending as $index => $post)
                                <li class="flex gap-3">
                                    <span class="w-5 shrink-0 text-lg font-semibold tabular-nums text-gray-300 dark:text-gray-700">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-medium leading-snug">
                                            <a href="{{ route('posts.show', $post) }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">
                                                {{ $post->title }}
                                            </a>
                                        </h3>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $post->category?->name }} &middot; {{ $post->published_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($categories->isNotEmpty())
                    <section aria-labelledby="topics-heading">
                        <h2 id="topics-heading" class="mb-4 text-lg font-semibold tracking-tight">Popular topics</h2>
                        <ul class="flex flex-wrap gap-2">
                            @foreach($categories as $category)
                                <li>
                                    <a href="{{ route('categories.show', $category) }}"
                                       class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1 text-sm transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800">
                                        <span class="size-2 rounded-full" style="background-color: {{ $category->color ?? '#94a3b8' }}"></span>
                                        {{ $category->name }}
                                        <span class="text-xs text-gray-400">{{ $category->posts_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <x-newsletter.form />

                <x-ads.sidebar />
            </aside>
        </div>
    </div>
@endsection
