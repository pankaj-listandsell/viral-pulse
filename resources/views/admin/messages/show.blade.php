@extends('layouts.admin')

@php
    use App\Enums\ContactMessageStatus;
@endphp

@section('title', 'Message from ' . $message->name)
@section('heading', $message->subject)
@section('subheading', 'From ' . $message->name . ' · ' . $message->created_at->toDayDateTimeString())

@section('content')
    <div class="grid gap-5 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            {{-- Escaped, never rendered as HTML: this is text a stranger typed
                 into a public form. --}}
            <p class="text-sm leading-relaxed whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $message->message }}</p>
        </x-ui.card>

        <div class="space-y-4">
            <x-ui.card title="Sender">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="font-medium">{{ $message->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                        <dd>
                            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . $message->subject) }}"
                               class="font-medium text-brand-600 hover:underline dark:text-brand-400">{{ $message->email }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1"><x-ui.badge>{{ $message->status->label() }}</x-ui.badge></dd>
                    </div>
                </dl>

                <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    The sender's IP is stored only as a salted hash, so it can be matched against other submissions
                    but never read back.
                </p>
            </x-ui.card>

            <x-ui.card title="Actions">
                <div class="flex flex-wrap gap-2">
                    @foreach([ContactMessageStatus::Replied, ContactMessageStatus::Read, ContactMessageStatus::Spam] as $status)
                        @if($message->status !== $status)
                            <form method="POST" action="{{ route('admin.messages.status', $message) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $status->value }}">
                                <x-ui.button type="submit" variant="secondary" size="sm">Mark {{ Str::lower($status->label()) }}</x-ui.button>
                            </form>
                        @endif
                    @endforeach

                    <x-ui.confirm-form
                        :action="route('admin.messages.destroy', $message)"
                        title="Delete this message?"
                        :message="'The message from ' . $message->name . ' will be removed.'"
                    >
                        <x-slot:trigger>
                            <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                        </x-slot:trigger>
                    </x-ui.confirm-form>
                </div>

                <a href="{{ route('admin.messages.index') }}"
                   class="mt-4 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-brand-600 dark:text-gray-400">
                    <x-icon name="arrow-left" class="size-4" />
                    Back to the inbox
                </a>
            </x-ui.card>
        </div>
    </div>
@endsection
