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
        </a>
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
@endsection
