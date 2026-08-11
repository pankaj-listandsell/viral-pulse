@extends('layouts.guest')

@section('title', 'Create account')
@section('heading', 'Create your account')
@section('subheading', 'Join to comment, like and follow the stories you care about.')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-ui.label for="name" required>Name</x-ui.label>
            <x-ui.input id="name" name="name" value="{{ old('name') }}" autocomplete="name"
                        required autofocus :invalid="$errors->has('name')" />
            <x-ui.error for="name" />
        </div>

        <div>
            <x-ui.label for="email" required>Email</x-ui.label>
            <x-ui.input id="email" name="email" type="email" value="{{ old('email') }}"
                        autocomplete="username" required :invalid="$errors->has('email')" />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password" required>Password</x-ui.label>
            <x-ui.input id="password" name="password" type="password"
                        autocomplete="new-password" required :invalid="$errors->has('password')" />
            <x-ui.error for="password" />
        </div>

        <div>
            <x-ui.label for="password_confirmation" required>Confirm password</x-ui.label>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password" required />
        </div>

        <x-ui.button type="submit" class="w-full">Create account</x-ui.button>
    </form>
@endsection

@section('below')
    Already registered?
    <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
        Sign in
    </a>
@endsection
