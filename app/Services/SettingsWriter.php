<?php

namespace App\Services;

use App\Support\SettingsSchema;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes one schema group from a request.
 *
 * Shared by the site settings screen and the SEO one so both handle booleans,
 * uploads and cache invalidation identically - the three things that are easy
 * to get subtly wrong in only one of two copies.
 */
class SettingsWriter
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly ContentFeedService $feed,
        private readonly SitemapService $sitemap,
        private readonly FeedService $rss,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function save(Request $request, string $group, array $validated): void
    {
        $imageKeys = SettingsSchema::imageKeys($group);
        $values = [];

        foreach (SettingsSchema::fields($group) as $field) {
            $key = $field['key'];

            if (in_array($key, $imageKeys, true)) {
                continue;
            }

            // An unchecked box is absent from the request rather than false, so
            // booleans are read from the request itself. Taken from $validated,
            // a toggle could be switched on but never off.
            $values[$key] = $field['input'] === 'boolean'
                ? $request->boolean($key)
                : ($validated[$key] ?? null);
        }

        foreach ($imageKeys as $key) {
            if ($request->boolean("remove_{$key}")) {
                $this->deleteStored($key);
                $values[$key] = null;

                continue;
            }

            // No file in the request means "leave it alone", not "clear it".
            if ($request->hasFile($key)) {
                $this->deleteStored($key);
                $values[$key] = $this->storeImage($request->file($key), $key);
            }
        }

        $this->settings->setMany($values);
        $this->flushCaches();

        $this->logger->log('settings.updated', null, "Updated the {$group} settings");
    }

    /**
     * The site name reaches the RSS channel, the sitemap is built from routes a
     * settings change can alter, and cards carry the per-page count. Any of
     * them would otherwise serve the previous values for up to an hour.
     */
    public function flushCaches(): void
    {
        $this->settings->flush();
        $this->feed->flush();
        $this->sitemap->flush();
        $this->rss->flush();
    }

    /**
     * The extension is guessed from the file's contents, never taken from the
     * name the browser sent - that half of the upload is attacker-controlled.
     */
    private function storeImage(UploadedFile $file, string $key): string
    {
        return $file->storeAs(
            'settings',
            $key.'-'.Str::random(8).'.'.($file->extension() ?: 'png'),
            config('site.media.disk')
        );
    }

    /**
     * Replacing a logo should not leave the old file on disk forever. Only
     * files this class wrote are removed - a path pointing anywhere else was
     * not ours to delete.
     */
    private function deleteStored(string $key): void
    {
        $path = $this->settings->get($key);

        if (blank($path) || ! Str::startsWith($path, 'settings/')) {
            return;
        }

        Storage::disk(config('site.media.disk'))->delete($path);
    }
}
