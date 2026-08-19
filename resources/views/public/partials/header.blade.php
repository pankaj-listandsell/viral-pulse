@php
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $logo = $siteSettings['site_logo'] ?? null;
    $activeCategoryId = request()->route('category')?->id;
    $horoscopeEnabled = $siteSettings['horoscope_enabled'] ?? true;
@endphp

<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/85 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/85">

    {{-- Row one: identity, search, controls. --}}
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ $siteName }} home">
            {{-- This slot wants a square mark, not a wordmark: the site name is
                 rendered as live text beside it. Text beats a picture of text
                 here - it stays sharp at any zoom, it is selectable, and it
                 recolours itself in dark mode, which a flat PNG cannot. --}}
            @if($logo)
                <img src="{{ Storage::disk(config('site.media.disk'))->url($logo) }}" alt=""
                     class="size-9 shrink-0 rounded-xl object-contain" width="36" height="36">
            @else
                <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm shadow-brand-600/30">
                    <x-icon name="flame" class="size-5" />
                </span>
            @endif
            <span class="text-xl font-black tracking-tight">{{ $siteName }}</span>
        </a>

        <div class="ml-auto flex items-center gap-1">
            {{-- Plain form first so search works without JavaScript; the island
                 upgrades it with live suggestions. --}}
            <div class="hidden sm:block" data-island="SearchBox" data-island-eager
                 data-props="{{ json_encode(['action' => route('search'), 'value' => request('q')]) }}">
                <form action="{{ route('search') }}" role="search">
                    <label for="search-desktop" class="sr-only">Search</label>
                    <input id="search-desktop" type="search" name="q" value="{{ request('q') }}" placeholder="Search stories"
                           class="w-48 rounded-xl border border-gray-300 bg-gray-50 px-3.5 py-2 text-sm transition focus:w-60 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900">
                </form>
            </div>

            <div data-island="SavedStoriesDrawer" data-island-eager></div>

            <button
                type="button"
                data-theme-toggle
                class="rounded-xl p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
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
                class="rounded-xl p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
                aria-label="Open menu"
            >
                <x-icon name="menu" class="size-5" />
            </button>
        </div>
    </div>

    {{-- Row two: the sections. On its own line the nav has room for every
         category, which is what removed the duplicate category strip the front
         page used to carry underneath. --}}
    <nav class="hidden border-t border-gray-100 lg:block dark:border-gray-800/70" aria-label="Sections">
        <ul class="mx-auto flex max-w-6xl items-center gap-1 px-4 sm:px-6">
            @php
                $primary = collect([
                    ['label' => 'Latest', 'url' => route('latest'), 'active' => request()->routeIs('latest')],
                    ['label' => 'Trending', 'url' => route('trending'), 'active' => request()->routeIs('trending')],
                ]);
            @endphp

            <li>
                <a href="{{ route('home') }}"
                   @class([
                       'relative block px-3 py-2.5 text-sm font-bold transition',
                       'text-brand-600 dark:text-brand-400' => request()->routeIs('home'),
                       'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => ! request()->routeIs('home'),
                   ])
                   @if(request()->routeIs('home')) aria-current="page" @endif>
                    Home
                    @if(request()->routeIs('home'))
                        <span class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-brand-600 dark:bg-brand-400"></span>
                    @endif
                </a>
            </li>

            @foreach($primary as $item)
                <li>
                    <a href="{{ $item['url'] }}"
                       @class([
                           'relative block px-3 py-2.5 text-sm font-bold transition',
                           'text-brand-600 dark:text-brand-400' => $item['active'],
                           'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => ! $item['active'],
                       ])
                       @if($item['active']) aria-current="page" @endif>
                        {{ $item['label'] }}
                        @if($item['active'])
                            <span class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-brand-600 dark:bg-brand-400"></span>
                        @endif
                    </a>
                </li>
            @endforeach

            @foreach($nav as $category)
                @php $isActive = $activeCategoryId === $category->id; @endphp
                <li>
                    <a href="{{ route('categories.show', $category) }}"
                       @class([
                           'relative block px-3 py-2.5 text-sm font-medium transition',
                           'text-gray-900 dark:text-white' => $isActive,
                           'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => ! $isActive,
                       ])
                       @if($isActive) aria-current="page" @endif>
                        {{ $category->name }}
                        @if($isActive)
                            <span class="absolute inset-x-2 -bottom-px h-0.5 rounded-full" style="background-color: {{ $category->color ?? '#ef4444' }};"></span>
                        @endif
                    </a>
                </li>
            @endforeach

            @if($horoscopeEnabled)
                <li class="ml-auto">
                    <a href="{{ route('horoscope') }}"
                       @class([
                           'flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-bold transition',
                           'bg-violet-600 text-white' => request()->routeIs('horoscope*'),
                           'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-500/15 dark:text-violet-300 dark:hover:bg-violet-500/25' => ! request()->routeIs('horoscope*'),
                       ])
                       @if(request()->routeIs('horoscope')) aria-current="page" @endif>
                        <span aria-hidden="true">✨</span> Horoscope
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    {{-- Mobile panel --}}
    <div id="mobile-nav" data-nav-panel hidden class="border-t border-gray-200 lg:hidden dark:border-gray-800">
        <nav class="mx-auto max-w-6xl px-4 py-4 sm:px-6" aria-label="Sections">
            <form action="{{ route('search') }}" class="mb-4 sm:hidden" role="search">
                <label for="search-mobile" class="sr-only">Search</label>
                <input id="search-mobile" type="search" name="q" value="{{ request('q') }}" placeholder="Search stories"
                       class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
            </form>

            @if($horoscopeEnabled)
                <div class="mb-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('horoscope') }}"
                       class="flex items-center justify-center gap-1.5 rounded-xl bg-violet-50 px-3 py-2.5 text-sm font-black text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                        <span aria-hidden="true">✨</span> Horoscope
                    </a>
                    <a href="{{ route('horoscope.compatibility') }}"
                       class="flex items-center justify-center gap-1.5 rounded-xl bg-rose-50 px-3 py-2.5 text-sm font-black text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                        <span aria-hidden="true">💖</span> Love match
                    </a>
                </div>
            @endif

            <p class="mb-2 text-[11px] font-black uppercase tracking-wider text-gray-400">Sections</p>
            <ul class="grid grid-cols-2 gap-1">
                <li><a href="{{ route('latest') }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">Latest</a></li>
                <li><a href="{{ route('trending') }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">Trending</a></li>
                @foreach($nav as $category)
                    <li>
                        <a href="{{ route('categories.show', $category) }}"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">
                            <span class="size-1.5 rounded-full" style="background-color: {{ $category->color ?? '#ef4444' }};" aria-hidden="true"></span>
                            {{ $category->name }}
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('categories.index') }}"
                       class="block rounded-lg px-3 py-2 text-sm font-bold text-brand-600 hover:bg-gray-100 dark:hover:bg-gray-800">
                        All categories
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
