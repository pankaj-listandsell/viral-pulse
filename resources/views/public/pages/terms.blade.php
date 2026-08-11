@extends('layouts.public')

@php
    $siteName = app(\App\Services\SettingsService::class)->get('site_name') ?: config('app.name');
@endphp

@section('content')
    <x-legal-page title="Terms of Service">
        <p>
            By using {{ $siteName }} you agree to these terms. If you do not agree with them, please do not use
            the site.
        </p>

        <h2>Using the site</h2>
        <p>
            You may read, link to and share our articles freely. You may not scrape the site at a rate that degrades it
            for other readers, republish our articles in full elsewhere, or present our work as your own.
        </p>

        <h2>Our content</h2>
        <p>
            Unless stated otherwise, the articles, images and design on this site belong to {{ $siteName }}.
            Short quotations with a link back are welcome and need no permission. Wholesale reproduction does.
        </p>

        <h2>Content you submit</h2>
        <p>
            If you send us a message, subscribe to the newsletter or leave a comment, you confirm the content is yours
            to send and is not unlawful, abusive or misleading. We may remove anything that is, and we may decline to
            publish submissions without giving a reason.
        </p>

        <h2>Links to other sites</h2>
        <p>
            Our articles link to external sources. We do not control those sites and are not responsible for their
            content, accuracy or privacy practices.
        </p>

        <h2>Accuracy and availability</h2>
        <p>
            We work to keep articles accurate and up to date, but we do not guarantee that everything is free of
            errors, nor that the site will always be available. We may change, suspend or remove any part of the site
            at any time.
        </p>

        <h2>Liability</h2>
        <p>
            The site is provided as-is. To the extent the law allows, {{ $siteName }} is not liable for any loss
            arising from your use of it or reliance on its content.
        </p>

        <h2>Changes to these terms</h2>
        <p>
            We may update these terms. The date at the top shows when they last changed, and continuing to use the
            site after a change means you accept the updated terms.
        </p>

        <h2>Contact</h2>
        <p>
            Questions about these terms can go through the <a href="{{ route('contact') }}">contact page</a>.
        </p>
    </x-legal-page>
@endsection
