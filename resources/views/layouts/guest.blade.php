@php
    $siteName = $siteSettings['site_name'] ?? config('app.name');
    $tagline = $siteSettings['site_description'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title') · {{ $siteName }}</title>

    <x-seo.favicon :siteSettings="$siteSettings" />

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

    <style>
        /* The one flourish this page owns. A flat panel looked like a form
           served by mistake; this reads as the front door of the publication. */
        .vp-auth-sky {
            background:
                radial-gradient(1.5px 1.5px at 18% 24%, rgba(255,255,255,.7), transparent 60%),
                radial-gradient(1px 1px at 72% 16%, rgba(255,255,255,.55), transparent 60%),
                radial-gradient(1.5px 1.5px at 44% 68%, rgba(255,255,255,.6), transparent 60%),
                radial-gradient(1px 1px at 86% 58%, rgba(255,255,255,.45), transparent 60%),
                radial-gradient(1px 1px at 28% 86%, rgba(255,255,255,.4), transparent 60%);
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="h-full bg-white font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

<div class="flex min-h-full">

    {{-- Brand panel. Decorative, so it is the half that disappears on a phone. --}}
    <div class="relative hidden w-1/2 overflow-hidden bg-[#0f1421] lg:flex lg:flex-col lg:justify-between lg:p-12 xl:w-[45%]">
        <div class="vp-auth-sky pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -left-24 -top-32 size-[30rem] rounded-full bg-brand-600/25 blur-[130px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 size-[28rem] rounded-full bg-indigo-600/20 blur-[130px]" aria-hidden="true"></div>

        <a href="{{ route('home') }}" class="relative flex items-center gap-3 text-white">
            <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 shadow-lg shadow-brand-900/40">
                <x-icon name="flame" class="size-6" />
            </span>
            <span class="text-lg font-black tracking-tight">{{ $siteName }}</span>
        </a>

        <div class="relative max-w-md">
            <p class="text-3xl font-black leading-tight tracking-tight text-white xl:text-4xl">
                The newsroom
                <span class="block bg-gradient-to-r from-brand-300 via-rose-200 to-indigo-200 bg-clip-text text-transparent">
                    behind the site.
                </span>
            </p>

            @if($tagline)
                <p class="mt-4 text-sm leading-relaxed text-white/55">{{ $tagline }}</p>
            @endif
        </div>

        <p class="relative text-xs text-white/35">
            &copy; {{ now()->year }} {{ $siteName }}
        </p>
    </div>

    {{-- Form --}}
    <div class="flex flex-1 flex-col justify-center px-5 py-12 sm:px-10">
        <div class="mx-auto w-full max-w-sm">

            <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                    <x-icon name="flame" class="size-5" />
                </span>
                <span class="text-base font-black tracking-tight">{{ $siteName }}</span>
            </a>

            @hasSection('heading')
                <div class="mb-7">
                    <h1 class="text-2xl font-black tracking-tight sm:text-3xl">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">@yield('subheading')</p>
                    @endif
                </div>
            @endif

            @yield('content')

            @hasSection('below')
                <p class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">@yield('below')</p>
            @endif
        </div>
    </div>
</div>

<x-ui.toasts />
</body>
</html>
