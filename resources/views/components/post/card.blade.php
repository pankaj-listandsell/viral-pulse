@props(['post', 'size' => 'md', 'eager' => false])

@php
    $sizes = [
        'sm' => ['title' => 'text-sm', 'image' => 'aspect-16/9', 'excerpt' => false],
        'md' => ['title' => 'text-base', 'image' => 'aspect-16/9', 'excerpt' => true],
        'lg' => ['title' => 'text-xl sm:text-2xl', 'image' => 'aspect-16/9', 'excerpt' => true],
    ];
    $style = $sizes[$size] ?? $sizes['md'];
    $url = route('posts.show', $post);
@endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col']) }}>
    <a href="{{ $url }}" class="block overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800" tabindex="-1" aria-hidden="true">
        <x-post.image :post="$post" :class="$style['image'] . ' w-full object-cover transition duration-300 group-hover:scale-[1.03]'" :eager="$eager" />
    </a>

    <div class="mt-3 flex flex-1 flex-col">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            @if($post->category)
                <a href="{{ route('categories.show', $post->category) }}"
                   class="inline-flex items-center gap-1.5 font-medium text-gray-600 transition hover:text-brand-600 dark:text-gray-300">
                    <span class="size-2 rounded-full" style="background-color: {{ $post->category->color ?? '#94a3b8' }}"></span>
                    {{ $post->category->name }}
                </a>
            @endif

            <span class="text-gray-400" aria-hidden="true">&middot;</span>
            <time datetime="{{ $post->published_at?->toDateString() }}" class="text-gray-500 dark:text-gray-400">
                {{ $post->published_at?->format('M j, Y') }}
            </time>

            @if($post->reading_time)
                <span class="text-gray-400" aria-hidden="true">&middot;</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $post->reading_time }} min read</span>
            @endif
        </div>

        <h3 class="mt-1.5 font-semibold leading-snug tracking-tight {{ $style['title'] }}">
            <a href="{{ $url }}" class="transition group-hover:text-brand-600 dark:group-hover:text-brand-400">
                {{ $post->title }}
            </a>
        </h3>

        @if($style['excerpt'] && $post->excerpt)
            <p class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
