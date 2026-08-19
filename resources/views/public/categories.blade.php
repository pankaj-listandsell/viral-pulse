@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">

        <x-breadcrumb :crumbs="[['name' => 'Categories', 'url' => route('categories.index')]]" />

        <header class="mb-10 border-b border-gray-200 pb-6 dark:border-gray-800">
            <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                All categories
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-gray-600 sm:text-base dark:text-gray-400">
                Every topic we cover, from breaking news and technology to entertainment, sport and travel.
                Pick a section to read its latest stories.
            </p>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                <span class="rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-900">
                    {{ $categories->count() }} {{ Str::plural('section', $categories->count()) }}
                    &middot; {{ number_format($categories->sum('posts_count')) }} stories
                </span>
            </p>
        </header>

        @if($categories->isEmpty())
            <p class="rounded-3xl border border-dashed border-gray-300 px-6 py-16 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                No categories with published stories yet.
            </p>
        @else
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    @php $accent = $category->color ?? '#ef4444'; @endphp

                    <li>
                        <a href="{{ route('categories.show', $category) }}"
                           class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-200/50 dark:border-gray-800 dark:bg-gray-900/40 dark:hover:shadow-none">
                            <span class="absolute inset-x-0 top-0 h-1.5" style="background-color: {{ $accent }};" aria-hidden="true"></span>
                            <span class="pointer-events-none absolute -right-16 -top-16 size-40 rounded-full opacity-10 blur-2xl transition duration-500 group-hover:opacity-25"
                                  style="background-color: {{ $accent }};" aria-hidden="true"></span>

                            <span class="relative flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2.5">
                                    <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $accent }};" aria-hidden="true"></span>
                                    <span class="text-lg font-black tracking-tight text-gray-900 dark:text-white">{{ $category->name }}</span>
                                </span>

                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-black text-white" style="background-color: {{ $accent }};">
                                    {{ $category->posts_count }}
                                </span>
                            </span>

                            @if($category->description)
                                <span class="relative mt-3 line-clamp-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                    {{ $category->description }}
                                </span>
                            @endif

                            <span class="relative mt-auto pt-4 text-xs font-bold text-brand-600 transition group-hover:translate-x-0.5 dark:text-brand-400">
                                Read {{ $category->name }} &rarr;
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-newsletter.form class="mt-14 max-w-xl" />
    </div>
@endsection
