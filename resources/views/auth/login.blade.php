@extends('layouts.guest')

@section('title', 'Sign in')
@section('heading', 'Welcome back')
@section('subheading', 'Sign in to write, schedule and publish.')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-ui.label for="email" required>Email</x-ui.label>
            <x-ui.input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="username"
                placeholder="you@example.com"
                required
                autofocus
                :invalid="$errors->has('email')"
            />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password" required>Password</x-ui.label>

            {{-- The eye is worth the twelve lines: a mistyped password on a
                 phone keyboard is the most common reason a correct one is
                 rejected, and there is no reset link to fall back on. --}}
            <div class="relative" x-data="{ shown: false }">
                <x-ui.input
                    id="password"
                    name="password"
                    ::type="shown ? 'text' : 'password'"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="pr-11"
                    :invalid="$errors->has('password')"
                />

                <button
                    type="button"
                    @click="shown = !shown"
                    class="absolute inset-y-0 right-0 grid w-11 place-items-center text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                    :aria-label="shown ? 'Hide password' : 'Show password'"
                    tabindex="-1"
                >
                    <span x-show="!shown"><x-icon name="eye" class="size-4.5" /></span>
                    <span x-show="shown" x-cloak><x-icon name="eye-off" class="size-4.5" /></span>
                </button>
            </div>

            <x-ui.error for="password" />
        </div>

        <label class="flex items-center gap-2.5 text-sm text-gray-600 dark:text-gray-300">
            <x-ui.checkbox name="remember" value="1" />
            Keep me signed in
        </label>

        <x-ui.button type="submit" size="lg" class="w-full">Sign in</x-ui.button>
    </form>
@endsection

@section('below')
    <a href="{{ route('home') }}" class="font-bold text-gray-500 transition hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
        &larr; Back to the site
    </a>
@endsection
