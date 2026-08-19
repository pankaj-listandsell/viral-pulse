@php
    /**
     * An error page is still a visit. Sending it to a dead end with one button
     * loses the reader; a search box and the day's stories give them somewhere
     * to go. Kept deliberately light - no islands, no analytics - because this
     * page also renders when something is already going wrong.
     */
    $siteName = config('app.name');
    $recent = collect();
    $sections = collect();

    try {
        $feed = app(\App\Services\ContentFeedService::class);
        $recent = $feed->latest(4);
        $sections = $feed->popularCategories(6);
    } catch (\Throwable) {
        // A 500 caused by the database must not fail again while rendering.
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">

    <title>@yield('code') · {{ $siteName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full flex-col bg-white font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

<main class="mx-auto w-full max-w-4xl flex-1 px-4 py-14 sm:px-6">

    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-lg font-black tracking-tight">
        <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
            <x-icon name="flame" class="size-5" />
        </span>
        {{ $siteName }}
    </a>

    <div class="mt-12 border-b border-gray-200 pb-10 dark:border-gray-800">
        <p class="text-6xl font-black tracking-tight text-brand-600 sm:text-7xl dark:text-brand-500">@yield('code')</p>
        <h1 class="mt-4 text-2xl font-black tracking-tight sm:text-3xl">@yield('title')</h1>
        <p class="mt-2 max-w-xl text-sm text-gray-600 dark:text-gray-400">@yield('message')</p>

        <form action="{{ url('search') }}" role="search" class="mt-7 flex max-w-md gap-2">
            <label for="error-search" class="sr-only">Search</label>
            <input id="error-search" type="search" name="q" placeholder="Search for a story…"
                   class="flex-1 rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900">
            <button type="submit"
                    class="rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">
                Search
            </button>
        </form>

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ url('/') }}" class="rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-bold text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-400 dark:hover:text-brand-400">Home</a>
            <a href="{{ url('latest') }}" class="rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-bold text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-400 dark:hover:text-brand-400">Latest stories</a>
            <a href="{{ url('trending') }}" class="rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-bold text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-400 dark:hover:text-brand-400">Trending</a>
            @foreach($sections as $section)
                {{-- A "Trending" section would otherwise sit next to the Trending link above. --}}
                @continue(in_array($section->slug, ['trending', 'latest'], true))
                <a href="{{ url('category/'.$section->slug) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-bold text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-400 dark:hover:text-brand-400">
                    <span class="size-1.5 rounded-full" style="background-color: {{ $section->color ?? '#ef4444' }};" aria-hidden="true"></span>
                    {{ $section->name }}
                </a>
            @endforeach
        </div>
    </div>

    @if($recent->isNotEmpty())
        <section aria-labelledby="error-recent" class="mt-10">
            <h2 id="error-recent" class="text-sm font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Latest stories
            </h2>

            <ul class="mt-4 divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                @foreach($recent as $post)
                    <li class="group p-4 transition hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <a href="{{ url('post/'.$post->slug) }}" class="block">
                            @if($post->category)
                                <span class="text-[10px] font-black uppercase tracking-wider" style="color: {{ $post->category->color ?? '#ef4444' }};">
                                    {{ $post->category->name }}
                                </span>
                            @endif
                            <p class="mt-1 text-sm font-bold leading-snug transition group-hover:text-brand-600 dark:group-hover:text-brand-400">
                                {{ $post->title }}
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</main>

</body>
</html>
