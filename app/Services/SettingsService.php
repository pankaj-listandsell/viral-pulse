<?php

namespace App\Services;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    private const PUBLIC_CACHE_KEY = 'settings.public';

    /**
     * Every setting, keyed by name, with values already cast to their declared
     * type. Cached forever and flushed whenever a setting is written.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::all()
                ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->typed_value])
                ->all();
        });
    }

    /**
     * Only settings flagged is_public. This is the sole set that may be shared
     * with the frontend - anything else stays server-side.
     *
     * @return array<string, mixed>
     */
    public function public(): array
    {
        return Cache::rememberForever(self::PUBLIC_CACHE_KEY, function (): array {
            return Setting::query()
                ->public()
                ->get()
                ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->typed_value])
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) ($this->all()[$key] ?? $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) ($this->all()[$key] ?? $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $setting = Setting::firstWhere('key', $key);

            if (! $setting) {
                continue;
            }

            $setting->update(['value' => $setting->type->serialize($value)]);
        }

        $this->flush();
    }

    public function set(string $key, mixed $value, SettingType $type = SettingType::String, string $group = 'general'): void
    {
        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->type = $setting->exists ? $setting->type : $type;
        $setting->group = $setting->exists ? $setting->group : $group;
        $setting->value = $setting->type->serialize($value);
        $setting->save();

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::PUBLIC_CACHE_KEY);
    }
}
