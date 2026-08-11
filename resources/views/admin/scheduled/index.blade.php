@extends('layouts.admin')

@php
    use App\Enums\ScheduledPostStatus;

    $badge = fn (ScheduledPostStatus $status) => match ($status) {
        ScheduledPostStatus::Published => 'green',
        ScheduledPostStatus::Failed => 'red',
        ScheduledPostStatus::Processing => 'blue',
        ScheduledPostStatus::Cancelled => 'gray',
        default => 'amber',
    };
@endphp

@section('title', 'Scheduled Posts')
@section('heading', 'Scheduled Posts')
@section('subheading', $nextDue
    ? 'Next one goes live ' . \Illuminate\Support\Carbon::parse($nextDue)->diffForHumans()
    : 'Nothing waiting to be published')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Waiting" :value="(int) ($counts[ScheduledPostStatus::Pending->value] ?? 0)" icon="clock" color="amber" />
        <x-ui.stat-card label="Published" :value="(int) ($counts[ScheduledPostStatus::Published->value] ?? 0)" icon="check" color="green" />
        <x-ui.stat-card label="Failed" :value="(int) ($counts[ScheduledPostStatus::Failed->value] ?? 0)" icon="triangle-alert" color="gray" />
        <x-ui.stat-card label="Cancelled" :value="(int) ($counts[ScheduledPostStatus::Cancelled->value] ?? 0)" icon="x" color="gray" />
    </div>

    <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        Publishing is driven by <code>php artisan schedule:run</code> every minute. If nothing here ever goes live,
        the cron entry (or the Windows scheduled task) is not running.
    </div>

    <x-ui.card :padded="false">
        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            <form method="GET" class="flex items-center gap-2">
                <x-ui.select name="status" class="w-44" onchange="this.form.submit()">
                    <option value="">Any status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.button type="submit" variant="secondary" size="sm">Filter</x-ui.button>
            </form>
        </div>

        @if($rows->isEmpty())
            <x-ui.empty-state
                icon="calendar-clock"
                title="Nothing scheduled"
                description="Schedule a post from the editor, or let the trending pipeline queue one."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase dark:border-gray-800 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Post</th>
                            <th scope="col" class="px-5 py-3 font-medium">Goes live</th>
                            <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($rows as $row)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="max-w-md px-5 py-3">
                                    @if($row->post)
                                        <a href="{{ route('admin.posts.edit', $row->post) }}" class="truncate font-medium hover:underline">
                                            {{ $row->post->title }}
                                        </a>
                                        @if($row->post->category)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row->post->category->name }}</p>
                                        @endif
                                    @else
                                        <span class="text-gray-400">The post was deleted</span>
                                    @endif

                                    @if($row->last_error)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $row->last_error }}</p>
                                    @endif
                                </td>

                                <td class="px-5 py-3">
                                    <p class="font-medium tabular-nums">{{ $row->scheduled_at->format('d M, H:i') }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row->scheduled_at->diffForHumans() }}</p>
                                </td>

                                <td class="hidden px-5 py-3 sm:table-cell">
                                    <x-ui.badge :color="$badge($row->status)">{{ $row->status->label() }}</x-ui.badge>
                                    @if($row->attempts > 1)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row->attempts }} attempts</p>
                                    @endif
                                </td>

                                <td class="px-5 py-3 text-right">
                                    @if($row->status === ScheduledPostStatus::Pending && $row->post)
                                        <div class="flex items-center justify-end gap-1.5">
                                            <form method="POST" action="{{ route('admin.scheduled.publish', $row) }}">
                                                @csrf
                                                <x-ui.button type="submit" size="sm">Publish now</x-ui.button>
                                            </form>

                                            <x-ui.confirm-form
                                                :action="route('admin.scheduled.cancel', $row)"
                                                method="POST"
                                                title="Cancel this schedule?"
                                                message="The post goes back to drafts. Nothing is deleted."
                                                confirm-label="Cancel schedule"
                                                variant="secondary"
                                            >
                                                <x-slot:trigger>
                                                    <button type="submit"
                                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                                            aria-label="Cancel schedule">
                                                        <x-icon name="x" class="size-4" />
                                                    </button>
                                                </x-slot:trigger>
                                            </x-ui.confirm-form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="border-t border-gray-100 p-4 dark:border-gray-800">{{ $rows->links() }}</div>
            @endif
        @endif
    </x-ui.card>
@endsection
