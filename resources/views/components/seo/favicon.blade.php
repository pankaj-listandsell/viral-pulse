@php
    // Falls back to the file Laravel ships, so there is always something in the
    // tab rather than the browser's blank-page icon.
    $favicon = ($siteSettings ?? [])['site_favicon'] ?? null;
    $faviconUrl = $favicon
        ? Storage::disk(config('site.media.disk'))->url($favicon)
        : asset('favicon.ico');
@endphp

<link rel="icon" href="{{ $faviconUrl }}" sizes="any">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
