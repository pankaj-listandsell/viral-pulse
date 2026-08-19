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
     * The featured image is a brand card drawn by BrandCardGenerator: it has
     * the headline, the section and the date painted into the pixels. Showing
     * it above the same headline in text printed everything twice, so in the
     * feed the card is rebuilt in HTML instead - same design, real text.
     *
     * The generated file still does the job it is actually good at: it is the
     * 1200x630 og:image, where markup cannot go. A post carrying a genuine
     * photograph is detected here and rendered as one.
     */
    $isBrandCard = ! $post->featured_image || str_contains($post->featured_image, '/cards/');
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $logo = $siteSettings['site_logo'] ?? null;
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-200/50 dark:border-gray-800 dark:bg-gray-900/40 dark:hover:shadow-none']) }}>

    @if($isBrandCard)
        {{-- Brand card, in markup --}}
        <div class="relative flex {{ $style['aspect'] }} flex-col justify-between overflow-hidden bg-[#0f1421] {{ $style['pad'] }} text-white">
            <span class="absolute inset-x-0 top-0 h-1.5" style="background-color: {{ $accent }};" aria-hidden="true"></span>
            <span class="absolute inset-0 opacity-15 transition duration-500 group-hover:opacity-25"
                  style="background-image: linear-gradient(to top, {{ $accent }}, transparent 60%);" aria-hidden="true"></span>

            <div class="relative flex items-start justify-between gap-3">
                <span class="flex items-center gap-1.5 text-xs font-bold text-white/70">
                    @if($logo)
                        <img src="{{ Storage::disk(config('site.media.disk'))->url($logo) }}" alt=""
                             class="size-4 rounded object-contain" width="16" height="16" loading="lazy">
                    @else
                        <span class="grid size-4 place-items-center rounded bg-brand-600 text-white">
                            <x-icon name="flame" class="size-2.5" />
                        </span>
                    @endif
                    {{ $siteName }}
                </span>

                @if($post->category)
                    <span class="shrink-0 rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white"
                          style="background-color: {{ $accent }};">
                        {{ $post->category->name }}
                    </span>
                @endif
            </div>

            <{{ $heading }} class="relative mt-4 font-black leading-tight tracking-tight {{ $style['title'] }} {{ $style['clamp'] }}">
                <a href="{{ $url }}" class="transition after:absolute after:inset-0 after:content-[''] group-hover:text-white/90">
                    {{ $post->title }}
                </a>
            </{{ $heading }}>

            <p class="relative mt-3 flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-white/45">
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
                @if($post->reading_time)
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $post->reading_time }} min read</span>
                @endif
            </p>
        </div>
    @else
        {{-- A real photograph: show it, and put the headline underneath. --}}
        <div class="relative overflow-hidden">
            <a href="{{ $url }}" class="block" tabindex="-1" aria-hidden="true">
                <x-post.image :post="$post" :eager="$eager"
                              :class="$style['aspect'].' w-full object-cover transition duration-700 ease-out group-hover:scale-105'" />
            </a>

            @if($post->category)
                <span class="absolute left-3 top-3 rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow"
                      style="background-color: {{ $accent }};">
                    {{ $post->category->name }}
                </span>
            @endif

            {{-- A drawing is labelled wherever it is large enough to be taken
                 for a photograph, and a card in the feed is that large. --}}
            @if(str_contains((string) $post->featured_image, '/illustrations/'))
                <span class="absolute right-3 top-3 rounded bg-black/60 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white backdrop-blur">
                    AI illustration
                </span>
            @endif
        </div>

        <div class="{{ $style['pad'] }} pb-0">
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
        </div>
    @endif

    @if($style['excerpt'] && $post->excerpt)
        <div class="flex flex-1 flex-col {{ $style['pad'] }}">
            <p class="line-clamp-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $post->excerpt }}</p>

            <span class="mt-auto pt-4 text-xs font-bold text-brand-600 transition group-hover:translate-x-0.5 dark:text-brand-400">
                Read article &rarr;
            </span>
        </div>
    @endif
</article>
