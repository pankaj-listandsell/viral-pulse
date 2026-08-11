<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title>@yield('code') · {{ config('app.name') }}</title>

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

    @vite(['resources/css/app.css'])
</head>
<body class="grid h-full place-items-center bg-gray-50 px-4 font-sans text-gray-900 antialiased
             dark:bg-gray-950 dark:text-gray-100">
    <div class="w-full max-w-md text-center">
        <p class="text-6xl font-semibold tracking-tight text-brand-600 dark:text-brand-500">@yield('code')</p>
        <h1 class="mt-4 text-xl font-semibold tracking-tight">@yield('title')</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">@yield('message')</p>

        <div class="mt-8 flex justify-center gap-2">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white
                      transition hover:bg-brand-700">
                Back to home
            </a>
        </div>
    </div>
</body>
</html>
