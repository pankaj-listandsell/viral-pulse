<x-mail::message>
# New message from the contact form

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})
**Subject:** {{ $contactMessage->subject }}
**Received:** {{ $contactMessage->created_at->toDayDateTimeString() }}

---

{{ $contactMessage->message }}

---

<x-mail::button :url="$inboxUrl">
Open in the inbox
</x-mail::button>

Replying to this email goes straight back to the sender.

{{ app(\App\Services\SettingsService::class)->get('site_name') ?: config('app.name') }}
</x-mail::message>
