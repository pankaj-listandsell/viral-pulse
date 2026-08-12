@extends('layouts.admin')

@section('title', 'SEO Settings')
@section('heading', 'SEO Settings')
@section('subheading', 'Defaults for any page that does not set its own metadata.')

@section('content')
    <div class="grid gap-5 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.seo.update') }}" enctype="multipart/form-data" class="lg:col-span-2">
            @csrf

            <x-ui.card :title="$group['label']" :subtitle="$group['description']">
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($group['fields'] as $field)
                        <x-admin.setting-field :field="$field" :value="$values[$field['key']] ?? null" />
                    @endforeach
                </div>

                <div class="mt-5 flex justify-end border-t border-gray-100 pt-4 dark:border-gray-800">
                    <x-ui.button type="submit">Save SEO settings</x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <div class="space-y-4">
            <x-ui.card title="Live endpoints" subtitle="Open these to check what a crawler actually receives.">
                <ul class="space-y-2 text-sm">
                    @foreach($endpoints as $label => $url)
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 font-medium text-brand-600 hover:underline dark:text-brand-400">
                                open <x-icon name="external-link" class="size-3.5" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>

            <x-ui.card title="What is handled for you">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>Canonical tag on every page.</li>
                    <li>Pages 2+ of an archive and all search results are <code>noindex, follow</code>.</li>
                    <li>Renaming a post 301s the old URL instead of breaking it.</li>
                    <li>Trailing slashes and the www variant fold into one URL.</li>
                    <li>Article, breadcrumb, organisation and item-list structured data.</li>
                    <li>The sitemap and feeds rebuild whenever a post is published.</li>
                </ul>
            </x-ui.card>

            @if(! app()->isProduction())
                <x-ui.card>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        This is not a production environment, so <code>robots.txt</code> currently blocks every crawler
                        regardless of the settings above. That is deliberate: a staging copy indexed next to the real
                        site is painful to undo.
                    </p>
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection
