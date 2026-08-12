<?php

namespace App\Support;

use App\Enums\ContentTone;
use App\Enums\SettingType;
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
                    ['key' => 'site_tagline', 'label' => 'Tagline', 'input' => 'text', 'rules' => ['nullable', 'string', 'max:150'], 'help' => 'One line, shown under the name.'],
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
                    ['key' => 'likes_enabled', 'label' => 'Likes on articles', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'search_enabled', 'label' => 'Site search', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'newsletter_enabled', 'label' => 'Newsletter signup', 'input' => 'boolean', 'rules' => ['boolean']],
                    ['key' => 'show_ai_disclosure', 'label' => 'Disclose AI-written articles', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'Leave this on. Hiding it is the kind of thing an AdSense review treats as deceptive.'],
                ],
            ],

            'ai' => [
                'label' => 'AI',
                'description' => 'How much the generator is allowed to do on its own. The provider, the model and the API key live elsewhere.',
                'fields' => [
                    ['key' => 'ai_auto_generate', 'label' => 'Write trending topics automatically', 'input' => 'boolean', 'rules' => ['boolean'], 'help' => 'The scheduled run spends money on every pass. Off by default.'],
                    ['key' => 'ai_daily_limit', 'label' => 'Daily generation cap', 'input' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:500'], 'help' => 'A stuck scheduler is the realistic way this runs up a bill. 0 removes the cap.'],
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
