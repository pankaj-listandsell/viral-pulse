@extends('layouts.admin')

@section('title', 'Posts')
@section('heading', 'Posts')
@section('subheading', $posts->total() . ' ' . Str::plural('post', $posts->total()))

@section('actions')
    <x-ui.button :href="route('admin.posts.create')">
        <x-icon name="plus" class="size-4" />
        New post
    </x-ui.button>
@endsection

@section('content')
    {{-- Top Status Pill Navigation Bar (1-Click Instant Filters) --}}
    <div class="mb-4 flex items-center justify-between gap-3 overflow-x-auto pb-1 scrollbar-none">
        <nav class="flex items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white p-1 shadow-2xs dark:border-gray-800 dark:bg-gray-900">
            {{-- All Tab --}}
            <a href="{{ route('admin.posts.index', array_filter([...request()->except('status', 'trashed', 'page')])) }}"
               @class([
                   'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                   'bg-gray-900 text-white shadow-xs dark:bg-white dark:text-gray-900' => !request('status') && !request('trashed'),
                   'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => request('status') || request('trashed'),
               ])>
                <span>All</span>
                <span @class([
                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                    'bg-gray-700 text-white dark:bg-gray-200 dark:text-gray-900' => !request('status') && !request('trashed'),
                    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => request('status') || request('trashed'),
                ])>{{ array_sum($statusCounts->all()) }}</span>
            </a>

            {{-- Status Tabs --}}
            @foreach(\App\Enums\PostStatus::cases() as $status)
                <a href="{{ route('admin.posts.index', array_filter([...request()->except('trashed', 'page'), 'status' => $status->value])) }}"
                   @class([
                       'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                       'bg-gray-900 text-white shadow-xs dark:bg-white dark:text-gray-900' => request('status') === $status->value && !request('trashed'),
                       'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => request('status') !== $status->value || request('trashed'),
                   ])>
                    <span>{{ $status->label() }}</span>
                    <span @class([
                        'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                        'bg-gray-700 text-white dark:bg-gray-200 dark:text-gray-900' => request('status') === $status->value && !request('trashed'),
                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => request('status') !== $status->value || request('trashed'),
                    ])>{{ $statusCounts[$status->value] ?? 0 }}</span>
                </a>
            @endforeach

            <span class="h-4 w-px bg-gray-200 dark:bg-gray-700 mx-0.5"></span>

            {{-- Trash Tab --}}
            <a href="{{ route('admin.posts.index', ['trashed' => 1]) }}"
               @class([
                   'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                   'bg-red-600 text-white shadow-xs' => request()->boolean('trashed'),
                   'text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:bg-red-950/30' => !request()->boolean('trashed'),
               ])>
                <x-icon name="trash" class="size-3.5" />
                <span>Trash</span>
                <span class="rounded-full bg-gray-100 px-1.5 py-0.2 text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    {{ $trashedCount }}
                </span>
            </a>
        </nav>
    </div>

    {{-- Filter & Search Action Bar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200/80 bg-white p-3 shadow-2xs dark:border-gray-800 dark:bg-gray-900">
        <form method="GET" class="flex flex-1 flex-wrap items-center gap-2.5">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('trashed'))
                <input type="hidden" name="trashed" value="1">
            @endif

            {{-- Search Bar with Clear Icon --}}
            <div class="relative min-w-[240px] flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search by headline, keyword..."
                    class="block w-full rounded-lg border border-gray-200 bg-gray-50/50 py-1.5 pr-8 pl-9 text-xs text-gray-900 transition placeholder:text-gray-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-100 dark:focus:bg-gray-900"
                />
                @if(!empty($filters['search']))
                    <a href="{{ route('admin.posts.index', array_filter(request()->except('search', 'page'))) }}"
                       class="absolute top-1/2 right-2.5 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-icon name="x" class="size-3.5" />
                    </a>
                @endif
            </div>

            {{-- Category Filter --}}
            <div class="w-full sm:w-auto">
                <select name="category" onchange="this.form.submit()"
                        class="block w-full sm:w-44 rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-1.5 text-xs font-medium text-gray-700 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-200">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Source Filter --}}
            <div class="w-full sm:w-auto">
                <select name="source" onchange="this.form.submit()"
                        class="block w-full sm:w-36 rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-1.5 text-xs font-medium text-gray-700 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-200">
                    <option value="">All Sources</option>
                    <option value="ai" @selected(($filters['source'] ?? '') === 'ai')>🤖 AI Generated</option>
                    <option value="manual" @selected(($filters['source'] ?? '') === 'manual')>✍️ Manual</option>
                </select>
            </div>

            @if(request('category') || request('source') || request('search'))
                <a href="{{ route('admin.posts.index', array_filter(['status' => request('status'), 'trashed' => request('trashed')])) }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <x-icon name="x" class="size-3" />
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{--
        Selection state lives on this wrapper rather than on a <form> around the
        table: the row action buttons are forms themselves, and forms cannot be
        nested. The bulk form sits outside the table and receives the selected
        ids as hidden inputs.
    --}}
    <div x-data="{ selected: [] }">
        <form
            method="POST"
            action="{{ route('admin.posts.bulk') }}"
            x-ref="bulkForm"
            x-show="selected.length"
            x-cloak
            class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3
                   dark:border-gray-800 dark:bg-gray-900"
        >
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>

            <span class="text-sm text-gray-600 dark:text-gray-300">
                <span x-text="selected.length"></span> selected
            </span>

            <div class="ml-auto flex flex-wrap gap-2">
                <x-ui.button type="submit" name="action" value="publish" variant="secondary" size="sm">Publish</x-ui.button>
                <x-ui.button type="submit" name="action" value="unpublish" variant="secondary" size="sm">Unpublish</x-ui.button>
                <x-ui.button type="submit" name="action" value="archive" variant="secondary" size="sm">Archive</x-ui.button>
                <x-ui.button type="submit" name="action" value="delete" variant="danger" size="sm">Trash</x-ui.button>
            </div>
        </form>

        <x-ui.card :padded="false">
            @if($posts->isEmpty())
                <x-ui.empty-state
                    icon="file-text"
                    :title="array_filter($filters) ? 'No posts match those filters' : 'No posts yet'"
                    :description="array_filter($filters)
                        ? 'Try a different search or clear the filters.'
                        : 'Write your first post, or generate one with AI.'"
                >
                    @unless(array_filter($filters))
                        <x-slot:action>
                            <x-ui.button :href="route('admin.posts.create')">New post</x-ui.button>
                        </x-slot:action>
                    @endunless
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase
                                      dark:border-gray-800 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="w-10 px-5 py-3">
                                    <x-ui.checkbox
                                        @change="selected = $event.target.checked ? @js($posts->pluck('id')->map(fn ($id) => (string) $id)) : []"
                                        aria-label="Select all"
                                    />
                                </th>
                                <th scope="col" class="px-5 py-3 font-medium">Title</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium lg:table-cell">Category</th>
                                <th scope="col" class="px-5 py-3 font-medium">Status</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Views</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Updated</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($posts as $post)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-5 py-3">
                                        <x-ui.checkbox
                                            :value="(string) $post->id"
                                            x-model="selected"
                                            :aria-label="'Select ' . $post->title"
                                        />
                                    </td>

                                    <td class="max-w-md px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $media = app(\App\Services\MediaResolver::class)->find($post->featured_image);
                                            @endphp
                                            <div class="w-14 h-10 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200/50 dark:border-gray-700/50">
                                                @if($media)
                                                    <img src="{{ $media->conversionUrl('thumbnail') ?? $media->url }}" 
                                                         alt="{{ $post->title }}" 
                                                         class="h-full w-full object-cover">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-600">
                                                        <x-icon name="image" class="size-4" />
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    @if($post->trashed())
                                                        <span class="truncate font-medium text-gray-500 line-through block">{{ $post->title }}</span>
                                                    @else
                                                        <a href="{{ route('admin.posts.edit', $post) }}"
                                                           class="truncate font-medium hover:text-brand-600 dark:hover:text-brand-400 block"
                                                           title="{{ $post->title }}">
                                                            {{ $post->title }}
                                                        </a>
                                                    @endif

                                                    @if($post->ai_generated)
                                                        <x-ui.badge color="violet" class="shrink-0"><x-icon name="bot" class="size-3" />AI</x-ui.badge>
                                                    @endif
                                                    @if($post->is_featured)
                                                        <x-icon name="star" class="size-3.5 shrink-0 text-amber-500" />
                                                    @endif
                                                </div>
                                                <p class="truncate text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ $post->author?->name }} &middot; /{{ $post->slug }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="hidden px-5 py-3 lg:table-cell">
                                        <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                            <span class="size-2 rounded-full" style="background-color: {{ $post->category?->color ?? '#94a3b8' }}"></span>
                                            {{ $post->category?->name }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3">
                                        <x-ui.badge :color="$post->status->color()">{{ $post->status->label() }}</x-ui.badge>
                                        @if($post->status === \App\Enums\PostStatus::Scheduled && $post->scheduled_at)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $post->scheduled_at->format('M j, H:i') }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="hidden px-5 py-3 tabular-nums text-gray-500 sm:table-cell dark:text-gray-400">
                                        {{ number_format($post->views_count) }}
                                    </td>

                                    <td class="hidden px-5 py-3 text-gray-500 md:table-cell dark:text-gray-400">
                                        {{ $post->updated_at->diffForHumans() }}
                                    </td>

                                    <td class="px-5 py-3 text-right">
                                        @include('admin.posts.partials.row-actions')
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    @if($posts->hasPages())
        <div class="mt-4">{{ $posts->links() }}</div>
    @endif
@endsection
