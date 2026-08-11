<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title') · {{ $siteSettings['site_name'] ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="flex h-full flex-col bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

<div class="flex flex-1 flex-col items-center justify-center px-4 py-10">
    <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2.5">
        <span class="grid size-9 place-items-center rounded-lg bg-brand-600 text-white">
            <x-icon name="flame" class="size-5" />
        </span>
        <span class="text-lg font-semibold tracking-tight">
            {{ $siteSettings['site_name'] ?? config('app.name') }}
        </span>
    </a>

    <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        @hasSection('heading')
            <div class="mb-6">
                <h1 class="text-lg font-semibold tracking-tight">@yield('heading')</h1>
                @hasSection('subheading')
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@yield('subheading')</p>
                @endif
            </div>
        @endif

        @yield('content')
    </div>

    @hasSection('below')
        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">@yield('below')</p>
    @endif
</div>

<x-ui.toasts />
</body>
</html>
