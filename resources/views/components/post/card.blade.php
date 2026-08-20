@props(['post', 'size' => 'md', 'eager' => false, 'heading' => 'h3'])

@php
    $sizes = [
        'sm' => ['title' => 'text-base', 'aspect' => 'aspect-16/10', 'pad' => 'p-4', 'excerpt' => false, 'clamp' => 'line-clamp-3'],
        'md' => ['title' => 'text-lg', 'aspect' => 'aspect-16/10', 'pad' => 'p-5', 'excerpt' => true, 'clamp' => 'line-clamp-3'],
        'lg' => ['title' => 'text-xl sm:text-2xl', 'aspect' => 'aspect-16/9', 'pad' => 'p-6', 'excerpt' => true, 'clamp' => 'line-clamp-4'],
    ];
    $style = $sizes[$size] ?? $sizes['md'];
    $url = route('posts.show', $post);
    $accent = $post->category?->color ?? '#ef4444';

    /**
     * A brand card already carries a coloured bar, the masthead and the section
     * name, drawn into the picture by BrandCardGenerator. Laying the same three
     * things over it stacked two badges in one corner and covered the logo, so
     * the overlay is only for pictures that do not have them: a stock photo or
     * an illustration.
     */
    $isBrandCard = ! $post->featured_image || str_contains($post->featured_image, '/cards/');
@endphp

{{-- One shape for every post.
     A brand card, a stock photograph and an AI illustration all render the
     same way here: picture on top, headline underneath as live text. The
     headline does appear twice on a post whose picture is a brand card, and
     that is not an SEO problem - a page repeating its own words is normal, and
     the version that counts is the one in the markup. A feed that changes
     shape row to row is the worse cost. --}}
<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-200/50 dark:border-gray-800 dark:bg-gray-900/40 dark:hover:shadow-none']) }}>

    <div class="relative overflow-hidden">
        @unless($isBrandCard)
            <span class="absolute inset-x-0 top-0 z-10 h-1" style="background-color: {{ $accent }};" aria-hidden="true"></span>
        @endunless

        <a href="{{ $url }}" class="block" tabindex="-1" aria-hidden="true">
            <x-post.image :post="$post" :eager="$eager"
                          :class="$style['aspect'].' w-full object-cover transition duration-700 ease-out group-hover:scale-105'" />
        </a>

        @if($post->category && ! $isBrandCard)
            <span class="absolute left-3 top-4 rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow"
                  style="background-color: {{ $accent }};">
                {{ $post->category->name }}
            </span>
        @endif

        {{-- A drawing is labelled wherever it is large enough to be taken for a
             photograph, and a card in the feed is that large. --}}
        @if(str_contains((string) $post->featured_image, '/illustrations/'))
            <span class="absolute right-3 top-4 rounded bg-black/60 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white backdrop-blur">
                AI illustration
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col {{ $style['pad'] }}">
        <{{ $heading }} class="font-black leading-snug tracking-tight text-gray-900 {{ $style['title'] }} {{ $style['clamp'] }} dark:text-white">
            <a href="{{ $url }}" class="transition after:absolute after:inset-0 after:content-[''] hover:text-brand-600 dark:hover:text-brand-400">
                {{ $post->title }}
            </a>
        </{{ $heading }}>

        <p class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-gray-500 dark:text-gray-400">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
            @if($post->reading_time)
                <span aria-hidden="true">&middot;</span>
                <span>{{ $post->reading_time }} min read</span>
            @endif
        </p>

        @if($style['excerpt'] && $post->excerpt)
            <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $post->excerpt }}</p>

            <span class="mt-auto pt-4 text-xs font-bold text-brand-600 transition group-hover:translate-x-0.5 dark:text-brand-400">
                Read article &rarr;
            </span>
        @endif
    </div>
</article>
