@extends('layouts.public')

@php
    $settings = app(\App\Services\SettingsService::class);
    $siteName = $settings->get('site_name') ?: config('app.name');
    $contactEmail = $settings->get('contact_email');
    $adsense = $settings->bool('adsense_enabled');
    $analytics = $settings->get('google_analytics_id');
@endphp

@section('content')
    <x-legal-page title="Privacy Policy">
        <p>
            This policy explains what {{ $siteName }} collects when you visit, why, and what you can do about it.
            We have tried to write it in plain language rather than legal filler.
        </p>

        <h2>What we collect</h2>
        <ul>
            <li>
                <strong>Reading activity.</strong> When you open an article we record that it was read, along with the
                date, a rough device type and the page that referred you. Your IP address is <em>not</em> stored — it is
                converted into an irreversible hash that lets us avoid counting the same reader twice, and nothing more.
            </li>
            <li>
                <strong>Newsletter.</strong> If you subscribe we store your email address, and your name if you give it.
                We use double opt-in, so nothing is sent until you confirm from your own inbox.
            </li>
            <li>
                <strong>Messages.</strong> If you contact us we keep your name, email and message so we can reply.
            </li>
        </ul>

        <h2>What we do not do</h2>
        <ul>
            <li>We do not sell your personal information.</li>
            <li>We do not store raw IP addresses.</li>
            <li>We do not require an account to read anything on this site.</li>
        </ul>

        <h2>Cookies</h2>
        <p>
            We use a session cookie to keep the site working and to remember whether you prefer light or dark mode.
            @if($analytics || $adsense)
                Third-party services described below may set their own cookies.
            @endif
            You can clear or block cookies in your browser; the site will still work, it will simply forget your
            preferences.
        </p>

        @if($analytics)
            <h2>Analytics</h2>
            <p>
                We use Google Analytics to understand which articles people find useful. Google processes this data on
                its own terms and may set cookies. You can opt out using
                <a href="https://tools.google.com/dlpage/gaoptout" rel="nofollow noopener" target="_blank">Google's
                opt-out add-on</a>.
            </p>
        @endif

        @if($adsense)
            <h2>Advertising</h2>
            <p>
                We show ads served by Google AdSense. Google and its partners may use cookies to serve ads based on your
                previous visits to this and other websites. You can control personalised advertising in your
                <a href="https://myadcenter.google.com/" rel="nofollow noopener" target="_blank">Google Ad Settings</a>,
                or opt out of third-party vendor cookies at
                <a href="https://www.aboutads.info/choices/" rel="nofollow noopener" target="_blank">aboutads.info</a>.
            </p>
        @endif

        <h2>Your choices</h2>
        <p>
            You can unsubscribe from the newsletter at any time using the link in every email — one click, no questions.
            You can also ask us to delete the data we hold about you
            @if($contactEmail)
                by emailing <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
            @else
                through the <a href="{{ route('contact') }}">contact page</a>.
            @endif
        </p>

        <h2>Children</h2>
        <p>
            This site is not directed at children under 13, and we do not knowingly collect information from them.
        </p>

        <h2>Changes</h2>
        <p>
            If this policy changes we will update this page and the date at the top. Material changes will be
            noted clearly rather than slipped in quietly.
        </p>
    </x-legal-page>
@endsection
