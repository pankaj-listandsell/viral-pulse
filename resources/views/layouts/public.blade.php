@php
    $nav = app(\App\Services\ContentFeedService::class)->navigation();

    /*
     * Outfit and Instrument Sans carry no Devanagari glyphs. Left alone, a
     * Hindi page falls back to whatever the device happens to have - Nirmala UI
     * on Windows, something else on Android - and the page reads as an
     * afterthought. A locale that needs its own face names it in config, and
     * only that locale pays to download it.
     */
    $localeMeta = app(\App\Services\LocaleService::class)->meta();
    $localeFont = $localeMeta['font'] ?? null;
    $localeFontStack = $localeMeta['font_stack'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo.head :seo="$seo ?? []" />

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700|instrument-sans:400,500,600,700{{ $localeFont ? '|'.$localeFont : '' }}&display=swap" rel="stylesheet">

    <link rel="alternate" type="application/rss+xml"
          title="{{ $siteSettings['site_name'] ?? config('app.name') }}" href="{{ url('feed.xml') }}">

    {{-- Applied before first paint so the page never flashes or loses dark theme on refresh --}}
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else if (stored === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @if($analyticsId = ($siteSettings['google_analytics_id'] ?? null))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($analyticsId));
        </script>
    @endif

    @if(($siteSettings['adsense_enabled'] ?? false) && ($client = $siteSettings['adsense_client_id'] ?? null))
        <script async crossorigin="anonymous"
                src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $client }}"></script>
    @endif

    @if($oneSignalAppId = ($siteSettings['onesignal_app_id'] ?? null))
        <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
        <script>
            window.OneSignal = window.OneSignal || [];
            OneSignal.push(async function() {
                await OneSignal.init({
                    appId: @json($oneSignalAppId),
                    safari_web_id: @json($siteSettings['onesignal_safari_web_id'] ?? ''),
                    allowLocalhostAsSecureOrigin: true,
                });
            });
        </script>
    @endif

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($localeFontStack)
        {{-- After @vite on purpose: this has to win over the --font-sans that
             Tailwind's @theme block emits onto :root. The Latin faces stay in
             the stack behind it so digits, brand names and any English left on
             a Hindi page still render in the site's own type. --}}
        <style>
            :root {
                --font-sans: "{{ $localeFontStack }}", 'Outfit', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif,
                    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
            }
        </style>
    @endif
</head>
<body class="flex h-full flex-col bg-white font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

<a href="#main"
   class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg
          focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-white">
    Skip to content
</a>

@include('public.partials.header', ['nav' => $nav])
@include('public.partials.breaking-ticker')

<main id="main" class="flex-1">
    @yield('content')
</main>

@include('public.partials.footer', ['nav' => $nav])

<div data-island="PushNotificationPrompt" data-props="{{ json_encode(['appId' => $siteSettings['onesignal_app_id'] ?? null]) }}"></div>

</body>
</html>
