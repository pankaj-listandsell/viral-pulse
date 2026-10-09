<?php

use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\CloudflareProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Default provider
    |--------------------------------------------------------------------------
    |
    | The admin can switch between any provider that has a key configured; this
    | is the fallback when no choice has been saved yet.
    |
    */

    'provider' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | API keys are read from the environment and never stored in the database.
    | A settings row holding a live key would end up in every backup, every DB
    | dump and on the settings screen itself; the environment keeps it in one
    | place that is already excluded from version control.
    |
    | A provider without a key is simply not offered in the admin.
    |
    */

    'providers' => [

        'gemini' => [
            'label' => 'Gemini (Google)',
            'driver' => GeminiProvider::class,
            'key' => env('GEMINI_API_KEY'),
            // Google retires model ids. gemini-2.5-flash and gemini-2.5-pro now
            // return 404 "no longer available to new users", which surfaced as
            // every generation failing at once. Every id below was confirmed
            // working with a live call before being listed here.
            // Flash Lite by default: the free tier allowed Flash only 20
            // requests a day, which one morning's articles used up and left
            // the site publishing nothing for days.
            'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
            'models' => [
                'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite — cheapest, highest free limit (default)',
                'gemini-3.6-flash' => 'Gemini 3.6 Flash — newest, best quality',
                'gemini-3.5-flash' => 'Gemini 3.5 Flash',
                // An alias that always points at the current Flash. Convenient,
                // but it moves without warning and the price table below cannot
                // follow it, so it is not the default.
                'gemini-flash-latest' => 'Gemini Flash Latest — always current',
            ],
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
        ],

        // Listed after Gemini on purpose: when the selected provider fails,
        // the next configured one in this list writes the article instead.
        'cloudflare' => [
            'label' => 'Cloudflare Workers AI',
            'driver' => CloudflareProvider::class,
            'key' => env('CLOUDFLARE_AI_TOKEN'),
            'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
            'model' => env('CLOUDFLARE_TEXT_MODEL', '@cf/meta/llama-3.3-70b-instruct-fp8-fast'),
            'models' => [
                '@cf/meta/llama-3.3-70b-instruct-fp8-fast' => 'Llama 3.3 70B — fast, free allowance',
            ],
            'max_tokens' => 8000,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Request limits
    |--------------------------------------------------------------------------
    */

    'max_tokens' => (int) env('AI_MAX_TOKENS', 16000),
    'timeout' => (int) env('AI_TIMEOUT', 180),
    'retries' => (int) env('AI_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Spend guard
    |--------------------------------------------------------------------------
    |
    | A runaway scheduler or a stuck retry loop is the realistic way this costs
    | real money. The daily cap is checked before every call.
    |
    */

    'daily_limit' => (int) env('AI_DAILY_GENERATION_LIMIT', 50),

    /*
    |--------------------------------------------------------------------------
    | Approximate prices per million tokens, for the cost column in the admin.
    |--------------------------------------------------------------------------
    |
    | Indicative only - billing is whatever the provider actually charges.
    |
    | A model with no entry here reports no cost rather than a made-up one, and
    | the admin shows "—" instead of "$0.0000". Fill an id in from
    | https://ai.google.dev/pricing when you want the column populated; a wrong
    | number is worse than an honest blank.
    |
    */

    'pricing' => [
        // Retired by Google, kept for generations already recorded against them.
        'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.00],
        'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50],

        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
    ],

];
