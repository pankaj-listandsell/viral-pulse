@props(['title', 'updated' => true])

@php
    $settings = app(\App\Services\SettingsService::class);
    $contactEmail = $settings->get('contact_email');
@endphp

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="text-3xl font-semibold tracking-tight">{{ $title }}</h1>

    @if($updated)
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last updated {{ now()->format('F Y') }}</p>
    @endif

    <div class="prose prose-lg mt-8 max-w-none dark:prose-invert prose-headings:tracking-tight prose-a:text-brand-600">
        {{ $slot }}
    </div>

    @unless($contactEmail)
        <p class="mt-10 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900
                  dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <strong>Add your contact email</strong> under Site Settings. Ad networks and most privacy laws expect a
            reachable address on these pages, and this notice disappears once one is set.
        </p>
    @endunless
</div>
