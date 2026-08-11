@extends('layouts.admin')

@section('title', 'Tags')
@section('heading', 'Tags')
@section('subheading', $tags->total() . ' ' . Str::plural('tag', $tags->total()))

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="GET" class="mb-4 flex gap-2">
                <div class="relative max-w-sm flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                    <x-ui.input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search tags" class="pl-9" />
                </div>
                <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
            </form>

            <x-ui.card :padded="false">
                @if($tags->isEmpty())
                    <x-ui.empty-state icon="tags" title="No tags yet"
                                      description="Tags are also created automatically as you write posts." />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase
                                          dark:border-gray-800 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-medium">Tag</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Posts</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Trending</th>
                                    <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($tags as $tag)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                        x-data="{ editing: false }">
                                        <td class="px-5 py-3">
                                            <div x-show="!editing">
                                                <span class="font-medium">{{ $tag->name }}</span>
                                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">/{{ $tag->slug }}</span>
                                            </div>

                                            <form
                                                x-show="editing"
                                                x-cloak
                                                method="POST"
                                                action="{{ route('admin.tags.update', $tag) }}"
                                                class="flex flex-wrap items-center gap-2"
                                                id="tag-form-{{ $tag->id }}"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <x-ui.input name="name" value="{{ $tag->name }}" class="w-40" required />
                                                <x-ui.input name="slug" value="{{ $tag->slug }}" class="w-40" />
                                                <label class="flex items-center gap-1.5 text-xs">
                                                    <input type="hidden" name="is_trending" value="0">
                                                    <x-ui.checkbox name="is_trending" value="1" :checked="$tag->is_trending" />
                                                    Trending
                                                </label>
                                                <x-ui.button type="submit" size="sm">Save</x-ui.button>
                                                <x-ui.button type="button" size="sm" variant="ghost" @click="editing = false">Cancel</x-ui.button>
                                            </form>
                                        </td>
                                        <td class="px-5 py-3 tabular-nums text-gray-500 dark:text-gray-400">{{ $tag->posts_count }}</td>
                                        <td class="px-5 py-3">
                                            @if($tag->is_trending)
                                                <x-ui.badge color="red"><x-icon name="flame" class="size-3" />Trending</x-ui.badge>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button" @click="editing = !editing"
                                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600
                                                               dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                                        aria-label="Edit {{ $tag->name }}">
                                                    <x-icon name="pencil" class="size-4" />
                                                </button>

                                                <x-ui.confirm-form
                                                    :action="route('admin.tags.destroy', $tag)"
                                                    title="Delete this tag?"
                                                    :message="'It will be removed from ' . $tag->posts_count . ' ' . Str::plural('post', $tag->posts_count) . '. The posts themselves are not affected.'"
                                                >
                                                    <x-slot:trigger>
                                                        <button type="submit"
                                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600
                                                                       dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                                                aria-label="Delete {{ $tag->name }}">
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

            @if($tags->hasPages())
                <div class="mt-4">{{ $tags->links() }}</div>
            @endif
        </div>

        <div>
            <x-ui.card title="Add a tag">
                <form method="POST" action="{{ route('admin.tags.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-ui.label for="name" required>Name</x-ui.label>
                        <x-ui.input id="name" name="name" value="{{ old('name') }}" required :invalid="$errors->has('name')" />
                        <x-ui.error for="name" />
                    </div>

                    <div>
                        <x-ui.label for="slug">Slug</x-ui.label>
                        <x-ui.input id="slug" name="slug" value="{{ old('slug') }}" :invalid="$errors->has('slug')" />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Left blank, it is generated from the name.</p>
                        <x-ui.error for="slug" />
                    </div>

                    <x-ui.toggle name="is_trending" :checked="(bool) old('is_trending')" label="Trending" />

                    <x-ui.button type="submit" class="w-full">Add tag</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
@endsection
