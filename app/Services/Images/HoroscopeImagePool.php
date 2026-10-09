<?php

namespace App\Services\Images;

use App\Enums\SettingType;
use App\Models\User;
use App\Services\MediaService;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The fixed pictures for the morning horoscope article.
 *
 * The article is the same subject every day, so drawing a fresh picture each
 * morning spent the free image allowance on something no reader compares
 * day to day. A few chosen pictures ship with the code in
 * resources/images/daily-horoscope, and each morning's article takes one at
 * random.
 *
 * Each file is copied into the media library the first time it is needed, so
 * it gets the same WebP sizes as any upload, and the stored path is
 * remembered so the copy happens once rather than every morning.
 */
class HoroscopeImagePool
{
    private const SETTING = 'horoscope_daily_images';

    public function __construct(
        private readonly MediaService $media,
        private readonly SettingsService $settings,
    ) {}

    /**
     * A media path for today's article, or null when no picture is available
     * and the usual image chain should run instead.
     */
    public function pick(?User $author = null): ?string
    {
        $paths = $this->paths($author);

        return $paths === [] ? null : $paths[array_rand($paths)];
    }

    /**
     * @return array<int, string>
     */
    public function paths(?User $author = null): array
    {
        $stored = (array) ($this->settings->get(self::SETTING) ?: []);
        $disk = Storage::disk(config('site.media.disk'));
        $changed = false;

        foreach ($this->bundled() as $name => $file) {
            if (isset($stored[$name]) && $disk->exists($stored[$name])) {
                continue;
            }

            try {
                $media = $this->media->store(
                    new UploadedFile($file, $name, mime_content_type($file) ?: 'image/jpeg', null, true),
                    $author,
                    'horoscope',
                );

                $media->forceFill([
                    'caption' => AiIllustrationGenerator::CREDIT,
                    'alt_text' => 'Zodiac wheel with the twelve signs in a starry night sky',
                ])->save();

                $stored[$name] = $media->path;
                $changed = true;
            } catch (\Throwable $e) {
                Log::warning('Horoscope picture could not be added to the media library', [
                    'file' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Pictures removed from the folder stop being offered.
        $stored = array_intersect_key($stored, $this->bundled());

        if ($changed) {
            $this->settings->set(self::SETTING, $stored, SettingType::Json, 'media');
        }

        return array_values($stored);
    }

    /**
     * @return array<string, string> file name => absolute path
     */
    private function bundled(): array
    {
        // No GLOB_BRACE: it is missing from some PHP builds.
        $files = glob(resource_path('images/daily-horoscope/*')) ?: [];

        return collect($files)
            ->filter(fn (string $file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->mapWithKeys(fn (string $file) => [basename($file) => $file])
            ->all();
    }
}
