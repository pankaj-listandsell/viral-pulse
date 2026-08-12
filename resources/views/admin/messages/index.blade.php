@extends('layouts.admin')

@php
    use App\Enums\ContactMessageStatus;

    $badge = fn (ContactMessageStatus $status) => match ($status) {
        ContactMessageStatus::New => 'amber',
        ContactMessageStatus::Read => 'blue',
        ContactMessageStatus::Replied => 'green',
        ContactMessageStatus::Spam => 'gray',
    };
@endphp

@section('title', 'Contact Messages')
@section('heading', 'Contact Messages')
@section('subheading', ($counts[ContactMessageStatus::New->value] ?? 0) . ' unread of ' . $messages->total() . ' total')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($statuses as $status)
            <x-ui.stat-card
                :label="$status->label()"
                :value="(int) ($counts[$status->value] ?? 0)"
                :icon="match($status) {
                    ContactMessageStatus::New => 'inbox',
                    ContactMessageStatus::Read => 'mail-open',
                    ContactMessageStatus::Replied => 'reply',
                    ContactMessageStatus::Spam => 'shield-alert',
                }"
            />
        @endforeach
    </div>

    <x-ui.card :padded="false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                    <x-ui.input name="q" value="{{ $filters['q'] }}" placeholder="Search messages" class="w-56 pl-9" />
                </div>

                <x-ui.select name="status" class="w-36" onchange="this.form.submit()">
                    <option value="">Any status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.button type="submit" variant="secondary" size="sm">Filter</x-ui.button>
            </form>
        </div>

        @if($messages->isEmpty())
            <x-ui.empty-state
                icon="inbox"
                title="Nothing here"
                description="Messages sent through the contact form land in this inbox."
            />
        @else
            {{-- Spam arrives in batches, so the bulk bar is part of the table
                 rather than something to reach for one row at a time. --}}
            <form method="POST" action="{{ route('admin.messages.bulk') }}" x-data="{ selected: [] }">
                @csrf

                <div
                    class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50 px-5 py-2.5 text-sm dark:border-gray-800 dark:bg-gray-800/50"
                    x-show="selected.length"
                    x-cloak
                >
                    <span class="text-gray-600 dark:text-gray-400" x-text="selected.length + ' selected'"></span>

                    <div class="ml-auto flex flex-wrap gap-2">
                        <x-ui.button type="submit" name="action" value="read" variant="secondary" size="sm">Mark read</x-ui.button>
                        <x-ui.button type="submit" name="action" value="replied" variant="secondary" size="sm">Mark replied</x-ui.button>
                        <x-ui.button type="submit" name="action" value="spam" variant="secondary" size="sm">Spam</x-ui.button>
                        <x-ui.button
                            type="submit" name="action" value="delete" variant="danger" size="sm"
                            onclick="return confirm('Delete the selected messages?')"
                        >Delete</x-ui.button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase dark:border-gray-800 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="w-10 px-5 py-3"><span class="sr-only">Select</span></th>
                                <th scope="col" class="px-5 py-3 font-medium">From</th>
                                <th scope="col" class="px-5 py-3 font-medium">Subject</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Status</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($messages as $message)
                                <tr @class([
                                    'transition hover:bg-gray-50 dark:hover:bg-gray-800/50',
                                    'font-medium' => $message->status === ContactMessageStatus::New,
                                ])>
                                    <td class="px-5 py-3">
                                        <input
                                            type="checkbox" name="ids[]" value="{{ $message->id }}" x-model="selected"
                                            aria-label="Select the message from {{ $message->name }}"
                                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900"
                                        >
                                    </td>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.messages.show', $message) }}" class="block min-w-0">
                                            <span class="block truncate">{{ $message->name }}</span>
                                            <span class="block truncate text-xs font-normal text-gray-500 dark:text-gray-400">{{ $message->email }}</span>
                                        </a>
                                    </td>
                                    <td class="max-w-sm px-5 py-3">
                                        <a href="{{ route('admin.messages.show', $message) }}" class="block truncate hover:text-brand-600 dark:hover:text-brand-400">
                                            {{ $message->subject }}
                                        </a>
                                    </td>
                                    <td class="hidden px-5 py-3 sm:table-cell">
                                        <x-ui.badge :color="$badge($message->status)">{{ $message->status->label() }}</x-ui.badge>
                                    </td>
                                    <td class="hidden px-5 py-3 text-gray-500 md:table-cell dark:text-gray-400">
                                        {{ $message->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        @endif

        @if($messages->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-800">{{ $messages->links() }}</div>
        @endif
    </x-ui.card>
@endsection
