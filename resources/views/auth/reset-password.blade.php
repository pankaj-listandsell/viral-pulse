@extends('layouts.guest')

@section('title', 'Choose a new password')
@section('heading', 'Choose a new password')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <x-ui.label for="email" required>Email</x-ui.label>
            <x-ui.input id="email" name="email" type="email" value="{{ old('email', $email) }}"
                        autocomplete="username" required :invalid="$errors->has('email')" />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password" required>New password</x-ui.label>
            <x-ui.input id="password" name="password" type="password"
                        autocomplete="new-password" required autofocus :invalid="$errors->has('password')" />
            <x-ui.error for="password" />
        </div>

        <div>
            <x-ui.label for="password_confirmation" required>Confirm new password</x-ui.label>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password" required />
        </div>

        <x-ui.button type="submit" class="w-full">Reset password</x-ui.button>
    </form>
@endsection
