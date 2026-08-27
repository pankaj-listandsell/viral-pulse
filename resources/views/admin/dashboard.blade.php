@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Everything happening across the site right now.')

@section('actions')
    @if(Route::has('admin.ai.index'))
        <x-ui.button :href="route('admin.ai.index')">
            <x-icon name="sparkles" class="size-4" />
            Generate content
        </x-ui.button>
    @endif
@endsection

@section('content')
<div x-data="{ activeTab: 'overview' }">
    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button @click="activeTab = 'overview'"
                :class="activeTab === 'overview' ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold transition flex items-center gap-2 focus:outline-none">
                <x-icon name="layout-dashboard" class="size-4" />
                Overview
            </button>
            <button @click="activeTab = 'performance'"
                :class="activeTab === 'performance' ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold transition flex items-center gap-2 focus:outline-none">
                <x-icon name="trending-up" class="size-4" />
                Posting Performance
            </button>
            <button @click="activeTab = 'google'"
                :class="activeTab === 'google' ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold transition flex items-center gap-2 focus:outline-none">
                <x-icon name="globe" class="size-4" />
                Google Integration
            </button>
        </nav>
    </div>

    <!-- Overview Tab Content -->
    <div x-show="activeTab === 'overview'">
        {{-- Two rows, not one grid of eight identical boxes.
             What the site is doing comes first and links somewhere; who is reading
             it comes second. "Total posts" is gone: it repeated Published plus the
             two counts beside it, so it spent a card restating its neighbours. --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-ui.stat-card
                label="Published"
                :value="$stats['published_posts']"
                icon="check"
                color="green"
                :href="Route::has('admin.posts.index') ? route('admin.posts.index', ['status' => 'published']) : null"
                :hint="$stats['total_posts'].' in total'"
            />
            <x-ui.stat-card
                label="Drafts"
                :value="$stats['draft_posts']"
                icon="pencil"
                color="amber"
                :href="Route::has('admin.posts.index') ? route('admin.posts.index', ['status' => 'draft']) : null"
                :hint="$stats['draft_posts'] > 0 ? 'Waiting for review' : 'Nothing waiting'"
            />
            <x-ui.stat-card
                label="Scheduled"
                :value="$stats['scheduled_posts']"
                icon="calendar-clock"
                color="blue"
                :href="Route::has('admin.scheduled.index') ? route('admin.scheduled.index') : null"
                :hint="$stats['scheduled_posts'] > 0 ? 'Queued to publish' : 'Nothing queued'"
            />
            <x-ui.stat-card
                label="AI generated"
                :value="$stats['ai_posts']"
                icon="bot"
                color="violet"
                :href="Route::has('admin.ai.index') ? route('admin.ai.index') : null"
                :hint="$stats['ai_generations'].' generations run'"
            />
        </div>

        {{-- Audience, as one quiet strip rather than three more cards. These
             numbers are context for the ones above, not headlines of their own,
             and giving them the same weight flattened the whole screen. --}}
        <div class="mt-4 grid grid-cols-3 divide-x divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
            @foreach([
                ['label' => 'Total views', 'value' => $stats['total_views'], 'icon' => 'eye'],
                ['label' => 'Subscribers', 'value' => $stats['subscribers'], 'icon' => 'mail'],
                ['label' => 'Users', 'value' => $stats['users'], 'icon' => 'users', 'href' => Route::has('admin.users.index') ? route('admin.users.index') : null],
            ] as $item)
                @php $tag = ($item['href'] ?? null) ? 'a' : 'div'; @endphp

                <{{ $tag }} @if($item['href'] ?? null) href="{{ $item['href'] }}" @endif
                    class="flex items-center gap-3 px-4 py-3.5 transition {{ ($item['href'] ?? null) ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : '' }}">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <x-icon :name="$item['icon']" class="size-4.5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                        <span @class([
                            'block text-xl font-black tabular-nums tracking-tight',
                            'text-gray-900 dark:text-gray-50' => (int) $item['value'] !== 0,
                            'text-gray-300 dark:text-gray-700' => (int) $item['value'] === 0,
                        ])>{{ number_format($item['value']) }}</span>
                    </span>
                </{{ $tag }}>
            @endforeach
        </div>

        @if($stats['pending_comments'] > 0 && Route::has('admin.comments.index'))
            <a href="{{ route('admin.comments.index') }}"
               class="mt-4 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm
                      transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:hover:bg-amber-500/15">
                <x-icon name="message-square" class="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <span class="text-amber-900 dark:text-amber-200">
                    <strong>{{ $stats['pending_comments'] }}</strong>
                    {{ Str::plural('comment', $stats['pending_comments']) }} waiting for moderation
                </span>
                <x-icon name="chevron-right" class="ml-auto size-4 text-amber-600 dark:text-amber-400" />
            </a >
        @endif

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Posts published" subtitle="Last 30 days">
                <x-ui.chart :points="$postsPerDay" type="bar" label="Posts" />
            </x-ui.card>

            <x-ui.card title="Views" subtitle="Last 30 days">
                @if(collect($viewsPerDay)->sum('total') > 0)
                    <x-ui.chart :points="$viewsPerDay" label="Views" />
                @else
                    <x-ui.empty-state
                        icon="chart-column"
                        title="No view data yet"
                        description="Daily view totals appear here once the stats:aggregate command has run."
                    />
                @endif
            </x-ui.card>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <x-ui.card title="Top categories" class="lg:col-span-1">
                @forelse($topCategories as $category)
                    <div class="flex items-center gap-3 py-2 first:pt-0 last:pb-0">
                        <span class="size-2.5 shrink-0 rounded-full"
                              style="background-color: {{ $category->color ?? '#94a3b8' }}"></span>
                        <span class="min-w-0 flex-1 truncate text-sm">{{ $category->name }}</span>
                        <span class="shrink-0 text-sm font-medium tabular-nums text-gray-500 dark:text-gray-400">
                            {{ $category->posts_count }}
                        </span>
                    </div>
                @empty
                    <x-ui.empty-state icon="folder" title="No categories yet" />
                @endforelse
            </x-ui.card>

            <x-ui.card title="Most viewed posts" class="lg:col-span-2">
                @forelse($topPosts as $post)
                    <div class="flex items-center gap-3 py-2 first:pt-0 last:pb-0">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $post->title }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $post->category?->name }} · {{ $post->published_at?->diffForHumans() }}
                            </span>
                        </span>
                        <span class="flex shrink-0 items-center gap-1.5 text-sm tabular-nums text-gray-500 dark:text-gray-400">
                            <x-icon name="eye" class="size-4" />
                            {{ number_format($post->views_count) }}
                        </span>
                    </div>
                @empty
                    <x-ui.empty-state
                        icon="file-text"
                        title="Nothing published yet"
                        description="Publish your first post to start collecting view data."
                    />
                @endforelse
            </x-ui.card>
        </div>

        <x-ui.card title="Recently updated" class="mt-4" :padded="false">
            @if($recentPosts->isEmpty())
                <x-ui.empty-state icon="file-text" title="No posts yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500
                                      dark:border-gray-800 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Title</th>
                                <th scope="col" class="px-5 py-3 font-medium">Category</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Author</th>
                                <th scope="col" class="px-5 py-3 font-medium">Status</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($recentPosts as $post)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="max-w-xs px-5 py-3">
                                        <span class="flex items-center gap-2">
                                            <span class="truncate font-medium">{{ $post->title }}</span>
                                            @if($post->ai_generated)
                                                <x-ui.badge color="violet">
                                                    <x-icon name="bot" class="size-3" />
                                                    AI
                                                </x-ui.badge>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $post->category?->name }}</td>
                                    <td class="hidden px-5 py-3 text-gray-500 dark:text-gray-400 sm:table-cell">
                                        {{ $post->author?->name }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-ui.badge :color="$post->status->color()">{{ $post->status->label() }}</x-ui.badge>
                                    </td>
                                    <td class="hidden px-5 py-3 text-gray-500 dark:text-gray-400 md:table-cell">
                                        {{ $post->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Posting Performance Tab Content -->
    <div x-show="activeTab === 'performance'" style="display: none;">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-ui.card title="Best Posting Hours" subtitle="Total view distribution by publication hour of day">
                    @if(collect($viewsByPublishHour)->sum('total') > 0)
                        <x-ui.chart :points="$viewsByPublishHour" type="bar" label="Views" format="g A" />
                    @else
                        <x-ui.empty-state
                            icon="clock"
                            title="No view data by hour"
                            description="Hourly publishing performance will appear here once posts start getting views."
                        />
                    @endif
                </x-ui.card>
            </div>
            <div class="lg:col-span-1">
                <x-ui.card title="Hourly Peak Analysis" subtitle="Performance breakdown">
                    <div class="space-y-4">
                        @php
                            $maxHour = collect($viewsByPublishHour)->sortByDesc('total')->first();
                            $totalHourViews = collect($viewsByPublishHour)->sum('total');
                        @endphp
                        
                        @if($totalHourViews > 0)
                            <div class="rounded-xl bg-brand-50 p-4 dark:bg-brand-950/20 border border-brand-100 dark:border-brand-500/10">
                                <span class="block text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">Peak Publishing Hour</span>
                                <span class="text-2xl font-black text-brand-900 dark:text-brand-100">
                                    {{ \Illuminate\Support\Carbon::parse($maxHour['date'])->format('g A') }}
                                </span>
                                <span class="block text-xs mt-1 text-gray-500 dark:text-gray-400">
                                    This hour generated {{ number_format($maxHour['total']) }} views ({{ round(($maxHour['total'] / $totalHourViews) * 100, 1) }}% of total traffic).
                                </span>
                            </div>
                        @else
                            <div class="text-sm text-gray-500">Peak data is currently unavailable.</div>
                        @endif

                        <div class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            <p class="font-bold mb-1">💡 Editorial Recommendation:</p>
                            Publishing new articles close to peak traffic hours can improve index speed and immediate click-through rates.
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-ui.card title="Views by Category" subtitle="Total traffic generated per category">
                    @if(collect($viewsByCategory)->sum('total') > 0)
                        <x-ui.chart :points="$viewsByCategory" type="bar" label="Views" :rawLabels="true" />
                    @else
                        <x-ui.empty-state
                            icon="folder"
                            title="No category traffic yet"
                            description="Category view totals appear here once categories collect views."
                        />
                    @endif
                </x-ui.card>
            </div>
            <div class="lg:col-span-1">
                <x-ui.card title="Category Performance Rankings" subtitle="Sorted by total views">
                    <div class="space-y-3">
                        @forelse($viewsByCategory as $index => $cat)
                            <div class="flex items-center gap-3 py-1">
                                <span class="font-black text-xs text-gray-400 dark:text-gray-600 w-6">#{{ $index + 1 }}</span>
                                <span class="size-2.5 shrink-0 rounded-full"
                                      style="background-color: {{ $cat['color'] }}"></span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $cat['label'] }}</span>
                                <span class="shrink-0 text-sm font-bold tabular-nums text-gray-900 dark:text-gray-100">
                                    {{ number_format($cat['total']) }}
                                </span>
                            </div>
                        @empty
                            <x-ui.empty-state icon="folder" title="No traffic stats" />
                        @endforelse
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>

    <!-- Google Integration Tab Content -->
    <div x-show="activeTab === 'google'" style="display: none;">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Google Analytics Card -->
            <div class="rounded-2xl border p-5 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-500">Google Analytics 4</span>
                        <span class="text-lg font-black mt-1 block">
                            @if($googleReports['is_analytics_connected'])
                                Connected
                            @else
                                Not Connected
                            @endif
                        </span>
                        <span class="text-xs text-gray-500 mt-1 block">
                            @if($googleReports['is_analytics_connected'])
                                Property ID: <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded font-mono text-[10px]">{{ $googleReports['analytics_id'] }}</code>
                            @else
                                Enter your GA4 tag (G-XXXXX) in settings to track page views.
                            @endif
                        </span>
                    </div>
                    <span @class([
                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold',
                        'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400' => $googleReports['is_analytics_connected'],
                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' => !$googleReports['is_analytics_connected'],
                    ])>
                        <span class="size-1.5 rounded-full bg-current"></span>
                        {{ $googleReports['is_analytics_connected'] ? 'Active' : 'Setup Required' }}
                    </span>
                </div>
                @if(!$googleReports['is_analytics_connected'] && Route::has('admin.settings.index'))
                    <div class="mt-4 border-t pt-3 flex justify-end">
                        <a href="{{ route('admin.settings.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                            Configure Google Analytics &rarr;
                        </a>
                    </div>
                @endif
            </div>

            <!-- Google Search Console Card -->
            <div class="rounded-2xl border p-5 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-500">Google Search Console</span>
                        <span class="text-lg font-black mt-1 block">
                            @if($googleReports['is_gsc_connected'])
                                Verified
                            @else
                                Not Verified
                            @endif
                        </span>
                        <span class="text-xs text-gray-500 mt-1 block">
                            @if($googleReports['is_gsc_connected'])
                                Verification Token: <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded font-mono text-[10px] truncate max-w-[150px] inline-block align-middle">{{ Str::limit($googleReports['verification_id'], 20) }}</code>
                            @else
                                Verify site ownership in Settings to monitor search traffic.
                            @endif
                        </span>
                    </div>
                    <span @class([
                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold',
                        'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400' => $googleReports['is_gsc_connected'],
                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' => !$googleReports['is_gsc_connected'],
                    ])>
                        <span class="size-1.5 rounded-full bg-current"></span>
                        {{ $googleReports['is_gsc_connected'] ? 'Active' : 'Setup Required' }}
                    </span>
                </div>
                @if(!$googleReports['is_gsc_connected'] && Route::has('admin.settings.index'))
                    <div class="mt-4 border-t pt-3 flex justify-end">
                        <a href="{{ route('admin.settings.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                            Configure Verification Code &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-ui.stat-card
                label="Search Clicks"
                :value="$googleReports['gsc_clicks']"
                icon="eye"
                color="blue"
                hint="From organic Google Search"
            />
            <x-ui.stat-card
                label="Search Impressions"
                :value="$googleReports['gsc_impressions']"
                icon="sparkles"
                color="violet"
                hint="Google Search impressions"
            />
            <x-ui.stat-card
                label="Average CTR"
                :value="$googleReports['gsc_ctr'] . '%'"
                icon="trending-up"
                color="green"
                hint="Click-through rate"
            />
            <x-ui.stat-card
                label="Average Position"
                :value="$googleReports['gsc_position']"
                icon="star"
                color="amber"
                hint="Google Search ranking"
            />
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-ui.card title="Google Search Performance" subtitle="Organic search clicks (Last 14 days)">
                    @php
                        $searchClicksPoints = collect($googleReports['search_performance'])->map(fn($p) => ['date' => $p['date'], 'total' => $p['clicks']]);
                    @endphp
                    <x-ui.chart :points="$searchClicksPoints" label="Search Clicks" color="#2563eb" />
                </x-ui.card>
            </div>

            <div class="lg:col-span-1">
                <x-ui.card title="Realtime Audience Overview" subtitle="Live active users & device share">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="flex size-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full size-3 bg-green-500"></span>
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($googleReports['realtime_users']) }} active users on site
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Device Distribution</span>
                            <div class="space-y-2">
                                @foreach($googleReports['devices'] as $dev)
                                    <div>
                                        <div class="flex items-center justify-between text-xs font-medium mb-1">
                                            <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                                                <x-icon :name="$dev['icon']" class="size-3.5" />
                                                {{ $dev['name'] }}
                                            </span>
                                            <span class="text-gray-900 dark:text-gray-100 font-bold">{{ $dev['percentage'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-brand-600 h-full" style="width: {{ $dev['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <x-ui.card title="Top Countries by Traffic" subtitle="Audience geographical distribution">
                    <div class="space-y-3">
                        @foreach($googleReports['countries'] as $country)
                            <div class="flex items-center justify-between py-1 border-b last:border-0 border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded uppercase">
                                        {{ $country['code'] }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $country['name'] }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm font-bold text-gray-900 dark:text-gray-100">{{ number_format($country['views']) }}</span>
                                    <span class="block text-[10px] text-gray-500 dark:text-gray-400">{{ $country['percentage'] }}% of views</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div class="lg:col-span-2">
                <x-ui.card title="Google Webmasters Tools & Resources" subtitle="Open official consoles directly">
                    <div class="space-y-4 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        <p>
                            While the dashboard displays key metrics based on site performance, you can access complete detailed reports, inspect indexes, submit sitemaps, and debug crawl errors directly inside the official Google portals.
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-2">
                            <a href="https://analytics.google.com/" target="_blank" rel="noopener"
                               class="flex items-start gap-3 rounded-xl border p-4 bg-gray-50/50 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-900/50 dark:hover:bg-gray-800/50">
                                <x-icon name="external-link" class="size-5 shrink-0 text-brand-600 dark:text-brand-400" />
                                <div>
                                    <strong class="block text-gray-900 dark:text-gray-100 font-bold">Google Analytics Console</strong>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">View live conversions, demographic reports, and user flow metrics.</span>
                                </div>
                            </a>

                            <a href="https://search.google.com/search-console" target="_blank" rel="noopener"
                               class="flex items-start gap-3 rounded-xl border p-4 bg-gray-50/50 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-900/50 dark:hover:bg-gray-800/50">
                                <x-icon name="external-link" class="size-5 shrink-0 text-brand-600 dark:text-brand-400" />
                                <div>
                                    <strong class="block text-gray-900 dark:text-gray-100 font-bold">Google Search Console</strong>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Submit sitemaps, monitor keywords ranking, and verify crawl indexing.</span>
                                </div>
                            </a>
                        </div>

                        <div class="mt-4 border-t pt-4 text-xs bg-gray-50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-100 dark:border-gray-800/50">
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-1">How to link Google Services:</h4>
                            <ol class="list-decimal list-inside space-y-1 text-gray-500 dark:text-gray-400">
                                <li>Go to <strong>Settings &rarr; Branding & SEO</strong> in the Admin panel.</li>
                                <li>Paste your <strong>Google Analytics Tag (G-XXXXX)</strong> to start tracking views.</li>
                                <li>Paste the HTML tag content value of <strong>Google Site Verification</strong> to claim Search Console ownership.</li>
                            </ol>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</div>
@endsection
