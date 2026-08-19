<?php

namespace App\Support;

use App\Enums\ContentTone;
use App\Enums\SettingType;
use App\Rules\ValidTimeList;
use Illuminate\Validation\Rule;

/**
 * The single description of every editable setting: its label, its input type
 * and its validation rules.
 *
 * The form and the validator are both generated from this, so a field can
 * never be rendered without rules or validated against rules for a field that
 * is no longer on screen.
 */
final class SettingsSchema
{
    public const PUBLISH_MODES = [
        'immediate' => 'Write and publish at the time — simplest',
        'scheduled' => 'Write ahead, publish exactly at the time — safest',
    ];

    private const ROBOTS = [
        'index, follow' => 'index, follow — normal',
        'noindex, follow' => 'noindex, follow — crawl but do not list',
        'index, nofollow' => 'index, nofollow',
        'noindex, nofollow' => 'noindex, nofollow — hide entirely',
    ];

    /**
     * @return array<string, string>
     */
    private static function tones(): array
    {
        $tones = [];

        foreach (ContentTone::cases() as $tone) {
            $tones[$tone->value] = $tone->label();
        }

        return $tones;
    }

    /**
     * @return array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'description' => 'What the site is called and how it introduces itself.',
                'fields' => [
                    ['key' => 'site_name', 'label' => 'Site name', 'input' => 'text', 'rules' => ['required', 'string', 'max:100']],
                    ['key' => 'site_tagline', 'label' => 'Site Tagline (SEO)', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:150'], 'help' => 'Included in homepage title & search results (e.g. Trending Stories, Viral News & Explainers).'],
                    ['key' => 'site_description', 'label' => 'Description', 'input' => 'textarea', 'rules' => ['nullable', 'string', 'max:500'], 'help' => 'Used as the fallback meta description and in the RSS feed.'],
                    ['key' => 'contact_email', 'label' => 'Contact email', 'input' => 'email', 'rules' => ['nullable', 'email:rfc', 'max:255'], 'help' => 'Where contact form notifications are sent. Falls back to the admin account.'],
                    ['key' => 'posts_per_page', 'label' => 'Posts per page', 'input' => 'number', 'rules' => ['required', 'integer', 'min:3', 'max:48']],
                    ['key' => 'timezone', 'label' => 'Timezone', 'input' => 'timezone', 'rules' => ['required', 'timezone']],
                    // No SVG. It is markup, it can carry script, and opening
                    // the uploaded file directly would run that script on this
                    // origin. A PNG cannot do that.
                    ['key' => 'site_logo', 'label' => 'Logo', 'input' => 'image', 'rules' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024']],
                    ['key' => 'site_favicon', 'label' => 'Favicon', 'input' => 'image', 'rules' => ['nullable', 'image', 'mimes:png,ico,webp', 'max:256'], 'help' => 'A square PNG works everywhere. 512×512 is plenty.'],
                ],
            ],

            'social' => [
                'label' => 'Social',
                'description' => 'Profile links. These also feed the sameAs list in the site’s structured data.',
                'fields' => [
                    ['key' => 'social_facebook', 'label' => 'Facebook', 'input' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['key' => 'social_twitter', 'label' => 'X / Twitter', 'input' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['key' => 'social_instagram', 'label' => 'Instagram', 'input' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['key' => 'social_youtube', 'label' => 'YouTube', 'input' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                    ['key' => 'social_telegram', 'label' => 'Telegram', 'input' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                ],
            ],

            'adsense' => [
                'label' => 'AdSense',
                'description' => 'Ad slots render nothing until this is switched on and given a publisher id.',
                'fields' => [
                    ['key' => 'adsense_enabled', 'label' => 'Show ads', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'adsense_client_id', 'label' => 'Publisher id', 'input' => 'text', 'rules' => ['nullable', 'string', 'regex:/^ca-pub-\d{16}$/'], 'help' => 'Looks like ca-pub-0000000000000000. Also used to build ads.txt.'],
                    ['key' => 'adsense_slot_header', 'label' => 'Header slot', 'input' => 'text', 'rules' => ['nullable', 'string', 'regex:/^\d{6,20}$/']],
                    ['key' => 'adsense_slot_article', 'label' => 'In-article slot', 'input' => 'text', 'rules' => ['nullable', 'string', 'regex:/^\d{6,20}$/']],
                    ['key' => 'adsense_slot_sidebar', 'label' => 'Sidebar slot', 'input' => 'text', 'rules' => ['nullable', 'string', 'regex:/^\d{6,20}$/']],
                    ['key' => 'adsense_slot_footer', 'label' => 'Footer slot', 'input' => 'text', 'rules' => ['nullable', 'string', 'regex:/^\d{6,20}$/']],
                    ['key' => 'adsense_ads_txt', 'label' => 'ads.txt override', 'input' => 'textarea', 'rules' => ['nullable', 'string', 'max:5000'], 'help' => 'Leave empty and the file is built from the publisher id. Fill it in only if you sell through more than one network.'],
                ],
            ],

            'analytics' => [
                'label' => 'Analytics',
                'description' => 'Third-party tags. Both are optional and neither is loaded when empty.',
                'fields' => [
                    ['key' => 'google_analytics_id', 'label' => 'Google Analytics id', 'input' => 'text', 'rules' => ['nullable', 'string', 'regex:/^(G-[A-Z0-9]{4,}|UA-\d{4,}-\d+)$/'], 'help' => 'A GA4 id starts with G-.'],
                    ['key' => 'google_site_verification', 'label' => 'Search Console verification', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'The content value of the meta tag Google gives you.'],
                ],
            ],

            'features' => [
                'label' => 'Features',
                'description' => 'Switches for the parts of the site visitors interact with.',
                'fields' => [
                    ['key' => 'web_stories_enabled', 'label' => 'Web stories carousel', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'Show or hide the visual web stories carousel on the home page.'],
                    ['key' => 'horoscope_enabled', 'label' => 'Daily horoscope & zodiac', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'Show or hide the daily horoscope & zodiac carousel and page.'],
                    ['key' => 'likes_enabled', 'label' => 'Likes on articles', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'search_enabled', 'label' => 'Site search', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'newsletter_enabled', 'label' => 'Newsletter signup', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'show_ai_disclosure', 'label' => 'Disclose AI-written articles', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'Leave this on. Hiding it is the kind of thing an AdSense review treats as deceptive.'],
                ],
            ],

            'publishing' => [
                'label' => 'Publishing',
                'description' => 'When automatically written articles go live. Times are in the site timezone ('.config('app.timezone').').',
                'fields' => [
                    ['key' => 'publish_mode', 'label' => 'How publishing works', 'input' => 'select', 'options' => self::PUBLISH_MODES, 'rules' => ['required', Rule::in(array_keys(self::PUBLISH_MODES))],
                        'help' => 'Write and publish at the time is simpler and the article is minutes old. Write ahead is safer: if the model fails or the article is rejected, the slot still has something in it.'],
                    ['key' => 'publish_slots', 'label' => 'Publishing times', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:255', new ValidTimeList],
                        'help' => 'Exact times of day, comma separated — for example 08:00, 12:30, 17:00, 20:30. An article is scheduled for the next free one. Leave empty to space posts evenly instead.'],
                    ['key' => 'publish_max_per_day', 'label' => 'Maximum posts per day', 'input' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:48'],
                        'help' => 'Counts posts published by hand too, so the site never exceeds this in a day.'],
                    ['key' => 'publish_lead_minutes', 'label' => 'Minimum notice', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:240'],
                        'help' => 'Never schedule closer than this to now, so a slot is not missed while the article is still being written.'],
                    ['key' => 'publish_lookahead_hours', 'label' => 'Write this far ahead', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:168'],
                        'help' => 'Hours. An article is only written once its slot is this close, so trending news is fresh when it publishes rather than a day old. 0 removes the limit.'],
                    ['key' => 'trending_generate_per_run', 'label' => 'Articles per run', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:20'],
                        'help' => 'How many articles each hourly run starts. Every one costs an API call.'],
                    ['key' => 'trending_min_score', 'label' => 'Minimum topic score', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'],
                        'help' => 'Topics scoring below this are not worth writing about. Lower it if too few articles are being produced.'],
                ],
            ],

            'trending' => [
                'label' => 'Trending',
                'description' => 'Where topics come from and which ones are worth writing about. Published feeds and official APIs only — nothing here scrapes a website.',
                'fields' => [
                    ['key' => 'trending_google_trends', 'label' => 'Google Trends', 'input' => 'boolean', 'rules' => ['boolean'],
                        'help' => 'What people are actually searching for. The best demand signal available, and it needs no key.'],
                    ['key' => 'trending_google_news', 'label' => 'Google News', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'trending_region', 'label' => 'Region', 'input' => 'text', 'rules' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
                        'help' => 'Two-letter country code — IN for India, US, GB. Decides which country’s trends are pulled.'],
                    ['key' => 'trending_language', 'label' => 'Language', 'input' => 'text', 'rules' => ['required', 'string', 'size:2', 'regex:/^[a-z]{2}$/']],
                    ['key' => 'trending_rss_feeds', 'label' => 'Extra RSS feeds', 'input' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000'],
                        'help' => 'One URL per line. Add |category-slug to pin a feed to a section, for example https://example.com/feed.xml|technology'],
                    ['key' => 'trending_max_age_hours', 'label' => 'Ignore topics older than', 'input' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:336'],
                        'help' => 'Hours. A stale topic is already covered everywhere else, so writing about it ranks nowhere.'],
                    ['key' => 'trending_target_words', 'label' => 'Target article length', 'input' => 'number', 'rules' => ['required', 'integer', 'min:200', 'max:3000'],
                        'help' => 'Words. What the model is asked for, not what it is guaranteed to produce.'],
                ],
            ],

            'quality' => [
                'label' => 'Quality',
                'description' => 'The gate between the model and the site. An article that fails it stays a draft no matter what the publishing settings say.',
                'fields' => [
                    ['key' => 'content_min_words', 'label' => 'Minimum words', 'input' => 'number', 'rules' => ['required', 'integer', 'min:100', 'max:3000'],
                        'help' => 'Anything shorter loses 40 points, which on its own is usually enough to fail.'],
                    ['key' => 'content_min_quality_score', 'label' => 'Minimum quality score', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'],
                        'help' => 'Out of 100. At 70 a truncated, too-short or duplicate-titled article is blocked. Below about 60 the gate stops catching the things it exists for.'],
                    ['key' => 'auto_featured_image', 'label' => 'Draw a card for articles with no image', 'input' => 'boolean', 'rules' => ['boolean'],
                        'help' => 'Costs nothing and gives every article its own picture when shared.'],
                    ['key' => 'media_max_upload_kb', 'label' => 'Maximum upload size', 'input' => 'number', 'rules' => ['required', 'integer', 'min:256', 'max:20480'],
                        'help' => 'Kilobytes. Your PHP upload_max_filesize is the real ceiling; this cannot raise it, only lower it.'],
                    ['key' => 'media_webp_enabled', 'label' => 'Convert uploads to WebP', 'input' => 'boolean', 'rules' => ['boolean'],
                        'help' => 'Smaller files for the same quality, which is one of the things a page-speed score actually measures.'],
                ],
            ],

            'keys' => [
                'label' => 'API keys',
                'description' => 'Stored encrypted with your APP_KEY, so a stolen database dump holds ciphertext rather than working keys. Leave a field blank to keep the key already saved.',
                'fields' => [
                    ['key' => 'gemini_api_key', 'label' => 'Gemini API key', 'input' => 'secret', 'rules' => ['nullable', 'string', 'max:400'],
                        'help' => 'From aistudio.google.com/apikey. Needed for the article writer.'],
                    ['key' => 'openai_api_key', 'label' => 'OpenAI API key', 'input' => 'secret', 'rules' => ['nullable', 'string', 'max:400'],
                        'help' => 'Optional. Only needed if you switch the provider to OpenAI.'],
                    ['key' => 'news_api_key', 'label' => 'News API key', 'input' => 'secret', 'rules' => ['nullable', 'string', 'max:400'],
                        'help' => 'Optional. Adds newsapi.org as an extra trending source; Google Trends and Google News need no key.'],
                ],
            ],

            'ai' => [
                'label' => 'AI',
                'description' => 'How much the generator is allowed to do on its own. The provider, the model and the API key live elsewhere.',
                'fields' => [
                    ['key' => 'ai_auto_generate', 'label' => 'Write trending topics automatically', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'The scheduled run spends money on every pass. Off by default.'],
                    ['key' => 'ai_daily_limit', 'label' => 'Daily generation cap', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:500'], 'help' => 'A stuck scheduler is the realistic way this runs up a bill. 0 removes the cap.'],
                    ['key' => 'ai_max_tokens', 'label' => 'Maximum response size', 'input' => 'number', 'rules' => ['required', 'integer', 'min:2000', 'max:64000'], 'help' => 'Tokens. Too low and long articles are cut off mid-sentence, which the quality gate then rejects.'],
                    ['key' => 'ai_timeout', 'label' => 'Request timeout', 'input' => 'number', 'rules' => ['required', 'integer', 'min:30', 'max:600'], 'help' => 'Seconds to wait for the model before giving up.'],
                    ['key' => 'ai_retries', 'label' => 'Retries', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:5'], 'help' => 'Only rate limits and overloads are retried. A rejected key fails immediately however high this is.'],
                    ['key' => 'analytics_retention_days', 'label' => 'Keep raw view data for', 'input' => 'number', 'rules' => ['required', 'integer', 'min:7', 'max:730'], 'help' => 'Days. Views are rolled into daily totals first, so the charts keep working after the raw rows are pruned.'],
                    ['key' => 'activity_log_retention_days', 'label' => 'Keep the activity log for', 'input' => 'number', 'rules' => ['required', 'integer', 'min:7', 'max:730'], 'help' => 'Days.'],
                    ['key' => 'ai_default_language', 'label' => 'Default language', 'input' => 'select', 'options' => ['en' => 'English', 'hi' => 'Hindi'], 'rules' => ['required', 'in:en,hi']],
                    ['key' => 'ai_default_tone', 'label' => 'Default tone', 'input' => 'select', 'options' => self::tones(), 'rules' => ['required', Rule::in(array_column(ContentTone::cases(), 'value'))]],
                ],
            ],
        ];
    }

    /**
     * The SEO group is edited on its own screen, so it is kept out of groups().
     *
     * @return array{label: string, description: string, fields: array<int, array<string, mixed>>}
     */
    public static function seo(): array
    {
        return [
            'label' => 'SEO',
            'description' => 'Defaults used whenever a page does not set its own.',
            'fields' => [
                ['key' => 'seo_default_title', 'label' => 'Default title', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:70'], 'help' => 'Google truncates past roughly 60 characters.'],
                ['key' => 'seo_default_description', 'label' => 'Default description', 'input' => 'textarea', 'rules' => ['nullable', 'string', 'max:160'], 'help' => 'Aim for 150-160 characters.'],
                ['key' => 'seo_default_keywords', 'label' => 'Default keywords', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'help' => 'Ignored by every major search engine since 2009. Kept because some internal tools still read it.'],
                ['key' => 'seo_twitter_handle', 'label' => 'X / Twitter handle', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:50'], 'help' => 'Used for the twitter:site card attribution.'],
                // Rule::in rather than the string form: these values contain
                // commas, which the "in:a,b" syntax uses as its separator.
                ['key' => 'seo_robots_default', 'label' => 'Default robots directive', 'input' => 'select', 'options' => self::ROBOTS, 'rules' => ['required', Rule::in(array_keys(self::ROBOTS))]],
                ['key' => 'seo_discourage_indexing', 'label' => 'Block all crawlers in robots.txt', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'Turns robots.txt into a blanket Disallow. Useful while the site is being filled; remember to turn it off.'],
                ['key' => 'seo_default_og_image', 'label' => 'Default share image', 'input' => 'image', 'rules' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'help' => '1200×630 is the size every network crops to.'],
                ['key' => 'onesignal_app_id', 'label' => 'OneSignal App ID', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'help' => 'Required for push notifications. From your OneSignal App Dashboard.'],
                ['key' => 'onesignal_safari_web_id', 'label' => 'OneSignal Safari Web ID', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'help' => 'Optional. Needed to support Safari Web Push.'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fields(string $group): array
    {
        if ($group === 'seo') {
            return self::seo()['fields'];
        }

        return self::groups()[$group]['fields'] ?? [];
    }

    /**
     * Rules keyed by the request field name.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $group): array
    {
        $rules = [];

        foreach (self::fields($group) as $field) {
            $rules[$field['key']] = $field['rules'];
        }

        return $rules;
    }

    /**
     * Which fields in a group are uploads. They are handled separately from
     * scalar values because a missing file means "leave it alone", not "clear it".
     *
     * @return array<int, string>
     */
    public static function imageKeys(string $group): array
    {
        return array_values(array_map(
            fn (array $field) => $field['key'],
            array_filter(self::fields($group), fn (array $field) => $field['input'] === 'image')
        ));
    }

    public static function typeFor(array $field): SettingType
    {
        return match ($field['input']) {
            'boolean' => SettingType::Boolean,
            'number' => SettingType::Integer,
            'textarea' => SettingType::Text,
            'image' => SettingType::File,
            default => SettingType::String,
        };
    }
}
