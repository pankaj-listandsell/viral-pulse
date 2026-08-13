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
        'publish_slots' => 'trending.publishing.slots',
        'publish_max_per_day' => 'trending.publishing.max_per_day',
        'publish_lead_minutes' => 'trending.publishing.lead_minutes',
        'trending_generate_per_run' => 'trending.automation.per_run',
        'trending_min_score' => 'trending.automation.min_score',
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
    }
}
