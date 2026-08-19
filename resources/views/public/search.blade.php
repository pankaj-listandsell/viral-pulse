@extends('layouts.public')

@php
    $sections = app(\App\Services\ContentFeedService::class)->popularCategories(8);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">

        <x-breadcrumb :crumbs="[['name' => 'Search', 'url' => route('search')]]" />

        {{-- ======================= SEARCH HEADER ======================= --}}
        <header class="relative mb-10 overflow-hidden rounded-3xl border border-gray-200 bg-gray-50/70 p-6 sm:p-8 dark:border-gray-800 dark:bg-gray-900/40">
            <div class="pointer-events-none absolute -right-24 -top-28 size-72 rounded-full bg-brand-500/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative">
                <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                    @if($term !== '')
                        Results for &ldquo;{{ $term }}&rdquo;
                    @else
                        Search
                    @endif
                </h1>

                @if($results)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ number_format($results->total()) }} {{ Str::plural('result', $results->total()) }}
                        found across every published story.
                    </p>
                @else
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Search every article on the site — news, technology, entertainment, sport and explainers.
                    </p>
                @endif

                <form action="{{ route('search') }}" role="search" class="mt-6 flex max-w-xl gap-2">
                    <label for="search-page" class="sr-only">Search</label>
                    <input
                        id="search-page"
                        type="search"
                        name="q"
                        value="{{ $term }}"
                        placeholder="What are you looking for?"
                        autofocus
                        class="flex-1 rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950"
                    >
                    <button type="submit"
                            class="rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white transition hover:bg-brand-600 active:scale-95 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">
                        Search
                    </button>
                </form>

                @if($sections->isNotEmpty())
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Or browse</span>
                        @foreach($sections as $section)
                            <a href="{{ route('categories.show', $section) }}"
                               class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-gray-700 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:text-brand-400">
                                <span class="size-1.5 rounded-full" style="background-color: {{ $section->color ?? '#ef4444' }};" aria-hidden="true"></span>
                                {{ $section->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </header>

        {{-- ======================= RESULTS ======================= --}}
        @if($results === null)
            @if($popular->isNotEmpty())
                <section aria-labelledby="popular-heading">
                    <h2 id="popular-heading" class="mb-6 flex items-center gap-2.5 text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                        <span class="block h-6 w-1 rounded-full bg-brand-600" aria-hidden="true"></span>
                        Popular right now
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($popular as $post)
                            <x-post.card :post="$post" size="sm" />
                        @endforeach
                    </div>
                </section>
            @endif
        @elseif($results->isEmpty())
            <div class="rounded-3xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-800">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                    <x-icon name="search" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-black text-gray-900 dark:text-white">No results for &ldquo;{{ $term }}&rdquo;</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                    Try fewer words, or check the spelling.
                </p>
            </div>

            @if($popular->isNotEmpty())
                <section aria-labelledby="popular-heading" class="mt-14">
                    <h2 id="popular-heading" class="mb-6 flex items-center gap-2.5 text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                        <span class="block h-6 w-1 rounded-full bg-brand-600" aria-hidden="true"></span>
                        Popular right now
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($popular as $post)
                            <x-post.card :post="$post" size="sm" />
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($results as $post)
                    <x-post.card :post="$post" />
                @endforeach
            </div>

            @if($results->hasPages())
                <nav class="mt-12" aria-label="Pagination">{{ $results->links() }}</nav>
            @endif
        @endif
    </div>
@endsection
