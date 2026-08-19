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

        /*
         * Draw a branded card for any generated post that has no image of its
         * own. Costs nothing and gives every article its own share picture,
         * which is the difference between a link with a thumbnail and one
         * without in every feed it lands in.
         */
        'auto_featured_image' => env('AUTO_FEATURED_IMAGE', true),

        /*
        |----------------------------------------------------------------------
        | Where a post's picture comes from
        |----------------------------------------------------------------------
        |
        | Strategies are tried in order until one returns an image. 'card' never
        | fails, so it belongs last in every list.
        |
        |   stock         a real, licensed photograph from Pexels
        |   illustration  an AI drawing, labelled as one on the page
        |   card          the branded headline card this site already draws
        |
        | The default is 'card' alone, and that is deliberate. A news report
        | about a named person or a specific incident must not carry a stock
        | photo that looks like documentary evidence of it, and it must never
        | carry an AI picture of an event that did not happen. Sections opt in
        | below, and only where the picture is understood as decoration or as a
        | generic illustration of a subject rather than a record of an event.
        |
        */
        'strategy' => [
            '*' => ['card'],

            // A photograph of a trading floor or a phone illustrates the
            // subject without claiming to be the story.
            'business' => ['stock', 'card'],
            'technology' => ['stock', 'card'],
            'sports' => ['stock', 'card'],
            'travel' => ['stock', 'card'],
            'education' => ['stock', 'card'],
            'health' => ['stock', 'card'],
            'entertainment' => ['stock', 'card'],
            'lifestyle' => ['stock', 'illustration', 'card'],

            // Nothing factual is being depicted here, so a drawing is honest.
            'astrology' => ['illustration', 'card'],
            'devotional' => ['illustration', 'card'],
            'quiz-fun' => ['illustration', 'card'],
        ],

        'stock' => [
            // Free for commercial use, no attribution required by licence -
            // the photographer is credited on the page anyway, because taking
            // the credit off someone's work is a poor way to use a free gift.
            'endpoint' => 'https://api.pexels.com/v1/search',
            'key' => env('PEXELS_API_KEY'),
            'orientation' => 'landscape',
            // Below this the photo is worse than no photo on a wide card.
            'min_width' => 1200,
        ],

        'illustration' => [
            // Imagen, reached with the same Gemini key. A missing key simply
            // means this strategy is skipped and the next one runs.
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'model' => env('GEMINI_IMAGE_MODEL', 'imagen-4.0-generate-001'),
            'key' => env('GEMINI_API_KEY'),
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
