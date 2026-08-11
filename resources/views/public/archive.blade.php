@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <header class="mb-8 border-b border-gray-200 pb-6 dark:border-gray-800">
            <h1 class="flex items-center gap-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                @isset($accent)
                    <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $accent ?? '#94a3b8' }}"></span>
                @endisset
                {{ $heading }}
            </h1>

            @if(! empty($subheading))
                <p class="mt-2 max-w-2xl text-gray-600 dark:text-gray-400">{{ $subheading }}</p>
            @endif

            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                {{ number_format($posts->total()) }} {{ Str::plural('story', $posts->total()) }}
            </p>
        </header>

        <x-ads.header />

        @if($posts->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
                <span class="grid size-12 place-items-center rounded-full bg-gray-100 text-gray-400 mx-auto dark:bg-gray-800">
                    <x-icon name="file-text" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold">Nothing here yet</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                    No stories have been published in this section so far.
                </p>
                <a href="{{ route('home') }}" class="mt-5 inline-block text-sm font-medium text-brand-600 hover:text-brand-700">
                    Back to home
                </a>
            </div>
        @else
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $index => $post)
                    <x-post.card :post="$post" :eager="$index < 3" />
                @endforeach
            </div>

            @if($posts->hasPages())
                <nav class="mt-12" aria-label="Pagination">{{ $posts->links() }}</nav>
            @endif
        @endif

        <x-newsletter.form class="mt-14 max-w-xl" />
    </div>
@endsection
