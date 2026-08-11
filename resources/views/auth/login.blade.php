@extends('layouts.guest')

@section('title', 'Sign in')
@section('heading', 'Sign in')
@section('subheading', 'Administrator access.')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-ui.label for="email" required>Email</x-ui.label>
            <x-ui.input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="username"
                required
                autofocus
                :invalid="$errors->has('email')"
            />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password" required>Password</x-ui.label>
            <x-ui.input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                :invalid="$errors->has('password')"
            />
            <x-ui.error for="password" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <x-ui.checkbox name="remember" value="1" />
                Remember me
            </label>

            <a href="{{ route('password.request') }}"
               class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                Forgot password?
            </a>
        </div>

        <x-ui.button type="submit" class="w-full">Sign in</x-ui.button>
    </form>
@endsection

@section('below')
    <a href="{{ route('home') }}" class="font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">
        Back to the site
    </a>
@endsection
