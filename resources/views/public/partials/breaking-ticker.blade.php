@php
    $tickerPosts = app(\App\Services\ContentFeedService::class)->trending(5);
@endphp

@if($tickerPosts->isNotEmpty())
    <div class="border-b border-gray-200/70 bg-gradient-to-r from-brand-50/50 via-white to-gray-50/50 py-1.5 dark:border-gray-800/80 dark:from-brand-950/20 dark:via-gray-900/40 dark:to-gray-950/20">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 sm:px-6 text-xs">
            <div class="flex items-center gap-1.5 shrink-0 rounded-full bg-brand-600 px-2.5 py-0.5 font-bold tracking-wider text-white uppercase text-[10px] shadow-sm animate-pulse">
                <span>⚡</span>
                <span>Breaking</span>
            </div>

            <div class="relative flex-1 overflow-hidden h-5" data-ticker-wrapper>
                <div class="flex flex-col transition-transform duration-500 ease-in-out" data-ticker-track>
                    @foreach($tickerPosts as $index => $post)
                        <div class="h-5 flex items-center gap-2 truncate" data-ticker-item>
                            @if($post->category)
                                <span class="font-bold text-gray-400 dark:text-gray-500">[{{ $post->category->name }}]</span>
                            @endif
                            <a href="{{ route('posts.show', $post) }}" class="font-medium text-gray-800 hover:text-brand-600 dark:text-gray-200 dark:hover:text-brand-400 truncate transition">
                                {{ $post->title }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-1 shrink-0 text-gray-400">
                <button type="button" data-ticker-prev class="p-0.5 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Previous headline">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" data-ticker-next class="p-0.5 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Next headline">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
    </div>
@endif
