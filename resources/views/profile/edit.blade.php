@extends('layouts.admin')

@section('title', 'Profile')
@section('heading', 'Profile')
@section('subheading', 'Your account details and password.')

@section('content')
    <div class="grid max-w-3xl gap-4">

        <x-ui.card title="Account details" subtitle="Your name is used as the byline on posts you write.">
            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-ui.label for="name" required>Name</x-ui.label>
                    <x-ui.input id="name" name="name" value="{{ old('name', $user->name) }}"
                                required :invalid="$errors->has('name')" />
                    <x-ui.error for="name" />
                </div>

                <div>
                    <x-ui.label for="username">Username</x-ui.label>
                    <x-ui.input id="username" name="username" value="{{ old('username', $user->username) }}"
                                :invalid="$errors->has('username')" />
                    <x-ui.error for="username" />
                </div>

                <div>
                    <x-ui.label for="email" required>Email</x-ui.label>
                    <x-ui.input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                                required :invalid="$errors->has('email')" />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        This is your sign-in address and where password resets are sent.
                    </p>
                    <x-ui.error for="email" />
                </div>

                <div>
                    <x-ui.label for="bio">Bio</x-ui.label>
                    <x-ui.textarea id="bio" name="bio" rows="3" :invalid="$errors->has('bio')"
                    >{{ old('bio', $user->bio) }}</x-ui.textarea>
                    <x-ui.error for="bio" />
                </div>

                <x-ui.button type="submit">Save changes</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Password" subtitle="Use a long, unique password.">
            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-ui.label for="current_password" required>Current password</x-ui.label>
                    <x-ui.input id="current_password" name="current_password" type="password"
                                autocomplete="current-password" required
                                :invalid="$errors->has('current_password')" />
                    <x-ui.error for="current_password" />
                </div>

                <div>
                    <x-ui.label for="new_password" required>New password</x-ui.label>
                    <x-ui.input id="new_password" name="password" type="password"
                                autocomplete="new-password" required :invalid="$errors->has('password')" />
                    <x-ui.error for="password" />
                </div>

                <div>
                    <x-ui.label for="password_confirmation" required>Confirm new password</x-ui.label>
                    <x-ui.input id="password_confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password" required />
                </div>

                <x-ui.button type="submit">Update password</x-ui.button>
            </form>
        </x-ui.card>

    </div>
@endsection
