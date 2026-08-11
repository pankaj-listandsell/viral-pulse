@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', $categories->total() . ' ' . Str::plural('category', $categories->total()))

@section('actions')
    <x-ui.button :href="route('admin.categories.create')">
        <x-icon name="plus" class="size-4" />
        New category
    </x-ui.button>
@endsection

@section('content')
    <form method="GET" class="mb-4 flex gap-2">
        <div class="relative max-w-sm flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
            <x-ui.input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search categories" class="pl-9" />
        </div>
        <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
    </form>

    <x-ui.card :padded="false">
        @if($categories->isEmpty())
            <x-ui.empty-state icon="folder" title="No categories yet"
                              description="Categories group your posts and drive the site navigation.">
                <x-slot:action>
                    <x-ui.button :href="route('admin.categories.create')">New category</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase
                                  dark:border-gray-800 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Name</th>
                            <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Parent</th>
                            <th scope="col" class="px-5 py-3 font-medium">Posts</th>
                            <th scope="col" class="px-5 py-3 font-medium">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($categories as $category)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="size-2.5 shrink-0 rounded-full"
                                              style="background-color: {{ $category->color ?? '#94a3b8' }}"></span>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.categories.edit', $category) }}"
                                               class="block truncate font-medium hover:text-brand-600 dark:hover:text-brand-400">
                                                {{ $category->name }}
                                            </a>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">/{{ $category->slug }}</span>
                                        </div>
                                        @if($category->is_featured)
                                            <x-icon name="star" class="size-3.5 shrink-0 text-amber-500" />
                                        @endif
                                    </div>
                                </td>
                                <td class="hidden px-5 py-3 text-gray-500 md:table-cell dark:text-gray-400">
                                    {{ $category->parent?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-3 tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $category->posts_count }}
                                </td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :color="$category->is_active ? 'green' : 'gray'">
                                        {{ $category->is_active ? 'Active' : 'Hidden' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.categories.edit', $category) }}"
                                           class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600
                                                  dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                           aria-label="Edit {{ $category->name }}">
                                            <x-icon name="pencil" class="size-4" />
                                        </a>

                                        <x-ui.confirm-form
                                            :action="route('admin.categories.destroy', $category)"
                                            title="Delete this category?"
                                            :message="'&quot;' . $category->name . '&quot; will be removed. Categories that still hold posts cannot be deleted.'"
                                        >
                                            <x-slot:trigger>
                                                <button type="submit"
                                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600
                                                               dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                                        aria-label="Delete {{ $category->name }}">
                                                    <x-icon name="trash-2" class="size-4" />
                                                </button>
                                            </x-slot:trigger>
                                        </x-ui.confirm-form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @if($categories->hasPages())
        <div class="mt-4">{{ $categories->links() }}</div>
    @endif
@endsection
