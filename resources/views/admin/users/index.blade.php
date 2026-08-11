@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', $users->total() . ' registered ' . Str::plural('account', $users->total()))

@section('content')
    <form method="GET" class="mb-4 flex gap-2">
        <div class="relative max-w-sm flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
            <x-ui.input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or email" class="pl-9" />
        </div>
        <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
    </form>

    <x-ui.card :padded="false">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase
                              dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">User</th>
                        <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Comments</th>
                        <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Joined</th>
                        <th scope="col" class="hidden px-5 py-3 font-medium lg:table-cell">Last seen</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($users as $user)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-gray-100 text-xs font-semibold
                                                 dark:bg-gray-800">
                                        {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-1.5 truncate font-medium">
                                            {{ $user->name }}
                                            @if($user->is_admin)
                                                <x-ui.badge color="brand">Admin</x-ui.badge>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-5 py-3 tabular-nums text-gray-500 sm:table-cell dark:text-gray-400">
                                {{ $user->comments_count }}
                            </td>
                            <td class="hidden px-5 py-3 text-gray-500 md:table-cell dark:text-gray-400">
                                {{ $user->created_at->format('M j, Y') }}
                            </td>
                            <td class="hidden px-5 py-3 text-gray-500 lg:table-cell dark:text-gray-400">
                                {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="px-5 py-3">
                                <x-ui.badge :color="$user->is_active ? 'green' : 'red'">
                                    {{ $user->is_active ? 'Active' : 'Suspended' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if(! auth()->user()->is($user))
                                    <div class="flex items-center justify-end gap-1">
                                        <x-ui.confirm-form
                                            :action="route('admin.users.toggle-active', $user)"
                                            method="POST"
                                            :title="$user->is_active ? 'Suspend this account?' : 'Reactivate this account?'"
                                            :message="$user->is_active
                                                ? 'They will be signed out and unable to sign back in.'
                                                : 'They will be able to sign in again.'"
                                            :confirm-label="$user->is_active ? 'Suspend' : 'Reactivate'"
                                            :variant="$user->is_active ? 'danger' : 'primary'"
                                        >
                                            <x-slot:trigger>
                                                <button type="submit"
                                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-600 transition
                                                               hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                                                    {{ $user->is_active ? 'Suspend' : 'Reactivate' }}
                                                </button>
                                            </x-slot:trigger>
                                        </x-ui.confirm-form>

                                        <x-ui.confirm-form
                                            :action="route('admin.users.destroy', $user)"
                                            title="Delete this account?"
                                            message="Accounts that have written posts cannot be deleted, so bylines stay intact."
                                        >
                                            <x-slot:trigger>
                                                <button type="submit"
                                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600
                                                               dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                                        aria-label="Delete {{ $user->name }}">
                                                    <x-icon name="trash-2" class="size-4" />
                                                </button>
                                            </x-slot:trigger>
                                        </x-ui.confirm-form>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">You</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if($users->hasPages())
        <div class="mt-4">{{ $users->links() }}</div>
    @endif
@endsection
