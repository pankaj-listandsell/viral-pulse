@extends('layouts.public')

@php
    $feed = app(\App\Services\ContentFeedService::class);
    $categories = $feed->popularCategories(50);
    $recent = $feed->latest(50);
@endphp

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <h1 class="text-3xl font-semibold tracking-tight">Sitemap</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            A human-readable index. The machine-readable one lives at
            <a href="{{ url('sitemap.xml') }}" class="text-brand-600 hover:underline">/sitemap.xml</a>.
        </p>

        <div class="mt-10 grid gap-10 sm:grid-cols-2">
            <section>
                <h2 class="text-lg font-semibold tracking-tight">Pages</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Home</a></li>
                    <li><a href="{{ route('latest') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Latest</a></li>
                    <li><a href="{{ route('trending') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Trending</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Categories</a></li>
                    <li><a href="{{ route('pages.show', 'about') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Contact</a></li>
                    <li><a href="{{ route('pages.show', 'privacy') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Privacy Policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Terms</a></li>
                    <li><a href="{{ route('pages.show', 'disclaimer') }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Disclaimer</a></li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold tracking-tight">Categories</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse($categories as $category)
                        <li>
                            <a href="{{ route('categories.show', $category) }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">
                                {{ $category->name }}
                            </a>
                        </li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400">None yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        @if($recent->isNotEmpty())
            <section class="mt-10">
                <h2 class="text-lg font-semibold tracking-tight">Recent stories</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($recent as $post)
                        <li>
                            <a href="{{ route('posts.show', $post) }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">
                                {{ $post->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
