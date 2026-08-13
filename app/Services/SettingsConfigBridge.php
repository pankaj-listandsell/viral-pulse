<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Lets the settings screen actually change how the application behaves.
 *
 * Without this, several switches in the admin were writing rows nobody read:
 * the code asked config(), config asked .env, and the toggle did nothing. A
 * control that appears to work and does not is worse than no control at all.
 *
 * .env still supplies the defaults. A setting only wins once it has a value,
 * so an untouched installation behaves exactly as its environment says.
 */
class SettingsConfigBridge
{
    /**
     * setting key => config key.
     *
     * @var array<string, string>
     */
    private const MAP = [
        'ai_auto_publish' => 'site.content.auto_publish',
        'ai_auto_generate' => 'trending.automation.enabled',
        'ai_daily_limit' => 'ai.daily_limit',
        'publish_mode' => 'trending.publishing.mode',
        'publish_slots' => 'trending.publishing.slots',
        'publish_max_per_day' => 'trending.publishing.max_per_day',
        'publish_lead_minutes' => 'trending.publishing.lead_minutes',
        'publish_lookahead_hours' => 'trending.publishing.max_lookahead_hours',
        'trending_generate_per_run' => 'trending.automation.per_run',
        'trending_min_score' => 'trending.automation.min_score',
        'trending_max_age_hours' => 'trending.automation.max_age_hours',
        'trending_target_words' => 'trending.automation.target_words',
        'trending_region' => 'trending.region',
        'trending_language' => 'trending.language',
        'trending_google_trends' => 'trending.sources.google_trends.enabled',
        'trending_google_news' => 'trending.sources.google_news.enabled',
        'content_min_words' => 'site.content.min_words',
        'content_min_quality_score' => 'site.content.min_quality_score',
        'auto_featured_image' => 'site.media.auto_featured_image',
        'ai_timeout' => 'ai.timeout',
        'ai_retries' => 'ai.retries',
        'analytics_retention_days' => 'site.retention.analytics_days',
        'activity_log_retention_days' => 'site.retention.activity_log_days',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    public function apply(): void
    {
        try {
            $stored = $this->settings->all();
        } catch (\Throwable $e) {
            // The settings table does not exist yet during the first migrate,
            // and a boot-time failure there would make the app impossible to
            // install. Falling back to .env is the correct behaviour anyway.
            Log::debug('Settings could not be read; using environment defaults.', ['error' => $e->getMessage()]);

            return;
        }

        foreach (self::MAP as $setting => $key) {
            if (! array_key_exists($setting, $stored)) {
                continue;
            }

            $value = $stored[$setting];

            // An empty string means "not configured", not "set to nothing" -
            // clearing a field should hand control back to .env rather than
            // silently zeroing a limit.
            if ($value === null || $value === '') {
                continue;
            }

            config([$key => $value]);
        }

        // Not in MAP: this one is typed as text but config holds a list.
        $this->applyCustomFeeds($stored['trending_rss_feeds'] ?? null);
    }

    /**
     * Extra feeds are typed one per line, which is far easier to read and edit
     * than the comma-separated string the environment variable used.
     */
    private function applyCustomFeeds(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $feeds = array_values(array_filter(array_map(
            'trim',
            preg_split('/[\r\n,]+/', $value) ?: []
        )));

        config(['trending.custom_feeds' => $feeds]);
    }
}
