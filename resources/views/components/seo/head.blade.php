@props(['seo' => []])

@php
    $settings = app(\App\Services\SettingsService::class);
    $siteName = $settings->get('site_name') ?: config('app.name');

    $title = $seo['title'] ?? null;

    // The site name is dropped once the headline is long enough to fill the
    // result on its own. Appending it there only pushes the part that matters
    // past where Google truncates.
    $fullTitle = match (true) {
        ! $title => $siteName,
        mb_strlen($title) > 55 => $title,
        default => "{$title} · {$siteName}",
    };
    $description = $seo['description'] ?? $settings->get('seo_default_description');
    $canonical = $seo['canonical'] ?? url()->current();
    $image = $seo['image'] ?? null;
    $robots = $seo['robots'] ?? $settings->get('seo_robots_default') ?: 'index, follow';
    $twitterHandle = $settings->get('seo_twitter_handle');
@endphp

<title>{{ $fullTitle }}</title>

@if($description)
    <meta name="description" content="{{ $description }}">
@endif
@if(! empty($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
@endif

<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

<x-seo.favicon :siteSettings="$settings->public()" />

@if(! empty($seo['feed']))
    {{-- A section feed alongside the site-wide one in the layout. --}}
    <link rel="alternate" type="application/rss+xml" title="{{ $title }} · RSS" href="{{ $seo['feed'] }}">
@endif

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
<meta property="og:title" content="{{ $title ?: $siteName }}">
@if($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if($image)
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:alt" content="{{ $title ?: $siteName }}">
@endif
@if(($seo['type'] ?? null) === 'article')
    @isset($seo['published_at'])
        <meta property="article:published_time" content="{{ $seo['published_at']?->toIso8601String() }}">
    @endisset
    @isset($seo['modified_at'])
        <meta property="article:modified_time" content="{{ $seo['modified_at']?->toIso8601String() }}">
    @endisset
    @isset($seo['author'])
        <meta property="article:author" content="{{ $seo['author'] }}">
    @endisset
@endif

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title ?: $siteName }}">
@if($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif
@if($twitterHandle)
    <meta name="twitter:site" content="{{ Str::start($twitterHandle, '@') }}">
@endif

@if($verification = $settings->get('google_site_verification'))
    <meta name="google-site-verification" content="{{ $verification }}">
@endif

{{-- Structured data. Rendered server-side, so a crawler that runs no
     JavaScript still sees it. --}}
@foreach($seo['schemas'] ?? [] as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
