<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'status' => $request->string('status')->toString(),
        ];

        $messages = ContactMessage::query()
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';

                $query->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('message', 'like', $like));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.messages.index', [
            'messages' => $messages,
            'filters' => $filters,
            'statuses' => ContactMessageStatus::cases(),
            'counts' => ContactMessage::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        // Opening a message is what marks it read. A separate button for it is
        // one more thing to forget, and the inbox count then lies.
        if ($message->status === ContactMessageStatus::New) {
            $message->update([
                'status' => ContactMessageStatus::Read,
                'read_at' => now(),
            ]);
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function updateStatus(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(ContactMessageStatus::cases(), 'value'))],
        ]);

        $status = ContactMessageStatus::from($validated['status']);

        $message->update([
            'status' => $status,
            'read_at' => $message->read_at ?? now(),
        ]);

        $this->logger->log('message.status', $message, "Marked message from {$message->email} as {$status->label()}");

        return back()->with('success', "Marked as {$status->label()}.");
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $email = $message->email;
        $message->delete();

        $this->logger->log('message.deleted', $message, "Deleted the message from {$email}");

        return redirect()
            ->route('admin.messages.index')
            ->with('success', 'Message deleted.');
    }

    /**
     * Bulk actions on the current selection. Spam arrives in batches, so
     * clearing it one row at a time is the wrong shape of work.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:read,replied,spam,delete'],
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $query = ContactMessage::whereIn('id', $validated['ids']);
        $count = (clone $query)->count();

        if ($validated['action'] === 'delete') {
            $query->get()->each->delete();
        } else {
            $query->update([
                'status' => ContactMessageStatus::from($validated['action'])->value,
                'read_at' => now(),
            ]);

            // A mass update bypasses model events, so the badge cache that
            // booted() normally clears has to be dropped by hand here.
            Cache::forget('admin.unread-messages');
        }

        $this->logger->log('message.bulk', null, "{$validated['action']} applied to {$count} messages");

        return back()->with('success', "{$count} ".str('message')->plural($count).' updated.');
    }
}
