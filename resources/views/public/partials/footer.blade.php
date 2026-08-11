@php
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $socials = array_filter([
        'Facebook' => $siteSettings['social_facebook'] ?? null,
        'X' => $siteSettings['social_twitter'] ?? null,
        'Instagram' => $siteSettings['social_instagram'] ?? null,
        'YouTube' => $siteSettings['social_youtube'] ?? null,
        'Telegram' => $siteSettings['social_telegram'] ?? null,
    ]);
@endphp

<footer class="mt-16 border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <p class="flex items-center gap-2.5 text-base font-semibold tracking-tight">
                    <span class="grid size-7 place-items-center rounded-lg bg-brand-600 text-white">
                        <x-icon name="flame" class="size-4" />
                    </span>
                    {{ $siteName }}
                </p>
                <p class="mt-3 max-w-sm text-sm text-gray-600 dark:text-gray-400">
                    {{ $siteSettings['site_description'] ?? 'Trending stories, explained fast.' }}
                </p>

                @if($socials)
                    <ul class="mt-4 flex flex-wrap gap-3 text-sm">
                        @foreach($socials as $label => $url)
                            <li>
                                <a href="{{ $url }}" rel="noopener noreferrer" target="_blank"
                                   class="text-gray-500 transition hover:text-brand-600 dark:text-gray-400">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <h2 class="text-sm font-semibold">Explore</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('latest') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Latest</a></li>
                    <li><a href="{{ route('trending') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Trending</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Categories</a></li>
                    <li><a href="{{ route('sitemap.page') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Sitemap</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold">Site</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('pages.show', 'about') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Contact</a></li>
                    <li><a href="{{ route('pages.show', 'privacy') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Privacy Policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Terms</a></li>
                    <li><a href="{{ route('pages.show', 'disclaimer') }}" class="text-gray-600 transition hover:text-brand-600 dark:text-gray-400">Disclaimer</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-6 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <p>&copy; {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
            <p>
                <a href="{{ url('feed.xml') }}" class="transition hover:text-brand-600">RSS</a>
            </p>
        </div>
    </div>
</footer>

<x-ads.footer />
