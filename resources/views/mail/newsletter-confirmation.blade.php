<x-mail::message>
# One more step

Someone — hopefully you — asked to receive new stories from
{{ app(\App\Services\SettingsService::class)->get('site_name') ?: config('app.name') }}.

Confirm the address to start receiving them.

<x-mail::button :url="$confirmUrl">
Confirm subscription
</x-mail::button>

If this was not you, ignore this email. Nothing will be sent without your
confirmation, and the request will expire on its own.

Thanks,<br>
{{ app(\App\Services\SettingsService::class)->get('site_name') ?: config('app.name') }}

<x-slot:subcopy>
If the button does not work, copy this link into your browser:
[{{ $confirmUrl }}]({{ $confirmUrl }})
</x-slot:subcopy>
</x-mail::message>
