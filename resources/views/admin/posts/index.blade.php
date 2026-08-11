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
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <div class="relative min-w-56 flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
            <x-ui.input
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search titles and content"
                class="pl-9"
            />
        </div>

        <x-ui.select name="status" class="w-auto" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(\App\Enums\PostStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                    {{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})
                </option>
            @endforeach
        </x-ui.select>

        <x-ui.select name="category" class="w-auto" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-ui.select>

        <x-ui.select name="source" class="w-auto" onchange="this.form.submit()">
            <option value="">Any source</option>
            <option value="ai" @selected(($filters['source'] ?? '') === 'ai')>AI generated</option>
            <option value="manual" @selected(($filters['source'] ?? '') === 'manual')>Written manually</option>
        </x-ui.select>

        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

        @if(array_filter($filters))
            <x-ui.button variant="ghost" :href="route('admin.posts.index')">Clear</x-ui.button>
        @endif

        <a
            href="{{ route('admin.posts.index', ['trashed' => request()->boolean('trashed') ? null : 1]) }}"
            @class([
                'ml-auto rounded-lg px-3 py-2 text-sm font-medium transition',
                'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' => request()->boolean('trashed'),
                'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => ! request()->boolean('trashed'),
            ])
        >Trash</a>
    </form>

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

                                    <td class="max-w-sm px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            @if($post->trashed())
                                                <span class="truncate font-medium text-gray-500 line-through">{{ $post->title }}</span>
                                            @else
                                                <a href="{{ route('admin.posts.edit', $post) }}"
                                                   class="truncate font-medium hover:text-brand-600 dark:hover:text-brand-400">
                                                    {{ $post->title }}
                                                </a>
                                            @endif

                                            @if($post->ai_generated)
                                                <x-ui.badge color="violet"><x-icon name="bot" class="size-3" />AI</x-ui.badge>
                                            @endif
                                            @if($post->is_featured)
                                                <x-icon name="star" class="size-3.5 shrink-0 text-amber-500" />
                                            @endif
                                        </div>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $post->author?->name }} &middot; /{{ $post->slug }}
                                        </p>
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
