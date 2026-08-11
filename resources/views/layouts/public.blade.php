<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') · @endif{{ $siteSettings['site_name'] ?? config('app.name') }}</title>

    <meta name="description" content="@yield('meta_description', $siteSettings['site_description'] ?? '')">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @stack('head')

    {{-- Vue islands only. The page content itself is server-rendered HTML. --}}
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full flex-col bg-white font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

<a href="#main"
   class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg
          focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-white">
    Skip to content
</a>

<header class="border-b border-gray-200 dark:border-gray-800">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
                <x-icon name="flame" class="size-5" />
            </span>
            <span class="font-semibold tracking-tight">
                {{ $siteSettings['site_name'] ?? config('app.name') }}
            </span>
        </a>

        <nav class="flex items-center gap-1 text-sm">
            @auth
                @if(auth()->user()->canAccessAdminPanel())
                    <a href="{{ route('admin.dashboard') }}"
                       class="rounded-lg px-3 py-1.5 font-medium text-gray-600 transition hover:bg-gray-100
                              dark:text-gray-300 dark:hover:bg-gray-800">Admin</a>
                @endif
                <a href="{{ route('profile.edit') }}"
                   class="rounded-lg px-3 py-1.5 font-medium text-gray-600 transition hover:bg-gray-100
                          dark:text-gray-300 dark:hover:bg-gray-800">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg px-3 py-1.5 font-medium text-gray-600 transition hover:bg-gray-100
                                   dark:text-gray-300 dark:hover:bg-gray-800">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}"
                   class="rounded-lg px-3 py-1.5 font-medium text-gray-600 transition hover:bg-gray-100
                          dark:text-gray-300 dark:hover:bg-gray-800">Sign in</a>
            @endauth
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        @hasSection('heading')
            <h1 class="mb-6 text-2xl font-semibold tracking-tight">@yield('heading')</h1>
        @endif

        @yield('content')
    </div>
</main>

<footer class="border-t border-gray-200 dark:border-gray-800">
    <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-gray-500 sm:px-6 dark:text-gray-400">
        &copy; {{ now()->year }} {{ $siteSettings['site_name'] ?? config('app.name') }}
    </div>
</footer>

@stack('scripts')
</body>
</html>
