<?php

namespace App\Console\Commands;

use App\Models\HoroscopeReading;
use App\Services\AI\AiProviderManager;
use App\Services\HoroscopeAiWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Writes the readings the horoscope pages prefer over their static pool.
 *
 * Scheduled across a window rather than pinned to one minute, for the same
 * reason the daily article is: shared hosting throttles cron, and a task fixed
 * to 04:00 is simply skipped on the mornings nothing runs at 04:00. The
 * already-written check below is what makes running it repeatedly harmless.
 */
class GenerateHoroscopeReadings extends Command
{
    protected $signature = 'content:generate-horoscope-readings
                            {--locale=* : Languages to write. Defaults to every locale in config/horoscope.php}
                            {--period=daily : daily, weekly or monthly}
                            {--date= : The date to write for, format YYYY-MM-DD}
                            {--sign=* : Only these signs, by their English key}
                            {--force : Rewrite readings that already exist}';

    protected $description = 'Write the daily, weekly or monthly horoscope readings for every sign, in every language';

    public function handle(HoroscopeAiWriter $writer, AiProviderManager $providers): int
    {
        if (! config('horoscope.ai.enabled', true)) {
            $this->warn('Horoscope AI readings are disabled. Set HOROSCOPE_AI_READINGS=true to enable them.');

            return self::SUCCESS;
        }

        if (! $providers->hasAnyProvider()) {
            $this->error('No AI provider is configured. Set GEMINI_API_KEY or OPENAI_API_KEY.');

            return self::FAILURE;
        }

        $period = (string) $this->option('period');

        if (! in_array($period, config('horoscope.ai.periods', []), true)) {
            $this->error("Unknown period '{$period}'. Expected one of: ".implode(', ', config('horoscope.ai.periods', [])));

            return self::FAILURE;
        }

        $date = $this->option('date') ? Carbon::parse((string) $this->option('date')) : Carbon::today();
        $start = HoroscopeReading::periodStart($period, $date);

        $locales = $this->option('locale') ?: config('horoscope.ai.locales', []);
        $signs = $this->option('sign') ?: null;

        $this->info("Writing {$period} readings for ".$start->toDateString().'...');

        $failed = false;

        foreach ($locales as $locale) {
            $existing = HoroscopeReading::query()
                ->where('locale', $locale)
                ->where('period', $period)
                ->whereDate('period_start', $start)
                ->count();

            $expected = $signs === null ? 12 : count($signs);

            // The first run of the period writes; the rest see the rows and
            // stop. A partial run - a batch failed last time - is finished off
            // rather than skipped, so a bad morning self-heals on the next tick
            // instead of waiting for someone to notice.
            if ($existing >= $expected && ! $this->option('force')) {
                $this->line("  {$locale}: already written ({$existing} signs).");

                continue;
            }

            $written = $writer->write($locale, $period, $date, $signs);
            $count = count($written);

            if ($count === 0) {
                // Not a hard failure: the pages still render from the static
                // pool. It is reported so a monitored run surfaces it.
                $this->warn("  {$locale}: nothing written. The pages fall back to the static pool.");
                $failed = true;

                continue;
            }

            $this->line("  {$locale}: wrote {$count} of {$expected} signs.");

            if ($count < $expected) {
                $this->warn("  {$locale}: {$expected} expected, {$count} written. The rest keep the static pool until the next run.");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
