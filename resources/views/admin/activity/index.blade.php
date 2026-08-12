@extends('layouts.admin')

@section('title', 'Activity Log')
@section('heading', 'Activity Log')
@section('subheading', 'Every change made through the admin. Entries older than ' . $retentionDays . ' days are pruned by the nightly cleanup.')

@section('content')
    <x-ui.card :padded="false">
        <form method="GET" class="flex flex-wrap items-end gap-2 border-b border-gray-100 p-4 dark:border-gray-800">
            <div>
                <x-ui.label for="filter-q">Description</x-ui.label>
                <x-ui.input id="filter-q" name="q" value="{{ $filters['q'] }}" placeholder="Search" class="w-48" />
            </div>

            <div>
                <x-ui.label for="filter-action">Action</x-ui.label>
                <x-ui.select id="filter-action" name="action" class="w-44">
                    <option value="">Any action</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div>
                <x-ui.label for="filter-user">User</x-ui.label>
                <x-ui.select id="filter-user" name="user" class="w-40">
                    <option value="">Anyone</option>
                    @foreach($users as $id => $name)
                        <option value="{{ $id }}" @selected($filters['user'] === $id)>{{ $name }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div>
                <x-ui.label for="filter-from">From</x-ui.label>
                <x-ui.input id="filter-from" type="date" name="from" value="{{ $filters['from'] }}" class="w-40" />
            </div>

            <div>
                <x-ui.label for="filter-to">To</x-ui.label>
                <x-ui.input id="filter-to" type="date" name="to" value="{{ $filters['to'] }}" class="w-40" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if(array_filter($filters))
                <a href="{{ route('admin.activity.index') }}" class="px-2 py-2 text-sm text-gray-500 hover:underline dark:text-gray-400">Clear</a>
            @endif
        </form>

        @if($logs->isEmpty())
            <x-ui.empty-state
                icon="scroll-text"
                title="Nothing recorded"
                description="Actions taken in the admin show up here as they happen."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase dark:border-gray-800 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">When</th>
                            <th scope="col" class="px-5 py-3 font-medium">Who</th>
                            <th scope="col" class="px-5 py-3 font-medium">Action</th>
                            <th scope="col" class="px-5 py-3 font-medium">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($logs as $log)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    <span title="{{ $log->created_at?->toDayDateTimeString() }}">{{ $log->created_at?->diffForHumans() }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    {{-- Scheduled commands run with no signed-in user. --}}
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td class="px-5 py-3">
                                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">{{ $log->action }}</code>
                                </td>
                                <td class="max-w-lg px-5 py-3 text-gray-600 dark:text-gray-400">
                                    <span class="block truncate">{{ $log->description }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($logs->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-800">{{ $logs->links() }}</div>
        @endif
    </x-ui.card>
@endsection
