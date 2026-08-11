@extends('layouts.admin')

@php
    $quality = $payload['quality'] ?? ['score' => null, 'issues' => [], 'publishable' => false];
@endphp

@section('title', 'Review generated article')
@section('heading', 'Review before publishing')
@section('subheading', $generation->topic)

@section('actions')
    <x-ui.button variant="secondary" :href="route('admin.ai.index')">
        <x-icon name="arrow-left" class="size-4" />
        Back
    </x-ui.button>
@endsection

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @if($quality['issues'])
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-200">
                        <x-icon name="triangle-alert" class="size-4" />
                        {{ count($quality['issues']) }} {{ Str::plural('issue', $quality['issues']) }} found
                    </h2>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-900 dark:text-amber-200">
                        @foreach($quality['issues'] as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-ui.card :title="$payload['title'] ?? 'Untitled'" :subtitle="$payload['excerpt'] ?? null">
                {{-- Sanitised at generation time; safe to render. --}}
                <div class="prose prose-sm max-w-none dark:prose-invert">
                    {!! $payload['content'] ?? '' !!}
                </div>
            </x-ui.card>

            <x-ui.card title="Search engine listing">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="truncate text-base text-blue-700 dark:text-blue-400">{{ $payload['seo_title'] ?? '' }}</p>
                    <p class="mt-0.5 line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{{ $payload['seo_description'] ?? '' }}</p>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Keywords: {{ $payload['seo_keywords'] ?? '—' }}
                </p>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card title="Publish">
                @if($generation->post_id)
                    <p class="text-sm text-gray-600 dark:text-gray-400">A post already exists for this generation.</p>
                    <x-ui.button class="mt-3 w-full" :href="route('admin.posts.edit', $generation->post_id)">Open the post</x-ui.button>
                @else
                    <form method="POST" action="{{ route('admin.ai.approve', $generation) }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-ui.label for="category_id" required>Category</x-ui.label>
                            <x-ui.select id="category_id" name="category_id">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="category_id" />
                        </div>

                        <x-ui.button type="submit" class="w-full">Create draft</x-ui.button>

                        <x-ui.button type="submit" name="publish" value="1" variant="secondary" class="w-full">
                            Create and publish now
                        </x-ui.button>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            You are responsible for what goes live. Read it through — the article carries an AI
                            disclosure badge on the public page.
                        </p>
                    </form>
                @endif
            </x-ui.card>

            <x-ui.card title="Generation details">
                <dl class="space-y-2 text-sm">
                    @foreach([
                        'Quality score' => $quality['score'] ?? '—',
                        'Provider' => $generation->provider,
                        'Model' => $generation->model,
                        'Tokens' => number_format($generation->totalTokens()),
                        'Estimated cost' => $generation->cost ? '$'.number_format((float) $generation->cost, 4) : '—',
                        'Took' => $generation->duration_ms ? round($generation->duration_ms / 1000, 1).'s' : '—',
                        'Tags' => implode(', ', $payload['tags'] ?? []) ?: '—',
                    ] as $label => $value)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-right font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>

            @if($payload['image_prompt'] ?? null)
                <x-ui.card title="Suggested image prompt">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payload['image_prompt'] }}</p>
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection
