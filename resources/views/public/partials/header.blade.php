@php
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $logo = $siteSettings['site_logo'] ?? null;
    $activeCategoryId = request()->route('category')?->id;
@endphp

<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-950/90">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ $siteName }} home">
            {{-- This slot wants a square mark, not a wordmark: the site name is
                 rendered as live text beside it. Text beats a picture of text
                 here - it stays sharp at any zoom, it is selectable, and it
                 recolours itself in dark mode, which a flat PNG cannot. --}}
            @if($logo)
                <img src="{{ Storage::disk(config('site.media.disk'))->url($logo) }}" alt=""
                     class="size-8 shrink-0 rounded-lg object-contain" width="32" height="32">
            @else
                <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
                    <x-icon name="flame" class="size-5" />
                </span>
            @endif
            <span class="text-lg font-semibold tracking-tight">{{ $siteName }}</span>
        </a>

        <nav class="hidden flex-1 items-center gap-0.5 lg:flex" aria-label="Categories">
            <a href="{{ route('trending') }}"
               @class([
                   'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                   'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400' => request()->routeIs('trending'),
                   'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => ! request()->routeIs('trending'),
               ])>Trending</a>

            @foreach($nav as $category)
                <a href="{{ route('categories.show', $category) }}"
                   @class([
                       'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                       'bg-gray-100 dark:bg-gray-800' => $activeCategoryId === $category->id,
                       'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $activeCategoryId !== $category->id,
                   ])>{{ $category->name }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-1">
            {{-- Plain form first so search works without JavaScript; the island
                 upgrades it with live suggestions. --}}
            <div class="hidden sm:block" data-island="SearchBox" data-island-eager
                 data-props="{{ json_encode(['action' => route('search'), 'value' => request('q')]) }}">
                <form action="{{ route('search') }}" role="search">
                    <label for="search-desktop" class="sr-only">Search</label>
                    <input id="search-desktop" type="search" name="q" value="{{ request('q') }}" placeholder="Search"
                           class="w-44 rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900">
                </form>
            </div>

            <button
                type="button"
                data-theme-toggle
                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                aria-label="Toggle dark mode"
            >
                <span data-theme-icon="light"><x-icon name="moon" class="size-5" /></span>
                <span data-theme-icon="dark" hidden><x-icon name="sun" class="size-5" /></span>
            </button>

            <button
                type="button"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="mobile-nav"
                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
                aria-label="Open menu"
            >
                <x-icon name="menu" class="size-5" />
            </button>
        </div>
    </div>

    <div id="mobile-nav" data-nav-panel hidden class="border-t border-gray-200 lg:hidden dark:border-gray-800">
        <nav class="mx-auto max-w-6xl px-4 py-3 sm:px-6" aria-label="Categories">
            <form action="{{ route('search') }}" class="mb-3 sm:hidden" role="search">
                <label for="search-mobile" class="sr-only">Search</label>
                <input id="search-mobile" type="search" name="q" value="{{ request('q') }}" placeholder="Search"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </form>

            <ul class="grid grid-cols-2 gap-1">
                <li><a href="{{ route('trending') }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">Trending</a></li>
                <li><a href="{{ route('latest') }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">Latest</a></li>
                @foreach($nav as $category)
                    <li>
                        <a href="{{ route('categories.show', $category) }}"
                           class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ $category->name }}
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('categories.index') }}"
                       class="block rounded-lg px-3 py-2 text-sm font-medium text-brand-600 hover:bg-gray-100 dark:hover:bg-gray-800">
                        All categories
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
