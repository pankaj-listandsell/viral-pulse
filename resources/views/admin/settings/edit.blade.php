@extends('layouts.admin')

@section('title', 'Site Settings')
@section('heading', 'Site Settings')
@section('subheading', 'Everything the site reads at runtime. Saving clears the caches that depend on it.')

@section('content')
    <div class="grid gap-5 lg:grid-cols-[13rem_1fr]">
        <nav class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible" aria-label="Settings sections">
            @foreach($groups as $key => $group)
                <a
                    href="{{ route('admin.settings.edit', ['tab' => $key]) }}"
                    @class([
                        'shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $active === $key,
                        'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' => $active !== $key,
                    ])
                    @if($active === $key) aria-current="page" @endif
                >{{ $group['label'] }}</a>
            @endforeach
        </nav>

        <div class="space-y-4">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="group" value="{{ $active }}">

                <x-ui.card :title="$groups[$active]['label']" :subtitle="$groups[$active]['description']">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($groups[$active]['fields'] as $field)
                            <x-admin.setting-field :field="$field" :value="$values[$field['key']] ?? null" />
                        @endforeach
                    </div>

                    @if($active === 'ai')
                        <p class="mt-4 rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-800/60 dark:text-gray-400">
                            The provider and model are chosen on the
                            <a href="{{ route('admin.ai.index') }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">AI Generator</a>
                            page, next to where they are used. API keys are read from <code>.env</code> and are never stored in the
                            database — a key here would end up in every backup and on this screen.
                        </p>
                    @endif

                    @if($active === 'adsense')
                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                            Ads only render once this is enabled <em>and</em> a publisher id is set. Approval depends on the site
                            having genuine content — a wall of unreviewed auto-generated articles is exactly what Google’s
                            scaled-content-abuse policy exists to catch.
                        </p>
                    @endif

                    <div class="mt-5 flex justify-end border-t border-gray-100 pt-4 dark:border-gray-800">
                        <x-ui.button type="submit">Save {{ Str::lower($groups[$active]['label']) }} settings</x-ui.button>
                    </div>
                </x-ui.card>
            </form>

            <x-ui.card title="Caches" subtitle="Saving already clears these. This is here for when something looks stale anyway.">
                <form method="POST" action="{{ route('admin.settings.flush') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="sm">
                        <x-icon name="refresh-cw" class="size-4" />
                        Clear site caches
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
@endsection
