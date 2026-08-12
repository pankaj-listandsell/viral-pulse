<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Copies the shipped brand assets onto the media disk and points the settings
 * at them.
 *
 * They live in resources/brand because storage/app/public is gitignored like
 * every other upload - without this the logo, favicon and share card would
 * silently disappear on the first deploy, and og:image with them.
 *
 * Nothing already set is overwritten, so an admin who uploads their own
 * artwork keeps it.
 */
class BrandSeeder extends Seeder
{
    private const ASSETS = [
        // The square mark, not the wordmark: the header renders the site name as
        // live text beside it, and a wordmark there would print the name twice.
        'site_logo' => ['mark.png', 'settings/site-logo.png'],
        'site_favicon' => ['favicon.png', 'settings/site-favicon.png'],
        'seo_default_og_image' => ['share-card.png', 'settings/share-card.png'],
    ];

    public function run(): void
    {
        $disk = Storage::disk(config('site.media.disk'));
        $written = 0;

        foreach (self::ASSETS as $key => [$source, $target]) {
            $path = resource_path("brand/{$source}");

            if (! is_file($path)) {
                continue;
            }

            if (! $disk->exists($target)) {
                $disk->put($target, file_get_contents($path));
            }

            $setting = Setting::firstWhere('key', $key);

            // Only fills a blank. Replacing an admin's own logo every time the
            // seeders run would be its own small disaster.
            if ($setting && blank($setting->value)) {
                $setting->update(['value' => $target]);
                $written++;
            }
        }

        app(SettingsService::class)->flush();

        $this->command?->line("  Brand assets installed ({$written} setting(s) filled).");
    }
}
