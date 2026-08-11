@props(['slot' => null, 'format' => 'auto', 'label' => true])

@php
    $settings = app(\App\Services\SettingsService::class);
    $enabled = $settings->bool('adsense_enabled');
    $client = $settings->get('adsense_client_id');
    $slotId = $slot ? $settings->get("adsense_slot_{$slot}") : null;
@endphp

{{--
    Renders nothing at all unless AdSense is switched on and this specific slot
    has a real id. No placeholder boxes, no reserved empty space, and nothing
    that could be mistaken for an ad - which is what the AdSense policies
    require and what keeps the page fast before approval comes through.
--}}
@if($enabled && $client && $slotId)
    <aside {{ $attributes->merge(['class' => 'my-6']) }}>
        @if($label)
            <p class="mb-1 text-center text-[0.65rem] tracking-wider text-gray-400 uppercase">Advertisement</p>
        @endif

        <ins class="adsbygoogle block"
             style="display:block"
             data-ad-client="{{ $client }}"
             data-ad-slot="{{ $slotId }}"
             data-ad-format="{{ $format }}"
             data-full-width-responsive="true"></ins>

        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </aside>
@endif
