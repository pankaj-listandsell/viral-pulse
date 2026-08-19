@extends('layouts.public')

@php
    $accentColor = $accent ?? null;
    $sections = app(\App\Services\ContentFeedService::class)->popularCategories(10);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">

        <x-breadcrumb :crumbs="$crumbs ?? []" />

        {{-- ======================= HEADER ======================= --}}
        <header class="relative mb-10 overflow-hidden rounded-3xl border border-gray-200 bg-gray-50/70 p-6 sm:p-8 dark:border-gray-800 dark:bg-gray-900/40">
            @if($accentColor)
                <span class="absolute inset-x-0 top-0 h-1.5" style="background-color: {{ $accentColor }};" aria-hidden="true"></span>
                <span class="pointer-events-none absolute -right-20 -top-24 size-64 rounded-full opacity-20 blur-3xl"
                      style="background-color: {{ $accentColor }};" aria-hidden="true"></span>
            @endif

            <div class="relative">
                <h1 class="flex items-center gap-3 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                    @if($accentColor)
                        <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $accentColor }};" aria-hidden="true"></span>
                    @endif
                    {{ $heading }}
                </h1>

                @if(! empty($subheading))
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-gray-600 sm:text-base dark:text-gray-400">{{ $subheading }}</p>
                @endif

                <p class="mt-4 flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <span class="rounded-full bg-white px-3 py-1 shadow-sm dark:bg-gray-900">
                        {{ number_format($posts->total()) }} {{ Str::plural('story', $posts->total()) }}
                    </span>

                    @if($posts->isNotEmpty() && $posts->first()->published_at)
                        <span class="rounded-full bg-white px-3 py-1 shadow-sm dark:bg-gray-900">
                            Updated <time datetime="{{ $posts->first()->published_at->toDateString() }}">{{ $posts->first()->published_at->diffForHumans() }}</time>
                        </span>
                    @endif

                    @isset($seo['feed'])
                        <a href="{{ $seo['feed'] }}" class="rounded-full bg-white px-3 py-1 text-brand-600 shadow-sm transition hover:text-brand-700 dark:bg-gray-900 dark:text-brand-400">
                            RSS feed
                        </a>
                    @endisset
                </p>
            </div>
        </header>

        <x-ads.header />

        {{-- ======================= POSTS ======================= --}}
        @if($posts->isEmpty())
            <div class="rounded-3xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-800">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                    <x-icon name="file-text" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-black text-gray-900 dark:text-white">Nothing here yet</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                    No stories have been published in this section so far. Try one of the sections below.
                </p>
                <a href="{{ route('latest') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">
                    Read the latest stories <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $index => $post)
                    <x-post.card :post="$post" :eager="$index < 3" />
                @endforeach
            </div>

            @if($posts->hasPages())
                <nav class="mt-12" aria-label="Pagination">{{ $posts->links() }}</nav>
            @endif
        @endif

        {{-- ======================= OTHER SECTIONS ======================= --}}
        @if($sections->isNotEmpty())
            <nav aria-label="Other sections" class="mt-14 rounded-2xl border border-gray-200 bg-gray-50/60 p-5 dark:border-gray-800 dark:bg-gray-900/30">
                <h2 class="text-sm font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">Keep reading</h2>

                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach($sections as $section)
                        <li>
                            <a href="{{ route('categories.show', $section) }}"
                               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 transition hover:-translate-y-0.5 hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-brand-500 dark:hover:text-brand-400">
                                <span class="size-2 rounded-full" style="background-color: {{ $section->color ?? '#ef4444' }};" aria-hidden="true"></span>
                                {{ $section->name }}
                                <span class="text-[10px] font-black text-gray-400 dark:text-gray-500">{{ $section->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <x-newsletter.form class="mt-14 max-w-xl" />
    </div>
@endsection
