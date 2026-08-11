@extends('layouts.public')

@section('title', 'Home')

@section('content')
    <div class="rounded-2xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
        <span class="grid size-12 place-items-center rounded-full bg-brand-50 text-brand-600 mx-auto
                     dark:bg-brand-500/15 dark:text-brand-400">
            <x-icon name="flame" class="size-6" />
        </span>

        <h2 class="mt-4 text-lg font-semibold">The public site lands in phase 5</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
            The admin panel is live and the content engine is being built. This placeholder keeps the
            <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-gray-800">home</code> route resolvable
            in the meantime.
        </p>

        @auth
            @if(auth()->user()->canAccessAdminPanel())
                <div class="mt-6">
                    <x-ui.button :href="route('admin.dashboard')">Open the admin panel</x-ui.button>
                </div>
            @endif
        @else
            <div class="mt-6">
                <x-ui.button :href="route('login')">Sign in</x-ui.button>
            </div>
        @endauth
    </div>
@endsection
