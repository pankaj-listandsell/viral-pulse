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
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Daily coverage of what India is searching for — trending news, technology, entertainment, sport and clear explainers, published throughout the day.', 'type' => SettingType::Text, 'is_public' => true],
            ['group' => 'general', 'key' => 'site_logo', 'value' => null, 'type' => SettingType::File, 'is_public' => true],
            ['group' => 'general', 'key' => 'site_favicon', 'value' => null, 'type' => SettingType::File, 'is_public' => true],
            ['group' => 'general', 'key' => 'contact_email', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'general', 'key' => 'posts_per_page', 'value' => '12', 'type' => SettingType::Integer, 'is_public' => true],
            ['group' => 'general', 'key' => 'timezone', 'value' => config('app.timezone'), 'type' => SettingType::String],

            // SEO
            ['group' => 'seo', 'key' => 'seo_default_title', 'value' => config('app.name'), 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_default_description', 'value' => 'Trending stories explained clearly and quickly — news, technology, entertainment and sport, updated through the day as things develop.', 'type' => SettingType::Text, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_default_keywords', 'value' => null, 'type' => SettingType::String],
            ['group' => 'seo', 'key' => 'seo_default_og_image', 'value' => null, 'type' => SettingType::File, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_twitter_handle', 'value' => null, 'type' => SettingType::String, 'is_public' => true],
            ['group' => 'seo', 'key' => 'seo_robots_default', 'value' => 'index, follow', 'type' => SettingType::String],
            // Kill switch for robots.txt. Useful while a site is being filled
            // with content and is not worth indexing yet.
            ['group' => 'seo', 'key' => 'seo_discourage_indexing', 'value' => '0', 'type' => SettingType::Boolean],

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

            // Publishing. Seeded from the environment for the same reason.
            ['group' => 'publishing', 'key' => 'publish_mode', 'value' => (string) config('trending.publishing.mode', 'scheduled'), 'type' => SettingType::String],
            ['group' => 'publishing', 'key' => 'publish_slots', 'value' => null, 'type' => SettingType::String],
            ['group' => 'publishing', 'key' => 'publish_max_per_day', 'value' => (string) config('trending.publishing.max_per_day', 8), 'type' => SettingType::Integer],
            ['group' => 'publishing', 'key' => 'publish_lead_minutes', 'value' => (string) config('trending.publishing.lead_minutes', 15), 'type' => SettingType::Integer],
            ['group' => 'publishing', 'key' => 'publish_lookahead_hours', 'value' => (string) config('trending.publishing.max_lookahead_hours', 3), 'type' => SettingType::Integer],
            ['group' => 'publishing', 'key' => 'trending_generate_per_run', 'value' => (string) config('trending.automation.per_run', 2), 'type' => SettingType::Integer],
            ['group' => 'publishing', 'key' => 'trending_min_score', 'value' => (string) config('trending.automation.min_score', 45), 'type' => SettingType::Integer],

            // AI. Seeded from the environment rather than hardcoded: these
            // rows override config once set, so a default that disagreed with
            // .env would silently undo whatever the environment asked for.
            ['group' => 'ai', 'key' => 'ai_auto_publish', 'value' => config('site.content.auto_publish') ? '1' : '0', 'type' => SettingType::Boolean],
            ['group' => 'ai', 'key' => 'ai_auto_generate', 'value' => config('trending.automation.enabled') ? '1' : '0', 'type' => SettingType::Boolean],
            ['group' => 'ai', 'key' => 'ai_default_language', 'value' => 'en', 'type' => SettingType::String],
            ['group' => 'ai', 'key' => 'ai_default_tone', 'value' => 'informative', 'type' => SettingType::String],
            ['group' => 'ai', 'key' => 'ai_daily_limit', 'value' => (string) config('ai.daily_limit', 50), 'type' => SettingType::Integer],
        ];
    }
}
