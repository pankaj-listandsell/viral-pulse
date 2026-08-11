@extends(auth()->user()->canAccessAdminPanel() ? 'layouts.admin' : 'layouts.public')

@section('title', 'Profile')
@section('heading', 'Profile')
@section('subheading', 'Manage your account details and password.')

@section('content')
    <div class="grid max-w-3xl gap-4">

        <x-ui.card title="Account details" subtitle="Your name and email as they appear on the site.">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
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
                    <x-ui.error for="email" />
                </div>

                <div>
                    <x-ui.label for="bio">Bio</x-ui.label>
                    <textarea
                        id="bio"
                        name="bio"
                        rows="3"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs
                               transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/40 focus:outline-none
                               dark:border-gray-700 dark:bg-gray-900"
                    >{{ old('bio', $user->bio) }}</textarea>
                    <x-ui.error for="bio" />
                </div>

                <x-ui.button type="submit">Save changes</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Password" subtitle="Use a long, unique password.">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
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

        @unless($user->canAccessAdminPanel())
            <x-ui.card title="Delete account" subtitle="This permanently removes your account.">
                <form
                    method="POST"
                    action="{{ route('profile.destroy') }}"
                    x-data="confirmable('Your account and its data will be removed. This cannot be undone.')"
                    x-ref="form"
                    @submit.prevent="open = true"
                    class="space-y-4"
                >
                    @csrf
                    @method('DELETE')

                    <div>
                        <x-ui.label for="delete_password" required>Confirm your password</x-ui.label>
                        <x-ui.input id="delete_password" name="password" type="password"
                                    autocomplete="current-password" required
                                    :invalid="$errors->has('password')" />
                        <x-ui.error for="password" />
                    </div>

                    <x-ui.button type="submit" variant="danger">Delete account</x-ui.button>

                    <div
                        x-show="open"
                        x-transition.opacity
                        class="fixed inset-0 z-50 grid place-items-center bg-gray-900/50 p-4 backdrop-blur-xs"
                        x-cloak
                    >
                        <div class="w-full max-w-sm rounded-xl border border-gray-200 bg-white p-5 shadow-xl
                                    dark:border-gray-800 dark:bg-gray-900"
                             @click.outside="open = false">
                            <h3 class="text-sm font-semibold">Are you sure?</h3>
                            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
                            <div class="mt-5 flex justify-end gap-2">
                                <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                                <x-ui.button variant="danger" size="sm" @click="confirm()">Delete</x-ui.button>
                            </div>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        @endunless

    </div>
@endsection
