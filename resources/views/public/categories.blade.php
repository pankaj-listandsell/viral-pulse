@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        <header class="mb-8 border-b border-gray-200 pb-6 dark:border-gray-800">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Categories</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Every topic we cover.</p>
        </header>

        @if($categories->isEmpty())
            <p class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No categories yet.
            </p>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    <li>
                        <a href="{{ route('categories.show', $category) }}"
                           class="block h-full rounded-xl border border-gray-200 p-5 transition hover:border-brand-400 hover:shadow-sm dark:border-gray-800">
                            <span class="flex items-center gap-2.5">
                                <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $category->color ?? '#94a3b8' }}"></span>
                                <span class="font-semibold tracking-tight">{{ $category->name }}</span>
                            </span>

                            @if($category->description)
                                <span class="mt-2 block line-clamp-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $category->description }}
                                </span>
                            @endif

                            <span class="mt-3 block text-xs text-gray-500 dark:text-gray-400">
                                {{ $category->posts_count }} {{ Str::plural('story', $category->posts_count) }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
