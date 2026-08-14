@props(['post', 'size' => 'md', 'eager' => false])

@php
    $sizes = [
        'sm' => ['title' => 'text-base', 'image' => 'aspect-16/10', 'excerpt' => false],
        'md' => ['title' => 'text-lg', 'image' => 'aspect-16/10', 'excerpt' => true],
        'lg' => ['title' => 'text-xl sm:text-2xl', 'image' => 'aspect-16/10', 'excerpt' => true],
    ];
    $style = $sizes[$size] ?? $sizes['md'];
    $url = route('posts.show', $post);
@endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl border border-gray-200/50 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-gray-200/40 dark:border-gray-800/80 dark:bg-gray-900/40 dark:hover:shadow-none']) }}>
    <div class="relative overflow-hidden">
        <a href="{{ $url }}" class="block" tabindex="-1" aria-hidden="true">
            <x-post.image :post="$post" :class="$style['image'] . ' w-full object-cover transition duration-700 ease-out group-hover:scale-105'" :eager="$eager" />
        </a>
    </div>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center flex-wrap gap-2 text-xs">
            @if($post->category)
                <a href="{{ route('categories.show', $post->category) }}"
                   class="inline-flex items-center gap-1.5 font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                    <span class="size-2 rounded-full" style="background-color: {{ $post->category->color ?? '#ef4444' }};"></span>
                    {{ $post->category->name }}
                </a>
                <span class="text-gray-300 dark:text-gray-700" aria-hidden="true">&middot;</span>
            @endif
            
            <time datetime="{{ $post->published_at?->toDateString() }}" class="text-gray-500 dark:text-gray-400">
                {{ $post->published_at?->format('M j, Y') }}
            </time>
            
            @if($post->reading_time)
                <span class="text-gray-300 dark:text-gray-700" aria-hidden="true">&middot;</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $post->reading_time }} min read</span>
            @endif
        </div>

        <h3 class="mt-2.5 font-bold leading-snug tracking-tight text-gray-900 dark:text-white {{ $style['title'] }}">
            <a href="{{ $url }}" class="transition duration-150 hover:text-brand-600 dark:hover:text-brand-400">
                {{ $post->title }}
            </a>
        </h3>

        @if($style['excerpt'] && $post->excerpt)
            <p class="mt-3 line-clamp-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $post->excerpt }}</p>
        @endif

        <div class="mt-auto pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-800/60">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $post->author?->name ?? 'Viral Plush' }}</span>
            <span class="text-xs font-bold text-brand-600 dark:text-brand-400 flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                Read Article &rarr;
            </span>
        </div>
    </div>
</article>