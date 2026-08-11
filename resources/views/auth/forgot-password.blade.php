@extends('layouts.guest')

@section('title', 'Reset password')
@section('heading', 'Forgot your password?')
@section('subheading', 'Enter your email and we will send you a reset link.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-ui.label for="email" required>Email</x-ui.label>
            <x-ui.input id="email" name="email" type="email" value="{{ old('email') }}"
                        autocomplete="username" required autofocus :invalid="$errors->has('email')" />
            <x-ui.error for="email" />
        </div>

        <x-ui.button type="submit" class="w-full">Email reset link</x-ui.button>
    </form>
@endsection

@section('below')
    <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
        Back to sign in
    </a>
@endsection
