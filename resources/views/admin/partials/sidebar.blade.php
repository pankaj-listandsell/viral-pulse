@php
    /**
     * Route::has() keeps this list honest while later phases are still being
     * built: a link only appears once its route actually exists.
     */
    $groups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
            ],
        ],
        [
            'label' => 'Content',
            'items' => [
                ['route' => 'admin.posts.index', 'label' => 'Posts', 'icon' => 'file-text'],
                ['route' => 'admin.posts.create', 'label' => 'Create Post', 'icon' => 'file-plus'],
                ['route' => 'admin.categories.index', 'label' => 'Categories', 'icon' => 'folder'],
                ['route' => 'admin.tags.index', 'label' => 'Tags', 'icon' => 'tags'],
                ['route' => 'admin.media.index', 'label' => 'Media Library', 'icon' => 'image'],
            ],
        ],
        [
            'label' => 'Automation',
            'items' => [
                ['route' => 'admin.trending.index', 'label' => 'Trending Topics', 'icon' => 'flame'],
                ['route' => 'admin.ai.index', 'label' => 'AI Generator', 'icon' => 'sparkles'],
                ['route' => 'admin.scheduled.index', 'label' => 'Scheduled Posts', 'icon' => 'calendar-clock'],
            ],
        ],
        [
            'label' => 'Audience',
            'items' => [
                ['route' => 'admin.messages.index', 'label' => 'Contact Messages', 'icon' => 'inbox'],
            ],
        ],
        [
            'label' => 'System',
            'items' => [
                ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'users'],
                ['route' => 'admin.seo.edit', 'label' => 'SEO Settings', 'icon' => 'search'],
                ['route' => 'admin.settings.edit', 'label' => 'Site Settings', 'icon' => 'settings'],
                ['route' => 'admin.activity.index', 'label' => 'Activity Logs', 'icon' => 'scroll-text'],
            ],
        ],
    ];
@endphp

<aside
    x-data
    class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-gray-200 bg-white transition-all duration-200
           dark:border-gray-800 dark:bg-gray-900
           lg:translate-x-0"
    :class="[
        $store.sidebar.open ? 'translate-x-0' : '-translate-x-full',
        $store.sidebar.collapsed ? 'w-16' : 'w-64',
    ]"
>
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-gray-200 px-4 dark:border-gray-800">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-brand-600 text-white">
                <x-icon name="flame" class="size-5" />
            </span>
            <span x-show="!$store.sidebar.collapsed" class="truncate font-semibold tracking-tight" x-cloak>
                {{ $siteSettings['site_name'] ?? config('app.name') }}
            </span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="Admin">
        @foreach($groups as $group)
            @php
                $visible = collect($group['items'])->filter(fn ($item) => Route::has($item['route']));
            @endphp

            @if($visible->isNotEmpty())
                @if($group['label'])
                    <p
                        x-show="!$store.sidebar.collapsed"
                        class="mt-5 mb-1.5 px-3 text-[0.68rem] font-semibold tracking-wider text-gray-400 uppercase dark:text-gray-500"
                        x-cloak
                    >{{ $group['label'] }}</p>
                @endif

                <ul class="space-y-0.5">
                    @foreach($visible as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @class([
                                    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                    'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400' => $active,
                                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => ! $active,
                                ])
                                @if($active) aria-current="page" @endif
                                :title="$store.sidebar.collapsed ? '{{ $item['label'] }}' : null"
                            >
                                <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                                <span x-show="!$store.sidebar.collapsed" class="truncate" x-cloak>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-gray-200 p-2 dark:border-gray-800">
        <button
            type="button"
            @click="$store.sidebar.toggleCollapsed()"
            class="hidden w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-500
                   transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:flex"
        >
            <x-icon name="panel-left" class="size-5 shrink-0" />
            <span x-show="!$store.sidebar.collapsed" x-cloak>Collapse</span>
        </button>
    </div>
</aside>
