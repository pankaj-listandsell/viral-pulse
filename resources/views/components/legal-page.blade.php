@props(['title', 'updated' => true, 'crumbs' => []])

@php
    $settings = app(\App\Services\SettingsService::class);
    $contactEmail = $settings->get('contact_email');
@endphp

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <x-breadcrumb :crumbs="$crumbs" />

    <header class="border-b border-gray-200 pb-6 dark:border-gray-800">
        <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl dark:text-white">{{ $title }}</h1>

        @if($updated)
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Last updated <time datetime="{{ now()->format('Y-m') }}">{{ now()->format('F Y') }}</time>
            </p>
        @endif
    </header>

    <div class="prose prose-lg mt-8 max-w-none dark:prose-invert prose-headings:tracking-tight prose-headings:font-black prose-a:text-brand-600">
        {{ $slot }}
    </div>

    <nav aria-label="Related pages" class="mt-12 flex flex-wrap gap-2 border-t border-gray-200 pt-6 dark:border-gray-800">
        @foreach([
            ['About', route('pages.show', 'about')],
            ['Contact', route('contact')],
            ['Privacy Policy', route('pages.show', 'privacy')],
            ['Terms of Service', route('pages.show', 'terms')],
            ['Disclaimer', route('pages.show', 'disclaimer')],
        ] as [$label, $href])
            @continue($label === $title)
            <a href="{{ $href }}"
               class="rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-bold text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:text-gray-400 dark:hover:text-brand-400">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    {{-- A note to whoever runs the site, not to its readers: shown only to a
         signed-in admin. Printed for everyone it read as a public confession
         that the site is half-configured, on the very pages an ad network and
         a cautious reader open first. --}}
    @if(! $contactEmail && auth()->user()?->isAdmin())
        <p class="mt-8 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900
                  dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <strong>Add your contact email</strong> under Site Settings. Ad networks and most privacy laws expect a
            reachable address on these pages, and this notice disappears once one is set. Only admins see this.
        </p>
    @endif
</div>
