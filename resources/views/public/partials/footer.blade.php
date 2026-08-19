@php
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $logo = $siteSettings['site_logo'] ?? null;
    $horoscopeEnabled = $siteSettings['horoscope_enabled'] ?? true;

    $socials = array_filter([
        'Facebook' => $siteSettings['social_facebook'] ?? null,
        'X' => $siteSettings['social_twitter'] ?? null,
        'Instagram' => $siteSettings['social_instagram'] ?? null,
        'YouTube' => $siteSettings['social_youtube'] ?? null,
        'Telegram' => $siteSettings['social_telegram'] ?? null,
    ]);
@endphp

<footer class="mt-20 border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/40">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">

        <div class="grid gap-10 lg:grid-cols-12">

            {{-- Brand --}}
            <div class="lg:col-span-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ $siteName }} home">
                    @if($logo)
                        <img src="{{ Storage::disk(config('site.media.disk'))->url($logo) }}" alt=""
                             class="size-9 rounded-xl object-contain" width="36" height="36" loading="lazy">
                    @else
                        <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                            <x-icon name="flame" class="size-5" />
                        </span>
                    @endif
                    <span class="text-xl font-black tracking-tight">{{ $siteName }}</span>
                </a>

                <p class="mt-4 max-w-sm text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ $siteSettings['site_description'] ?? 'Trending stories, explained fast.' }}
                </p>

                @if($socials)
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach($socials as $label => $url)
                            <li>
                                <a href="{{ $url }}" rel="noopener noreferrer" target="_blank"
                                   class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:border-brand-500 hover:text-brand-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-brand-400 dark:hover:text-brand-400">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a href="{{ url('feed.xml') }}"
                   class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 transition hover:text-brand-600 dark:text-gray-400">
                    <span aria-hidden="true">📡</span> Subscribe via RSS
                </a>
            </div>

            {{-- Sections: the same categories the header carries, so every
                 section is one click from the bottom of any page too. --}}
            <div class="lg:col-span-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-400">Sections</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach($nav as $category)
                        <li>
                            <a href="{{ route('categories.show', $category) }}"
                               class="flex items-center gap-2 text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                                <span class="size-1.5 rounded-full" style="background-color: {{ $category->color ?? '#ef4444' }};" aria-hidden="true"></span>
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('categories.index') }}" class="font-bold text-brand-600 transition hover:text-brand-700 dark:text-brand-400">
                            All categories &rarr;
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Explore --}}
            <div class="lg:col-span-2">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-400">Explore</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('latest') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Latest</a></li>
                    <li><a href="{{ route('trending') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Trending</a></li>
                    @if($horoscopeEnabled)
                        <li><a href="{{ route('horoscope') }}" class="flex items-center gap-1 text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400"><span aria-hidden="true">✨</span> Horoscope</a></li>
                        <li><a href="{{ route('horoscope.compatibility') }}" class="flex items-center gap-1 text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400"><span aria-hidden="true">💖</span> Love match</a></li>
                    @endif
                    <li><a href="{{ route('sitemap.page') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Sitemap</a></li>
                </ul>
            </div>

            {{-- Site --}}
            <div class="lg:col-span-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-400">Site</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('pages.show', 'about') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">About us</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Contact</a></li>
                    <li><a href="{{ route('pages.show', 'privacy') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Privacy policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Terms of use</a></li>
                    <li><a href="{{ route('pages.show', 'disclaimer') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">Disclaimer</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-gray-200 pt-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:text-gray-400">
            <p>&copy; {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
            {{-- Saying who stands behind the reporting is what Google's quality
                 guidelines ask of a publisher, and readers check it too. --}}
            <p class="max-w-md sm:text-right">
                Published by {{ $siteName }}. Every story is edited before it goes live —
                <a href="{{ route('contact') }}" class="font-bold text-gray-700 underline-offset-4 transition hover:text-brand-600 hover:underline dark:text-gray-300">tell us if we got something wrong</a>.
            </p>
        </div>
    </div>
</footer>

<x-ads.footer />
