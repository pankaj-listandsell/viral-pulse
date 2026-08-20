<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- The admin panel must never be indexed. --}}
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Dashboard') · {{ $siteSettings['site_name'] ?? config('app.name') }} Admin</title>

    <x-seo.favicon :siteSettings="$siteSettings" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    {{-- Applied before first paint so the panel never flashes light then dark. --}}
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @routes
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

<div class="flex min-h-full">
    @include('admin.partials.sidebar')

    {{-- Mobile backdrop --}}
    <div
        x-data
        x-show="$store.sidebar.open"
        x-transition.opacity
        @click="$store.sidebar.close()"
        class="fixed inset-0 z-30 bg-gray-900/50 backdrop-blur-xs lg:hidden"
        x-cloak
    ></div>

    <div
        x-data
        class="flex min-w-0 flex-1 flex-col transition-all duration-200"
        :class="$store.sidebar.collapsed ? 'lg:pl-16' : 'lg:pl-64'"
    >
        @include('admin.partials.topbar')

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                @hasSection('heading')
                    <header class="mb-7 flex flex-wrap items-end justify-between gap-3">
                        <div class="min-w-0">
                            <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-[1.75rem] dark:text-gray-50">
                                @yield('heading')
                            </h1>
                            @hasSection('subheading')
                                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">@yield('subheading')</p>
                            @endif
                        </div>

                        @hasSection('actions')
                            <div class="flex items-center gap-2">@yield('actions')</div>
                        @endif
                    </header>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>

<x-ui.toasts />

@stack('scripts')
</body>
</html>
