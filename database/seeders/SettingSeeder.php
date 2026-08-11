<?php

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seeds defaults only. Existing values are never overwritten, so running
     * this again after an admin has edited settings is safe.
     */
    public function run(): void
    {
        foreach ($this->settings() as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'] ?? false,
                    'description' => $setting['description'] ?? null,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function settings(): array
    {
        return [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => config('app.name'), 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Trending stories, explained fast.', 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Daily trending news, technology, entertainment and explainers.', 'type' => SettingType::Text, 'is_public' => true],
            ['group' => 'general', 'key' => 'site_logo', 'value' => null, 'type' => SettingType::File, 'is_public' => true],
            ['group' => 'general', 'key' => 'site_favicon', 'value' => null, 'type' => SettingType::File, 'is_public' => true],
            ['group' => 'general', 'key' => 'contact_email', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'general', 'key' => 'posts_per_page', 'value' => '12', 'type' => SettingType::Integer, 'is_public' => true],
            ['group' => 'general', 'key' => 'timezone', 'value' => config('app.timezone'), 'type' => SettingType::String],

            // SEO
            ['group' => 'seo', 'key' => 'seo_default_title', 'value' => config('app.name'), 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_default_description', 'value' => 'Trending stories, explained fast.', 'type' => SettingType::Text, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_default_keywords', 'value' => null, 'type' => SettingType::String],
            ['group' => 'seo', 'key' => 'seo_default_og_image', 'value' => null, 'type' => SettingType::File, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_twitter_handle', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_robots_default', 'value' => 'index,follow', 'type' => SettingType::String],

            // Social
            ['group' => 'social', 'key' => 'social_facebook', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'social', 'key' => 'social_twitter', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'social', 'key' => 'social_instagram', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'social', 'key' => 'social_youtube', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'social', 'key' => 'social_telegram', 'value' => null, 'type' => SettingType::String, 'is_public' => true],

            // AdSense - disabled until a real client id is configured
            ['group' => 'adsense', 'key' => 'adsense_enabled', 'value' => '0', 'type' => SettingType::Boolean, 'is_public' => true],
            ['group' => 'adsense', 'key' => 'adsense_client_id', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'adsense', 'key' => 'adsense_slot_header', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'adsense', 'key' => 'adsense_slot_article', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'adsense', 'key' => 'adsense_slot_sidebar', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'adsense', 'key' => 'adsense_slot_footer', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'adsense', 'key' => 'adsense_ads_txt', 'value' => null, 'type' => SettingType::Text],

            // Analytics
            ['group' => 'analytics', 'key' => 'google_analytics_id', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'analytics', 'key' => 'google_site_verification', 'value' => null, 'type' => SettingType::String],

            // Features
            ['group' => 'features', 'key' => 'newsletter_enabled', 'value' => '1', 'type' => SettingType::Boolean, 'is_public' => true],
            ['group' => 'features', 'key' => 'comments_enabled', 'value' => '1', 'type' => SettingType::Boolean, 'is_public' => true],
            ['group' => 'features', 'key' => 'comments_require_approval', 'value' => '1', 'type' => SettingType::Boolean],
            ['group' => 'features', 'key' => 'likes_enabled', 'value' => '1', 'type' => SettingType::Boolean, 'is_public' => true],
            ['group' => 'features', 'key' => 'search_enabled', 'value' => '1', 'type' => SettingType::Boolean, 'is_public' => true],
            ['group' => 'features', 'key' => 'show_ai_disclosure', 'value' => '1', 'type' => SettingType::Boolean, 'is_public' => true],

            // AI
            ['group' => 'ai', 'key' => 'ai_auto_publish', 'value' => '0', 'type' => SettingType::Boolean],
            ['group' => 'ai', 'key' => 'ai_auto_generate', 'value' => '0', 'type' => SettingType::Boolean],
            ['group' => 'ai', 'key' => 'ai_default_language', 'value' => 'en', 'type' => SettingType::String],
            ['group' => 'ai', 'key' => 'ai_default_tone', 'value' => 'informative', 'type' => SettingType::String],
            ['group' => 'ai', 'key' => 'ai_daily_limit', 'value' => '50', 'type' => SettingType::Integer],
        ];
    }
}
