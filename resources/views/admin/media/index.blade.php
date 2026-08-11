@extends('layouts.admin')

@section('title', 'Media library')
@section('heading', 'Media library')
@section('subheading', $media->total() . ' ' . Str::plural('image', $media->total()))

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <form method="GET" class="flex max-w-sm flex-1 gap-2">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                <x-ui.input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by filename or alt text" class="pl-9" />
            </div>
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>

        <form
            method="POST"
            action="{{ route('admin.media.store') }}"
            enctype="multipart/form-data"
            class="ml-auto"
            x-data
        >
            @csrf
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                <x-icon name="plus" class="size-4" />
                Upload images
                <input
                    type="file"
                    name="files[]"
                    class="sr-only"
                    accept="{{ implode(',', config('site.media.allowed_mimes')) }}"
                    multiple
                    @change="$el.form.submit()"
                >
            </label>
        </form>
    </div>

    @error('files.*')
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700
                  dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">{{ $message }}</p>
    @enderror

    <x-ui.card :padded="false">
        @if($media->isEmpty())
            <x-ui.empty-state
                icon="image"
                title="No images yet"
                :description="'Uploads are re-encoded to ' . (config('site.media.webp') ? 'WebP' : 'JPEG') . ' and resized automatically. Max ' . round(config('site.media.max_upload_kb') / 1024, 1) . ' MB each.'"
            />
        @else
            <div class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($media as $item)
                    <div
                        class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800"
                        x-data="{ open: false }"
                    >
                        <img
                            src="{{ $item->conversionUrl('thumbnail') ?? $item->url }}"
                            alt="{{ $item->alt_text ?: $item->original_name }}"
                            loading="lazy"
                            width="{{ $item->width }}"
                            height="{{ $item->height }}"
                            class="aspect-4/3 w-full bg-gray-100 object-cover dark:bg-gray-800"
                        >

                        <div class="absolute inset-0 flex items-center justify-center gap-1.5 bg-gray-900/60 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                            <button type="button" @click="open = true"
                                    class="rounded-lg bg-white px-2.5 py-1.5 text-xs font-medium text-gray-900">
                                Details
                            </button>

                            <x-ui.confirm-form
                                :action="route('admin.media.destroy', $item)"
                                title="Delete this image?"
                                message="Any post still using it will show a broken image."
                            >
                                <x-slot:trigger>
                                    <button type="submit" class="rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white">
                                        Delete
                                    </button>
                                </x-slot:trigger>
                            </x-ui.confirm-form>
                        </div>

                        <div class="px-2 py-1.5">
                            <p class="truncate text-xs font-medium">{{ $item->original_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $item->width }}×{{ $item->height }} · {{ number_format($item->size / 1024) }} KB
                            </p>
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak x-transition.opacity
                                 class="fixed inset-0 z-50 grid place-items-center bg-gray-900/50 p-4 backdrop-blur-xs"
                                 @keydown.escape.window="open = false">
                                <div class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-5 shadow-xl
                                            dark:border-gray-800 dark:bg-gray-900"
                                     @click.outside="open = false" role="dialog" aria-modal="true">
                                    <img src="{{ $item->conversionUrl('medium') ?? $item->url }}"
                                         alt="{{ $item->alt_text ?: $item->original_name }}"
                                         class="mb-4 max-h-64 w-full rounded-lg object-contain">

                                    <form method="POST" action="{{ route('admin.media.update', $item) }}" class="space-y-3">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <x-ui.label for="alt-{{ $item->id }}">Alt text</x-ui.label>
                                            <x-ui.input id="alt-{{ $item->id }}" name="alt_text" value="{{ $item->alt_text }}"
                                                        placeholder="Describe the image for screen readers and search engines" />
                                        </div>

                                        <div>
                                            <x-ui.label for="caption-{{ $item->id }}">Caption</x-ui.label>
                                            <x-ui.input id="caption-{{ $item->id }}" name="caption" value="{{ $item->caption }}" />
                                        </div>

                                        <div>
                                            <x-ui.label for="url-{{ $item->id }}">Path</x-ui.label>
                                            <x-ui.input id="url-{{ $item->id }}" value="{{ $item->path }}" readonly
                                                        class="font-mono text-xs" @focus="$el.select()" />
                                        </div>

                                        <div class="flex justify-end gap-2 pt-1">
                                            <x-ui.button type="button" variant="secondary" size="sm" @click="open = false">Close</x-ui.button>
                                            <x-ui.button type="submit" size="sm">Save</x-ui.button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    @if($media->hasPages())
        <div class="mt-4">{{ $media->links() }}</div>
    @endif
@endsection
