@extends('layouts.public')

@php
    $settings = app(\App\Services\SettingsService::class);
    $siteName = $settings->get('site_name') ?: config('app.name');
@endphp

@section('content')
    <x-legal-page title="Disclaimer">
        <h2>General information only</h2>
        <p>
            Everything published on {{ $siteName }} is for general information and interest. It is not professional
            advice. Do not treat an article here as a substitute for guidance from a doctor, lawyer, financial adviser
            or any other qualified professional who knows your situation.
        </p>

        <h2>AI-assisted content</h2>
        <p>
            Some articles on this site are drafted with AI assistance and then reviewed and edited by a person before
            publishing. Articles produced this way carry a visible note. We check them, but AI can still get details
            wrong — if something matters to you, verify it against a primary source before acting on it.
        </p>

        <h2>Accuracy</h2>
        <p>
            We try to be accurate and to correct mistakes quickly, but we make no guarantee that every article is
            complete, current or error-free. Trending topics in particular change fast, and an article reflects what
            was known when it was written.
        </p>

        <h2>External links</h2>
        <p>
            We link to other websites where they are useful. Those links are not endorsements, and we are not
            responsible for what those sites publish or how they handle your data.
        </p>

        <h2>Advertising</h2>
        <p>
            This site may display advertising. Ads are served by third parties and their presence is not an endorsement
            of the advertised product or service. Editorial decisions are made independently of advertising.
        </p>

        <h2>Report a problem</h2>
        <p>
            If an article is wrong, out of date or misleading, tell us through the
            <a href="{{ route('contact') }}">contact page</a> and we will review it.
        </p>
    </x-legal-page>
@endsection
