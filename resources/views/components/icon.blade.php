@props(['name'])

@php
    // Paths live in config so `php artisan config:cache` turns this into a
    // plain array lookup with no file I/O per icon.
    $paths = config("icons.{$name}");
@endphp

@if($paths)
    <svg
        {{ $attributes->merge(['class' => 'size-5']) }}
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >{!! $paths !!}</svg>
@endif
