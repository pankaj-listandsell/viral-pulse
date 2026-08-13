<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A comma separated list of 24-hour times, as typed by a person.
 *
 * Deliberately forgiving about spacing and about "9:00" for "09:00", because
 * those are how people actually write times - and strict about anything that
 * is not a time, because a typo here silently stops the site publishing.
 */
class ValidTimeList implements ValidationRule
{
    public const MAX_TIMES = 24;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        $times = self::parse($value);
        $entries = array_filter(array_map('trim', explode(',', $value)), fn ($entry) => $entry !== '');

        if (count($times) !== count($entries)) {
            $fail('Every entry must be a time like 08:00 or 17:30, separated by commas.');

            return;
        }

        if (count($times) > self::MAX_TIMES) {
            $fail('That is more than '.self::MAX_TIMES.' times; there are only 24 hours in a day.');
        }
    }

    /**
     * The times in a list, normalised to HH:MM, sorted and deduplicated.
     *
     * @return array<int, string>
     */
    public static function parse(?string $value): array
    {
        $times = [];

        foreach (explode(',', (string) $value) as $entry) {
            $entry = trim($entry);

            if ($entry === '' || ! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $entry, $match)) {
                continue;
            }

            $times[] = sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
        }

        $times = array_values(array_unique($times));
        sort($times);

        return $times;
    }
}
