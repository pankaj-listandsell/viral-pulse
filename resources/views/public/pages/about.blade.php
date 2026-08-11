@extends('layouts.public')

@php
    $settings = app(\App\Services\SettingsService::class);
    $siteName = $settings->get('site_name') ?: config('app.name');
@endphp

@section('content')
    <x-legal-page title="About" :updated="false">
        <p>
            {{ $siteName }} covers trending stories, explainers and the things people are actually talking
            about — written to be read in a few minutes and to leave you knowing something you did not know before.
        </p>

        <h2>What we publish</h2>
        <p>
            News-style articles, explainers, listicles, how-to guides and light entertainment across technology,
            travel, education, culture and current events. Every article is reviewed by a person before it goes live.
        </p>

        <h2>How we use AI</h2>
        <p>
            Some of our articles are drafted with the help of AI and then checked, edited and approved by a human
            editor before publishing. Where that is the case, the article carries a visible note saying so. We do not
            publish anything automatically without review, and a person is accountable for everything on this site.
        </p>

        <h2>Corrections</h2>
        <p>
            If you spot something wrong, tell us and we will fix it. Accuracy matters more to us than being first.
            Use the <a href="{{ route('contact') }}">contact page</a> and we will look into it.
        </p>

        <h2>Get in touch</h2>
        <p>
            Questions, story tips, corrections or business enquiries are all welcome through the
            <a href="{{ route('contact') }}">contact page</a>.
        </p>
    </x-legal-page>
@endsection
