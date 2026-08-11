@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <header class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                @if($term !== '')
                    Results for &ldquo;{{ $term }}&rdquo;
                @else
                    Search
                @endif
            </h1>

            @if($results)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ number_format($results->total()) }} {{ Str::plural('result', $results->total()) }}
                </p>
            @endif
        </header>

        <form action="{{ route('search') }}" role="search" class="mb-10 flex max-w-xl gap-2">
            <label for="search-page" class="sr-only">Search</label>
            <input
                id="search-page"
                type="search"
                name="q"
                value="{{ $term }}"
                placeholder="What are you looking for?"
                autofocus
                class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-900"
            >
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700">
                Search
            </button>
        </form>

        @if($results === null)
            <p class="text-gray-600 dark:text-gray-400">Type something above to search every article on the site.</p>
        @elseif($results->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
                <span class="grid size-12 place-items-center rounded-full bg-gray-100 text-gray-400 mx-auto dark:bg-gray-800">
                    <x-icon name="search" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold">No results for &ldquo;{{ $term }}&rdquo;</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                    Try fewer words, or check the spelling.
                </p>
            </div>

            @if($popular->isNotEmpty())
                <section aria-labelledby="popular-heading" class="mt-12">
                    <h2 id="popular-heading" class="mb-5 text-lg font-semibold tracking-tight">Popular right now</h2>
                    <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($popular as $post)
                            <x-post.card :post="$post" size="sm" />
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
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
