@extends('layouts.public')

@php
    $contactEmail = app(\App\Services\SettingsService::class)->get('contact_email');
@endphp

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <h1 class="text-3xl font-semibold tracking-tight">Contact</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Story tips, corrections, business enquiries — all welcome.
            @if($contactEmail)
                You can also email <a href="mailto:{{ $contactEmail }}" class="text-brand-600 hover:underline">{{ $contactEmail }}</a>.
            @endif
        </p>

        @if(session('success'))
            <p class="mt-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800
                      dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300">
                {{ session('success') }}
            </p>
        @endif

        <form method="POST" action="{{ route('contact.submit') }}" class="mt-8 space-y-5">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium">Your name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    @error('name')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium">Your email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    @error('email')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="subject" class="mb-1.5 block text-sm font-medium">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                @error('subject')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="message" class="mb-1.5 block text-sm font-medium">Message</label>
                <textarea id="message" name="message" rows="6" required
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                >{{ old('message') }}</textarea>
                @error('message')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            @error('website')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

            <button type="submit"
                    class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700">
                Send message
            </button>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                We keep your message and email only to reply to you. See the
                <a href="{{ route('pages.show', 'privacy') }}" class="underline">privacy policy</a>.
            </p>
        </form>
    </div>
@endsection
