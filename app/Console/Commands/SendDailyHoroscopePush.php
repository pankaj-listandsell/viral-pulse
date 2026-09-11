<?php

namespace App\Console\Commands;

use App\Services\LocaleService;
use App\Services\OneSignalService;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class SendDailyHoroscopePush extends Command
{
    protected $signature = 'push:daily-horoscope';

    protected $description = 'Send daily morning horoscope push notification to all subscribers';

    public function handle(OneSignalService $oneSignal, SettingsService $settings, LocaleService $locales): int
    {
        if (! $settings->bool('horoscope_enabled', true)) {
            $this->info('Horoscope is disabled in settings.');
            return self::SUCCESS;
        }

        $dateStr = now()->format('j F');
        $title = "✨ आज का राशिफल (Daily Horoscope) — {$dateStr}";
        $message = "आज का दिन आपके लिए कैसा रहेगा? जानिए अपनी राशि का आज का भविष्यफल!";
        $url = $locales->horoscopeUrl('hub');

        $this->info("Sending daily horoscope push: {$title}");

        $sent = $oneSignal->send($title, $message, $url);

        if ($sent) {
            $this->info('Daily horoscope push notification dispatched successfully.');
        } else {
            $this->warn('Could not dispatch push notification (check App ID / API key).');
        }

        return self::SUCCESS;
    }
}
