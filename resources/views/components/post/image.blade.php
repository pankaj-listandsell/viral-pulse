@props(['post', 'eager' => false, 'conversion' => 'medium'])

@php
    $disk = Storage::disk(config('site.media.disk'));
    $path = $post->featured_image;

    // Resolved through the batching resolver, so a grid of cards costs one
    // query rather than one per image.
    $media = app(\App\Services\MediaResolver::class)->find($path);

    $src = $path ? $disk->url($path) : null;
    $srcset = null;

    if ($media?->conversions) {
        $candidates = collect($media->conversions)
            ->filter(fn ($conversion) => ! empty($conversion['path']) && ! empty($conversion['width']))
            ->map(fn ($conversion) => $disk->url($conversion['path']).' '.$conversion['width'].'w');

        if ($candidates->isNotEmpty()) {
            $srcset = $candidates->push($disk->url($path).' '.($media->width ?? 1600).'w')->implode(', ');
        }
    }
@endphp

@if($src)
    <img
        src="{{ $src }}"
        @if($srcset)
            srcset="{{ $srcset }}"
            sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw"
        @endif
        alt="{{ $post->featured_image_alt ?: $post->title }}"
        @if($media?->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        fetchpriority="{{ $eager ? 'high' : 'auto' }}"
        decoding="async"
        {{ $attributes }}
    >
@else
    {{-- Placeholder keeps the card's aspect ratio, so a post without an image
         does not cause a layout shift when the rest of the grid loads. --}}
    <div {{ $attributes->merge(['class' => 'grid place-items-center bg-gray-100 text-gray-300 dark:bg-gray-800 dark:text-gray-600']) }}>
        <x-icon name="image" class="size-8" />
    </div>
@endif
