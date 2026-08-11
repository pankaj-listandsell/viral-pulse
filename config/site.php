<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin account
    |--------------------------------------------------------------------------
    |
    | Used only by AdminUserSeeder. Leaving the password empty makes the seeder
    | generate a strong one and print it a single time.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Site Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@viralpulse.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content automation
    |--------------------------------------------------------------------------
    |
    | auto_publish stays false by default: AI writes drafts and a human decides
    | what goes live. Generated content must also clear these quality floors
    | before it is eligible for publishing at all.
    |
    */

    'content' => [
        'auto_publish' => env('AUTO_PUBLISH', false),
        'auto_generate' => env('AUTO_GENERATE_ENABLED', false),
        'min_words' => (int) env('CONTENT_MIN_WORDS', 400),
        'min_quality_score' => (int) env('CONTENT_MIN_QUALITY_SCORE', 70),
        'words_per_minute' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | imagick is not available in this environment, so GD is the driver. The
    | allowlist is enforced against the sniffed MIME type, not the filename.
    |
    */

    'media' => [
        'disk' => env('FILESYSTEM_DISK', 'public'),
        'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 5120),
        'driver' => env('MEDIA_IMAGE_DRIVER', 'gd'),
        'webp' => env('MEDIA_WEBP_ENABLED', true),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'conversions' => [
            'thumbnail' => ['width' => 320, 'height' => 200],
            'medium' => ['width' => 768, 'height' => 480],
            'large' => ['width' => 1280, 'height' => 720],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trending sources
    |--------------------------------------------------------------------------
    |
    | RSS feeds and official APIs only. Nothing here scrapes HTML.
    |
    */

    'trending' => [
        'rss_feeds' => array_filter(explode(',', (string) env('TRENDING_RSS_FEEDS'))),
        'news_api_key' => env('NEWS_API_KEY'),
        'region' => env('TRENDING_REGION', 'IN'),
        'user_agent' => 'ViralPulseBot/1.0 (+'.env('APP_URL', 'http://localhost').')',
    ],

    /*
    |--------------------------------------------------------------------------
    | AdSense
    |--------------------------------------------------------------------------
    |
    | Ad components render nothing unless enabled and given a slot id. These
    | values are defaults; the settings table overrides them at runtime.
    |
    */

    'adsense' => [
        'enabled' => env('ADSENSE_ENABLED', false),
        'client_id' => env('ADSENSE_CLIENT_ID'),
        'slots' => [
            'header' => env('ADSENSE_SLOT_HEADER'),
            'article' => env('ADSENSE_SLOT_ARTICLE'),
            'sidebar' => env('ADSENSE_SLOT_SIDEBAR'),
            'footer' => env('ADSENSE_SLOT_FOOTER'),
        ],
    ],

    'analytics' => [
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data retention
    |--------------------------------------------------------------------------
    |
    | Enforced by the data:cleanup command. Raw post_views rows are pruned once
    | they have been rolled up into post_daily_stats.
    |
    */

    'retention' => [
        'analytics_days' => (int) env('ANALYTICS_RETENTION_DAYS', 90),
        'activity_log_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 180),
    ],

];
