@extends('layouts.admin')

@section('title', 'Storage link')
@section('heading', 'Storage link')
@section('subheading', 'Uploads are written to storage/app/public and served through public/storage. If that link is missing or is a real folder, every new picture 404s while the old ones keep working.')

@section('content')
    @php
        $ok = $report['points_correctly'] && $report['target_writable'];
    @endphp

    <div class="space-y-4">
        @if($result)
            <p @class([
                'rounded-xl border px-4 py-3 text-sm',
                'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300' => $result['ok'],
                'border-red-300 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300' => ! $result['ok'],
            ])>
                <strong>{{ $result['ok'] ? 'Repaired.' : 'Could not repair.' }}</strong> {{ $result['message'] }}
                @if($result['moved'])
                    <br>The old folder was kept as <code>public/{{ $result['moved'] }}</code>. Delete it once you have
                    checked that nothing is missing.
                @endif
            </p>
        @endif

        @if(session('success'))
            <p class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
                @if(session('moved'))
                    <br>The old folder was kept as <code>public/{{ session('moved') }}</code>. Delete it once you have
                    checked that nothing is missing.
                @endif
            </p>
        @endif

        @if(session('error'))
            <p class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ session('error') }}
            </p>
        @endif

        <x-ui.card title="Diagnosis">
            <dl class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                @foreach([
                    ['public/storage exists', $report['exists'], null],
                    ['is a symlink (not a real folder)', $report['is_symlink'], $report['is_symlink'] ? null : 'An FTP copy turns the link into a folder holding a stale copy. This is the usual cause.'],
                    ['points at storage/app/public', $report['points_correctly'], $report['points_to'] ? 'Currently points at: '.$report['points_to'] : null],
                    ['target folder exists', $report['target_exists'], $report['target_path']],
                    ['target folder is writable', $report['target_writable'], null],
                ] as [$label, $pass, $note])
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <div>
                            <dt class="font-medium text-gray-900 dark:text-gray-100">{{ $label }}</dt>
                            @if($note)
                                <dd class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $note }}</dd>
                            @endif
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $pass,
                            'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' => ! $pass,
                        ])>{{ $pass ? 'OK' : 'NO' }}</span>
                    </div>
                @endforeach

                <div class="flex items-start justify-between gap-4 py-2.5">
                    <dt class="font-medium text-gray-900 dark:text-gray-100">disk in use</dt>
                    <dd class="text-right text-xs text-gray-500 dark:text-gray-400">
                        {{ $report['disk'] }}<br>{{ $report['disk_root'] }}
                    </dd>
                </div>

                @if($report['free_space'])
                    <div class="flex items-center justify-between gap-4 py-2.5">
                        <dt class="font-medium text-gray-900 dark:text-gray-100">free disk space</dt>
                        <dd class="text-xs text-gray-500 dark:text-gray-400">{{ round($report['free_space'] / 1073741824, 2) }} GB</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        @if($report['probe_url'])
            <x-ui.card title="End-to-end check"
                       subtitle="A file was just written through the same disk the uploader uses. Open it: if it downloads, the whole path works. If it 404s, the link is the problem.">
                <a href="{{ $report['probe_url'] }}" target="_blank" rel="noopener"
                   class="text-sm font-medium text-brand-600 underline underline-offset-2 dark:text-brand-400">
                    {{ $report['probe_url'] }}
                </a>
            </x-ui.card>
        @endif

        <x-ui.card title="Repair"
                   subtitle="Moves an existing public/storage folder aside (nothing is deleted) and creates the symlink. Same as running php artisan storage:link.">
            @if($ok)
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    The link is already correct. There is nothing to repair — if pictures are still missing, the cause is
                    elsewhere and the log will say what it is.
                </p>
            @endif

            <form method="POST" action="{{ route('admin.maintenance.storage-link.repair') }}" class="mt-4">
                @csrf
                <x-ui.button type="submit">
                    {{ $ok ? 'Recreate the link anyway' : 'Create the link' }}
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
