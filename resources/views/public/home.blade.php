@extends('layouts.public')

@section('content')
    <div class="relative overflow-hidden bg-gradient-to-b from-gray-50/50 via-white to-gray-50/30 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950">
        
        {{-- Mesh glow background decorations --}}
        <div class="absolute -top-40 right-0 -z-10 size-[35rem] rounded-full bg-brand-500/10 blur-[150px] dark:bg-brand-500/5"></div>
        <div class="absolute top-[30rem] -left-20 -z-10 size-[28rem] rounded-full bg-indigo-500/10 blur-[120px] dark:bg-indigo-500/5"></div>

        {{-- Interactive Top Category Bar --}}
        @if($categories->isNotEmpty())
            <div class="border-b border-gray-200/80 bg-white/40 backdrop-blur-md dark:border-gray-800/80 dark:bg-gray-900/20">
                <div class="mx-auto max-w-6xl px-4 py-3 sm:px-6">
                    <div class="flex items-center justify-between gap-4 overflow-x-auto scrollbar-none py-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Categories:</span>
                            <div class="flex gap-2">
                                @foreach($categories as $category)
                                    <a href="{{ route('categories.show', $category) }}"
                                       class="inline-flex items-center gap-1.5 rounded-full border border-gray-200/80 bg-white/80 px-3.5 py-1 text-xs font-semibold text-gray-700 transition hover:border-brand-500 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-300 dark:hover:border-brand-400 dark:hover:bg-brand-950/20">
                                        <span class="size-1.5 rounded-full" style="background-color: {{ $category->color ?? '#ef4444' }}"></span>
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <a href="{{ route('categories.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 whitespace-nowrap">View All Categories &rarr;</a>
                    </div>
                </div>
            </div>
        @endif

        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
            
            {{-- Visual Web Stories Carousel --}}
            @php
                $webStories = $trending->map(fn($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'excerpt' => $p->excerpt,
                    'category' => $p->category?->name,
                    'image' => $p->featured_image ? Storage::disk(config('site.media.disk'))->url($p->featured_image) : null,
                    'url' => route('posts.show', $p),
                    'reading_time' => $p->reading_time,
                ])->values()->all();
            @endphp

            @if(!empty($webStories))
                <div data-island="StoryViewerModal" data-island-eager
                     data-props="{{ json_encode(['stories' => $webStories]) }}"></div>
            @endif

            {{-- Overhauled Hero Slider Section --}}
            @php
                $slides = (isset($heroSlides) && $heroSlides->isNotEmpty()) ? $heroSlides : ($hero ? collect([$hero]) : collect());
            @endphp

            @if($slides->isNotEmpty())
                <section aria-label="Featured Articles" class="relative mb-16 select-none" data-hero-slider>
                    <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900/40">
                        
                        {{-- Slides track / container --}}
                        <div class="relative min-h-[580px] sm:min-h-[520px] lg:min-h-[460px] w-full" data-slider-track>
                            @foreach($slides as $index => $post)
                                <div class="hero-slide absolute inset-0 w-full transition-all duration-700 ease-in-out {{ $index === 0 ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none' }}"
                                     data-slide="{{ $index }}"
                                     aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                                    
                                    <div class="grid h-full lg:grid-cols-12">
                                        {{-- Slide Image Container --}}
                                        <div class="lg:col-span-8 relative overflow-hidden group h-[260px] sm:h-[320px] lg:h-[460px]">
                                            <a href="{{ route('posts.show', $post) }}" class="block h-full w-full" tabindex="{{ $index === 0 ? '0' : '-1' }}">
                                                <x-post.image :post="$post" :eager="$index === 0"
                                                              class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-103" />
                                            </a>
                                            
                                            {{-- Category Tag Badge on Image --}}
                                            @if($post->category)
                                                <div class="absolute top-4 left-4 z-10">
                                                    <a href="{{ route('categories.show', $post->category) }}"
                                                       class="inline-flex items-center gap-1.5 rounded-full bg-black/60 backdrop-blur-md px-3.5 py-1 text-xs font-bold text-white shadow-md transition hover:bg-black/80">
                                                        <span class="size-2 rounded-full" style="background-color: {{ $post->category->color ?? '#ef4444' }}"></span>
                                                        {{ $post->category->name }}
                                                    </a>
                                                </div>
                                            @endif

                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent lg:hidden"></div>
                                        </div>

                                        {{-- Slide Details Container --}}
                                        <div class="lg:col-span-4 flex flex-col justify-between p-6 sm:p-8 lg:p-10 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm">
                                            <div>
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">
                                                        <x-icon name="flame" class="size-4 animate-bounce" />
                                                        Featured Article
                                                    </div>
                                                    @if($slides->count() > 1)
                                                        <span class="text-xs font-mono font-bold text-gray-400 dark:text-gray-500">
                                                            0{{ $index + 1 }} / 0{{ $slides->count() }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <h2 class="mt-4 text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white leading-snug line-clamp-3">
                                                    <a href="{{ route('posts.show', $post) }}" class="transition duration-200 hover:text-brand-600 dark:hover:text-brand-400" tabindex="{{ $index === 0 ? '0' : '-1' }}">
                                                        {{ $post->title }}
                                                    </a>
                                                </h2>

                                                @if($post->excerpt)
                                                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-3 lg:line-clamp-4">
                                                        {{ $post->excerpt }}
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5 dark:border-gray-800/80">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-9 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-sm dark:bg-brand-900/40 dark:text-brand-400">
                                                        {{ substr($post->author?->name ?? 'A', 0, 1) }}
                                                    </div>
                                                    <div class="text-xs">
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $post->author?->name }}</p>
                                                        <p class="text-gray-500 dark:text-gray-400 mt-0.5">
                                                            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time>
                                                        </p>
                                                    </div>
                                                </div>
                                                @if($post->reading_time)
                                                    <span class="rounded-md bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                        {{ $post->reading_time }} min read
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Navigation Controls (if more than 1 slide) --}}
                        @if($slides->count() > 1)
                            {{-- Indicators / Pagination Dots --}}
                            <div class="absolute bottom-5 left-6 z-20 hidden sm:flex items-center gap-1.5">
                                @foreach($slides as $index => $post)
                                    <button type="button"
                                            data-slider-dot="{{ $index }}"
                                            aria-label="Go to slide {{ $index + 1 }}"
                                            class="h-2 rounded-full transition-all duration-300 {{ $index === 0 ? 'w-8 bg-brand-600 dark:bg-brand-400' : 'w-2 bg-gray-300 dark:bg-gray-700 hover:bg-gray-400' }}">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </section>
            @endif

            <x-ads.header />

            {{-- Main Layout Content Grid --}}
            <div class="grid gap-12 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-16">
                    
                    {{-- Latest Stories Section --}}
                    <section aria-labelledby="latest-heading">
                        <div class="mb-8 flex items-baseline justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="block h-6 w-1 rounded-full bg-brand-600"></span>
                                <h2 id="latest-heading" class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">Latest Stories</h2>
                            </div>
                            <a href="{{ route('latest') }}" class="inline-flex items-center gap-1 text-sm font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400 group">
                                View all stories
                                <span class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
                            </a>
                        </div>

                        @if($latest->isEmpty())
                            <p class="rounded-3xl border border-dashed border-gray-300 px-6 py-16 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                Nothing published yet. Check back soon.
                            </p>
                        @else
                            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2">
                                @foreach($latest as $post)
                                    <x-post.card :post="$post" />
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- Editor's Pick Section --}}
                    @if($featured->isNotEmpty())
                        <section aria-labelledby="featured-heading" class="border-t border-gray-200/70 pt-16 dark:border-gray-800/80">
                            <div class="mb-8 flex items-baseline justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="block h-6 w-1 rounded-full bg-indigo-600"></span>
                                    <h2 id="featured-heading" class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">Editor's Choice</h2>
                                </div>
                            </div>
                            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2">
                                @foreach($featured as $post)
                                    <x-post.card :post="$post" size="sm" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-10 lg:pl-4">
                    
                    {{-- Trending Section --}}
                    @if($trending->isNotEmpty())
                        <section aria-labelledby="trending-heading" class="rounded-2xl border border-gray-200/80 bg-white/70 p-6 shadow-sm dark:border-gray-800/80 dark:bg-gray-900/20">
                            <h2 id="trending-heading" class="mb-6 flex items-center gap-2.5 text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-red-500/10 text-red-500">
                                    <x-icon name="flame" class="size-4 animate-pulse" />
                                </span>
                                Trending Today
                            </h2>

                            <ol class="space-y-4">
                                @foreach($trending as $index => $post)
                                    <li class="flex gap-4 group">
                                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-brand-500 to-rose-500 text-[10px] font-extrabold text-white shadow-sm shadow-brand-500/20">
                                            {{ $index + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold leading-snug text-gray-900 dark:text-gray-100">
                                                <a href="{{ route('posts.show', $post) }}" class="transition duration-150 hover:text-brand-600 dark:hover:text-brand-400">
                                                    {{ $post->title }}
                                                </a>
                                            </h3>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                                <span class="font-bold text-brand-600 dark:text-brand-400">{{ $post->category?->name }}</span>
                                                <span aria-hidden="true">&middot;</span>
                                                <span>{{ $post->published_at?->diffForHumans() }}</span>
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    {{-- Newsletter --}}
                    <div class="relative overflow-hidden rounded-2xl bg-slate-950 p-6 text-white shadow-xl dark:bg-black/40 dark:border dark:border-gray-800">
                        <div class="absolute -right-20 -bottom-20 size-48 rounded-full bg-brand-600/20 blur-3xl"></div>
                        <div class="relative z-10">
                            <x-newsletter.form />
                        </div>
                    </div>

                    <x-ads.sidebar />
                </aside>
            </div>
        </div>
    </div>
@endsection