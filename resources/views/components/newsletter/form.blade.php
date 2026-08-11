@props(['compact' => false])

@php
    $enabled = app(\App\Services\SettingsService::class)->bool('newsletter_enabled', true);
@endphp

@if($enabled)
    <section
        {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-900/50']) }}
        aria-labelledby="newsletter-heading-{{ $compact ? 'compact' : 'main' }}"
    >
        <h2 id="newsletter-heading-{{ $compact ? 'compact' : 'main' }}" class="text-base font-semibold tracking-tight">
            Get the good stuff
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            New stories in your inbox. No spam, unsubscribe in one click.
        </p>

        {{-- Plain form first, so subscribing works with JavaScript disabled;
             the island upgrades it to submit without a page reload. --}}
        <div class="mt-4" data-island="NewsletterForm"
             data-props="{{ json_encode(['action' => route('newsletter.subscribe')]) }}">
            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="space-y-2">
                @csrf

                <label for="newsletter-email-{{ $compact ? 'c' : 'm' }}" class="sr-only">Email address</label>
                <input
                    id="newsletter-email-{{ $compact ? 'c' : 'm' }}"
                    type="email"
                    name="email"
                    required
                    placeholder="you@example.com"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                >

                {{-- Honeypot. Hidden from people, irresistible to bots. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website-{{ $compact ? 'c' : 'm' }}">Website</label>
                    <input id="website-{{ $compact ? 'c' : 'm' }}" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                    Subscribe
                </button>
            </form>

            @error('email')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </section>
@endif
