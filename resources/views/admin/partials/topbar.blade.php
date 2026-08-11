<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white/80 px-4 backdrop-blur
               dark:border-gray-800 dark:bg-gray-900/80 sm:px-6 lg:px-8">

    <button
        type="button"
        x-data
        @click="$store.sidebar.toggle()"
        class="-ml-1 rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
        aria-label="Toggle navigation"
    >
        <x-icon name="menu" class="size-5" />
    </button>

    <div class="flex-1"></div>

    <a
        href="{{ route('home') }}"
        target="_blank"
        rel="noopener"
        class="hidden items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 transition
               hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 sm:inline-flex"
    >
        <x-icon name="external-link" class="size-4" />
        View site
    </a>

    <button
        type="button"
        x-data
        @click="$store.theme.toggle()"
        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
        :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <span x-show="!$store.theme.dark"><x-icon name="moon" class="size-5" /></span>
        <span x-show="$store.theme.dark" x-cloak><x-icon name="sun" class="size-5" /></span>
    </button>

    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
        <button
            type="button"
            @click="open = !open"
            class="flex items-center gap-2 rounded-lg p-1.5 pr-2 transition hover:bg-gray-100 dark:hover:bg-gray-800"
            :aria-expanded="open"
            aria-haspopup="menu"
        >
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700
                         dark:bg-brand-500/20 dark:text-brand-300">
                {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
            </span>
            <span class="hidden text-sm font-medium sm:block">{{ auth()->user()->name }}</span>
            <x-icon name="chevron-down" class="size-4 text-gray-400" />
        </button>

        <div
            x-show="open"
            x-transition.origin.top.right
            @click.outside="open = false"
            class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg
                   dark:border-gray-800 dark:bg-gray-900"
            role="menu"
            x-cloak
        >
            <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-800">
                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
            </div>

            <a href="{{ route('admin.profile.edit') }}"
               class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50
                      dark:text-gray-200 dark:hover:bg-gray-800"
               role="menuitem">
                <x-icon name="user" class="size-4" />
                Profile
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-600 transition hover:bg-red-50
                               dark:text-red-400 dark:hover:bg-red-500/10"
                        role="menuitem">
                    <x-icon name="log-out" class="size-4" />
                    Sign out
                </button>
            </form>
        </div>
    </div>
</header>
