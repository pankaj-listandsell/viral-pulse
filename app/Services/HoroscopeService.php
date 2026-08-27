<?php

namespace App\Services;

use App\Models\HoroscopeReading;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Everything the horoscope pages know about the zodiac.
 *
 * The words a reader sees live in lang/{locale}/horoscope.php and
 * lang/{locale}/zodiac.php; what stays here is the structure those words hang
 * on - which sign is which element, how the elements pair, and how a reading
 * is chosen for a given day.
 *
 * A reading is looked for in horoscope_readings first, where the AI writer
 * puts one reading per sign per language per day. When there is none - the
 * overnight run has not landed, or failed - the page falls back to a static
 * pool seeded from the sign and the date. That fallback is what makes this
 * page safe to schedule: a bad night costs freshness, never the page.
 */
class HoroscopeService
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $signCache = [];

    /** @var array<string, array<string, HoroscopeReading>> */
    private array $readingCache = [];

    /**
     * Set once the readings table has proved unreachable, so a site running
     * before the migration - or during one - degrades to the static pool
     * instead of 500ing twelve times per page.
     */
    private bool $readingsUnavailable = false;

    /**
     * The structural half of each sign: the parts that are an identity rather
     * than a word, and so are the same in every language.
     *
     * `element` and `quality` stay in English here because they are lookup
     * keys - the compatibility rules, the element filter and the badge colours
     * all match on them. Their translated forms arrive as `element_label` and
     * `quality_label`, which is what a template should print.
     *
     * @return array<string, array<string, mixed>>
     */
    private function structure(): array
    {
        return [
            'aries' => [
                'slug' => 'aries', 'symbol' => '♈', 'icon' => 'flame',
                'range' => ['03-21', '04-19'], 'element' => 'Fire', 'quality' => 'Cardinal',
                'planet' => 'Mars', 'gemstone' => 'Red Coral', 'lucky_day' => 'Tuesday',
                'best_matches' => ['leo', 'sagittarius', 'gemini'],
                'color' => '#ef4444', 'image' => '/images/zodiac/aries.webp',
            ],
            'taurus' => [
                'slug' => 'taurus', 'symbol' => '♉', 'icon' => 'shield',
                'range' => ['04-20', '05-20'], 'element' => 'Earth', 'quality' => 'Fixed',
                'planet' => 'Venus', 'gemstone' => 'Diamond', 'lucky_day' => 'Friday',
                'best_matches' => ['virgo', 'capricorn', 'cancer'],
                'color' => '#10b981', 'image' => '/images/zodiac/taurus.webp',
            ],
            'gemini' => [
                'slug' => 'gemini', 'symbol' => '♊', 'icon' => 'sparkles',
                'range' => ['05-21', '06-20'], 'element' => 'Air', 'quality' => 'Mutable',
                'planet' => 'Mercury', 'gemstone' => 'Emerald', 'lucky_day' => 'Wednesday',
                'best_matches' => ['libra', 'aquarius', 'aries'],
                'color' => '#f59e0b', 'image' => '/images/zodiac/gemini.webp',
            ],
            'cancer' => [
                'slug' => 'cancer', 'symbol' => '♋', 'icon' => 'heart',
                'range' => ['06-21', '07-22'], 'element' => 'Water', 'quality' => 'Cardinal',
                'planet' => 'Moon', 'gemstone' => 'Pearl', 'lucky_day' => 'Monday',
                'best_matches' => ['scorpio', 'pisces', 'taurus'],
                'color' => '#06b6d4', 'image' => '/images/zodiac/cancer.webp',
            ],
            'leo' => [
                'slug' => 'leo', 'symbol' => '♌', 'icon' => 'sun',
                'range' => ['07-23', '08-22'], 'element' => 'Fire', 'quality' => 'Fixed',
                'planet' => 'Sun', 'gemstone' => 'Ruby', 'lucky_day' => 'Sunday',
                'best_matches' => ['aries', 'sagittarius', 'libra'],
                'color' => '#ea580c', 'image' => '/images/zodiac/leo.webp',
            ],
            'virgo' => [
                'slug' => 'virgo', 'symbol' => '♍', 'icon' => 'check-circle',
                'range' => ['08-23', '09-22'], 'element' => 'Earth', 'quality' => 'Mutable',
                'planet' => 'Mercury', 'gemstone' => 'Emerald', 'lucky_day' => 'Wednesday',
                'best_matches' => ['taurus', 'capricorn', 'cancer'],
                'color' => '#84cc16', 'image' => '/images/zodiac/virgo.webp',
            ],
            'libra' => [
                'slug' => 'libra', 'symbol' => '♎', 'icon' => 'scale',
                'range' => ['09-23', '10-22'], 'element' => 'Air', 'quality' => 'Cardinal',
                'planet' => 'Venus', 'gemstone' => 'Opal', 'lucky_day' => 'Friday',
                'best_matches' => ['gemini', 'aquarius', 'leo'],
                'color' => '#3b82f6', 'image' => '/images/zodiac/libra.webp',
            ],
            'scorpio' => [
                'slug' => 'scorpio', 'symbol' => '♏', 'icon' => 'zap',
                'range' => ['10-23', '11-21'], 'element' => 'Water', 'quality' => 'Fixed',
                'planet' => 'Pluto & Mars', 'gemstone' => 'Topaz', 'lucky_day' => 'Tuesday',
                'best_matches' => ['cancer', 'pisces', 'virgo'],
                'color' => '#8b5cf6', 'image' => '/images/zodiac/scorpio.webp',
            ],
            'sagittarius' => [
                'slug' => 'sagittarius', 'symbol' => '♐', 'icon' => 'compass',
                'range' => ['11-22', '12-21'], 'element' => 'Fire', 'quality' => 'Mutable',
                'planet' => 'Jupiter', 'gemstone' => 'Yellow Sapphire', 'lucky_day' => 'Thursday',
                'best_matches' => ['aries', 'leo', 'aquarius'],
                'color' => '#d946ef', 'image' => '/images/zodiac/sagittarius.webp',
            ],
            'capricorn' => [
                'slug' => 'capricorn', 'symbol' => '♑', 'icon' => 'mountain',
                'range' => ['12-22', '01-19'], 'element' => 'Earth', 'quality' => 'Cardinal',
                'planet' => 'Saturn', 'gemstone' => 'Blue Sapphire', 'lucky_day' => 'Saturday',
                'best_matches' => ['taurus', 'virgo', 'pisces'],
                'color' => '#64748b', 'image' => '/images/zodiac/capricorn.webp',
            ],
            'aquarius' => [
                'slug' => 'aquarius', 'symbol' => '♒', 'icon' => 'wind',
                'range' => ['01-20', '02-18'], 'element' => 'Air', 'quality' => 'Fixed',
                'planet' => 'Uranus', 'gemstone' => 'Amethyst', 'lucky_day' => 'Saturday',
                'best_matches' => ['gemini', 'libra', 'sagittarius'],
                'color' => '#0284c7', 'image' => '/images/zodiac/aquarius.webp',
            ],
            'pisces' => [
                'slug' => 'pisces', 'symbol' => '♓', 'icon' => 'droplet',
                'range' => ['02-19', '03-20'], 'element' => 'Water', 'quality' => 'Mutable',
                'planet' => 'Neptune', 'gemstone' => 'Aquamarine', 'lucky_day' => 'Thursday',
                'best_matches' => ['cancer', 'scorpio', 'capricorn'],
                'color' => '#ec4899', 'image' => '/images/zodiac/pisces.webp',
            ],
        ];
    }

    /**
     * All 12 signs, structure merged with the current language's copy.
     *
     * @return array<string, array<string, mixed>>
     */
    public function signs(): array
    {
        $locale = app()->getLocale();

        if (isset($this->signCache[$locale])) {
            return $this->signCache[$locale];
        }

        $signs = [];

        foreach ($this->structure() as $slug => $sign) {
            $copy = (array) __("horoscope.signs.{$slug}");

            $signs[$slug] = [
                ...$sign,
                ...$copy,
                // The translated form of each lookup key, printed by templates.
                // Falling back to the key itself means an untranslated term
                // shows as "Mercury" rather than as a raw translation string.
                'element_label' => $this->term('elements', $sign['element'], 'name'),
                'quality_label' => $this->term('qualities', $sign['quality']),
                'planet_label' => $this->term('planets', $sign['planet']),
                'gemstone_label' => $this->term('gemstones', $sign['gemstone']),
                'lucky_day_label' => $this->term('days', $sign['lucky_day']),
            ];
        }

        return $this->signCache[$locale] = $signs;
    }

    /**
     * One sign, or null when the slug is not a sign at all.
     *
     * @return array<string, mixed>|null
     */
    public function sign(string $slug): ?array
    {
        return $this->signs()[$slug] ?? null;
    }

    /**
     * A single translated term, falling back to the English key it was looked
     * up by rather than to a visible translation path.
     */
    private function term(string $group, string $key, ?string $field = null): string
    {
        $path = "horoscope.{$group}.{$key}".($field ? ".{$field}" : '');
        $value = __($path);

        return is_string($value) && $value !== $path ? $value : $key;
    }

    /**
     * The four elements, used by the filter pills and the explainer section.
     *
     * The array stays keyed by the English element name because that key is
     * what a sign's `element` field matches on.
     *
     * @return array<string, array<string, mixed>>
     */
    public function elements(): array
    {
        $presentation = [
            'Fire' => [
                'icon' => '🔥',
                'signs' => ['aries', 'leo', 'sagittarius'],
                'accent' => 'text-orange-600 dark:text-orange-400',
                'ring' => 'border-orange-500/30',
                'tint' => 'bg-orange-50 dark:bg-orange-500/10',
            ],
            'Earth' => [
                'icon' => '🌿',
                'signs' => ['taurus', 'virgo', 'capricorn'],
                'accent' => 'text-emerald-600 dark:text-emerald-400',
                'ring' => 'border-emerald-500/30',
                'tint' => 'bg-emerald-50 dark:bg-emerald-500/10',
            ],
            'Air' => [
                'icon' => '💨',
                'signs' => ['gemini', 'libra', 'aquarius'],
                'accent' => 'text-sky-600 dark:text-sky-400',
                'ring' => 'border-sky-500/30',
                'tint' => 'bg-sky-50 dark:bg-sky-500/10',
            ],
            'Water' => [
                'icon' => '💧',
                'signs' => ['cancer', 'scorpio', 'pisces'],
                'accent' => 'text-indigo-600 dark:text-indigo-400',
                'ring' => 'border-indigo-500/30',
                'tint' => 'bg-indigo-50 dark:bg-indigo-500/10',
            ],
        ];

        $elements = [];

        foreach ($presentation as $key => $meta) {
            $elements[$key] = [
                ...$meta,
                'key' => $key,
                ...(array) __("horoscope.elements.{$key}"),
            ];
        }

        return $elements;
    }

    /*
    |--------------------------------------------------------------------------
    | Readings
    |--------------------------------------------------------------------------
    */

    /**
     * Today's reading for one sign.
     *
     * @return array<string, mixed>
     */
    public function daily(string $slug, ?Carbon $date = null): array
    {
        return $this->reading(HoroscopeReading::PERIOD_DAILY, $slug, $date);
    }

    /**
     * This week's reading, keyed to the Monday of the week the date falls in.
     *
     * @return array<string, mixed>
     */
    public function weekly(string $slug, ?Carbon $date = null): array
    {
        return $this->reading(HoroscopeReading::PERIOD_WEEKLY, $slug, $date);
    }

    /**
     * This month's reading, keyed to the 1st.
     *
     * @return array<string, mixed>
     */
    public function monthly(string $slug, ?Carbon $date = null): array
    {
        return $this->reading(HoroscopeReading::PERIOD_MONTHLY, $slug, $date);
    }

    /**
     * Today's reading for every sign, in one query rather than twelve.
     *
     * The hub page prints all twelve, and doing that a sign at a time was
     * twelve round trips for a page that is otherwise almost free to render.
     *
     * @return array<string, array<string, mixed>>
     */
    public function dailyForAll(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $this->loadReadings(HoroscopeReading::PERIOD_DAILY, $date);

        $all = [];

        foreach (array_keys($this->structure()) as $slug) {
            $all[$slug] = $this->daily($slug, $date);
        }

        return $all;
    }

    /**
     * @return array<string, mixed>
     */
    private function reading(string $period, string $slug, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $sign = $this->sign($slug) ?? $this->signs()['aries'];
        $start = HoroscopeReading::periodStart($period, $date);

        $written = $this->written($period, $sign['slug'], $date);
        $fallback = $this->pooledReading($period, $sign['slug'], $start);

        // A written reading may be partial - the model returned four fields
        // out of five, or an editor filled in only the overview - so it is
        // merged over the pool rather than used instead of it. Every key the
        // template reads is then guaranteed to hold something.
        $values = $written === null
            ? $fallback
            : [...$fallback, ...array_filter([
                'overview' => $written->overview,
                'love' => $written->love,
                'career' => $written->career,
                'health' => $written->health,
                'money' => $written->money,
                'mantra' => $written->mantra,
                'lucky_number' => $written->lucky_number,
                'lucky_color' => $written->lucky_color,
                'lucky_time' => $written->lucky_time,
                'lucky_direction' => $written->lucky_direction,
                'mood' => $written->mood,
                'score' => $written->score,
                'scores' => $written->scores,
            ], fn ($value) => $value !== null && $value !== '')];

        return [
            ...$values,
            'sign' => $sign,
            'period' => $period,
            'date' => $start->translatedFormat('j F Y'),
            'date_iso' => $start->toDateString(),
            'period_end_iso' => $this->periodEnd($period, $start)->toDateString(),
            // Lets a template say "written for you today" rather than implying
            // an astrologer wrote the fallback by hand.
            'is_written' => $written !== null,
        ];
    }

    private function periodEnd(string $period, Carbon $start): Carbon
    {
        return match ($period) {
            HoroscopeReading::PERIOD_WEEKLY => $start->copy()->addDays(6),
            HoroscopeReading::PERIOD_MONTHLY => $start->copy()->endOfMonth(),
            default => $start->copy(),
        };
    }

    /**
     * The written reading for one sign, if the generator has produced one.
     */
    private function written(string $period, string $sign, Carbon $date): ?HoroscopeReading
    {
        if (! config('horoscope.ai.enabled', true) || $this->readingsUnavailable) {
            return null;
        }

        $this->loadReadings($period, $date);

        $key = $this->readingKey($period, $date);

        return $this->readingCache[$key][$sign] ?? null;
    }

    private function readingKey(string $period, Carbon $date): string
    {
        return $period.':'.app()->getLocale().':'.HoroscopeReading::periodStart($period, $date)->toDateString();
    }

    /**
     * Fetch every sign's reading for one period in a single query, once per
     * request. Called before the per-sign lookups so the hub page costs one
     * query rather than twelve.
     */
    private function loadReadings(string $period, Carbon $date): void
    {
        $key = $this->readingKey($period, $date);

        if (isset($this->readingCache[$key]) || $this->readingsUnavailable) {
            return;
        }

        if (! config('horoscope.ai.enabled', true)) {
            return;
        }

        try {
            $this->readingCache[$key] = HoroscopeReading::query()
                ->where('locale', app()->getLocale())
                ->where('period', $period)
                ->whereDate('period_start', HoroscopeReading::periodStart($period, $date))
                ->get()
                ->keyBy('sign')
                ->all();
        } catch (QueryException $e) {
            // Before the migration has run, or while the database is briefly
            // unreachable. The page is still complete from the static pool, so
            // this is logged rather than raised.
            $this->readingsUnavailable = true;

            Log::warning('Horoscope readings unavailable, falling back to the static pool.', [
                'period' => $period,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * A complete reading built from the static pools.
     *
     * Every value is drawn from a seed built out of the sign, the period and
     * the date, so one reader sees the same reading all day, two readers on
     * the same day see the same page, and the response stays cacheable.
     *
     * The locale is deliberately absent from the seed. The pools are parallel
     * across languages, so the same seed picks entry 3 in both - which is what
     * makes the Hindi page a translation of the English one rather than a
     * different forecast for the same morning.
     *
     * @return array<string, mixed>
     */
    private function pooledReading(string $period, string $slug, Carbon $start): array
    {
        $pool = fn (string $name): array => (array) __("horoscope.pools.{$name}");

        $overviews = $pool('overviews');
        $love = $pool('love');
        $career = $pool('career');
        $health = $pool('health');
        $money = $pool('money');
        $mantras = $pool('mantras');
        $colors = $pool('colors');
        $moods = $pool('moods');
        $directions = $pool('directions');

        $seed = crc32($slug.$period.$start->toDateString());
        mt_srand($seed);

        $pick = fn (array $items) => $items[mt_rand(0, max(0, count($items) - 1))] ?? '';

        $luckyNumber = mt_rand(1, 99);
        $luckyColor = $pick($colors);
        $overview = $pick($overviews);
        $loveText = $pick($love);
        $careerText = $pick($career);
        $healthText = $pick($health);
        $moneyText = $pick($money);
        $mantra = $pick($mantras);
        $mood = $pick($moods);
        $direction = $pick($directions);
        $score = mt_rand(82, 98);
        $scores = [
            'love' => mt_rand(68, 97),
            'career' => mt_rand(68, 97),
            'health' => mt_rand(68, 97),
            'money' => mt_rand(68, 97),
        ];

        // Drawn from the seeded sequence, not after it. A value generated once
        // the seed has been reset would change on every request, and the
        // reading would stop matching itself from one refresh to the next.
        $luckyTime = sprintf('%d:00 AM – %d:00 PM', mt_rand(8, 11), mt_rand(1, 4));

        mt_srand();

        return [
            'overview' => $overview,
            'love' => $loveText,
            'career' => $careerText,
            'health' => $healthText,
            'money' => $moneyText,
            'mantra' => $mantra,
            'lucky_number' => $luckyNumber,
            'lucky_color' => $luckyColor,
            'lucky_time' => $luckyTime,
            'lucky_direction' => $direction,
            'mood' => $mood,
            'score' => $score,
            'scores' => $scores,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FAQs
    |--------------------------------------------------------------------------
    */

    /**
     * Questions readers actually search for, rendered on the hub page and
     * mirrored into FAQPage structured data.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function faqs(): array
    {
        return (array) __('horoscope.faqs');
    }

    /**
     * The same idea for one sign's own page.
     *
     * The templates are shared but every answer is filled with that sign's own
     * facts - its dates, ruling planet, stone, day and best matches - so the
     * twelve pages answer twelve genuinely different questions rather than
     * repeating one block with a name swapped.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function signFaqs(string $slug): array
    {
        $sign = $this->sign($slug);

        if ($sign === null) {
            return [];
        }

        $signs = $this->signs();
        $matches = collect($sign['best_matches'])
            ->map(fn (string $match): string => $signs[$match]['name'] ?? $match)
            ->implode(', ');

        $replacements = [
            ':name' => $sign['name'],
            ':other' => $sign['vedic'],
            ':dates' => $sign['dates'],
            ':element' => $sign['element_label'],
            ':quality' => $sign['quality_label'],
            ':planet' => $sign['planet_label'],
            ':gemstone' => $sign['gemstone_label'],
            ':day' => $sign['lucky_day_label'],
            ':symbol' => $sign['symbol_name'],
            ':matches' => $matches,
            ':traits' => $sign['traits'],
            ':letters' => $sign['letters'] ?? '',
        ];

        return collect((array) __('horoscope.sign_faqs'))
            ->map(fn (array $faq): array => [
                'question' => strtr($faq['question'], $replacements),
                'answer' => strtr($faq['answer'], $replacements),
            ])
            ->all();
    }

    /**
     * FAQ block for the compatibility page.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function compatibilityFaqs(): array
    {
        return (array) __('zodiac.faqs');
    }

    /*
    |--------------------------------------------------------------------------
    | Compatibility
    |--------------------------------------------------------------------------
    */

    /**
     * The four kinds of pairing, and the numbers each one scores.
     *
     * @return array<string, array<string, mixed>>
     */
    public function compatibilityTypes(): array
    {
        return (array) __('zodiac.types');
    }

    /**
     * Which of the four pairing types two signs fall into.
     */
    public function compatibilityType(string $element1, string $element2, bool $sameSign): string
    {
        if ($sameSign) {
            return 'twin';
        }

        if ($element1 === $element2) {
            return 'element';
        }

        $complementary = ($element1 === 'Fire' && $element2 === 'Air') || ($element1 === 'Air' && $element2 === 'Fire')
            || ($element1 === 'Earth' && $element2 === 'Water') || ($element1 === 'Water' && $element2 === 'Earth');

        return $complementary ? 'complementary' : 'contrast';
    }

    /**
     * Compute compatibility between two signs.
     *
     * @return array<string, mixed>
     */
    public function compatibility(string $slug1, string $slug2): array
    {
        $signs = $this->signs();
        $s1 = $signs[$slug1] ?? $signs['aries'];
        $s2 = $signs[$slug2] ?? $signs['leo'];

        $type = $this->compatibilityType($s1['element'], $s2['element'], $s1['slug'] === $s2['slug']);
        $rule = $this->compatibilityTypes()[$type];

        // The element placeholders take the translated label, not the lookup
        // key, so a Hindi sentence reads "अग्नि तत्व" rather than "Fire तत्व".
        $fill = fn (string $text): string => strtr($text, [
            '{s1}' => $s1['name'],
            '{s2}' => $s2['name'],
            '{e1}' => $s1['element_label'],
            '{e2}' => $s2['element_label'],
        ]);

        return [
            'sign1' => $s1,
            'sign2' => $s2,
            'type' => $type,
            'score' => $rule['score'],
            'title' => $rule['title'],
            'summary' => $fill($rule['summary']),
            'detail' => $fill($rule['detail']),
            'strengths' => $rule['strengths'],
            'challenges' => $rule['challenges'],
            'advice' => $rule['advice'],
            'scores' => $rule['scores'],
            // Kept flat as well: the older callers and the share text read these.
            'love' => $rule['scores']['love'],
            'friendship' => $rule['scores']['friendship'],
        ];
    }

    /**
     * Every other sign ranked against this one, best match first.
     *
     * This is what turns a sign page from a daily reading into a page worth
     * linking to: eleven internal links out to the compatibility pairs, each
     * one labelled with the score it leads to.
     *
     * @return array<int, array<string, mixed>>
     */
    public function matchesFor(string $slug): array
    {
        $signs = $this->signs();
        $sign = $signs[$slug] ?? null;

        if ($sign === null) {
            return [];
        }

        $types = $this->compatibilityTypes();

        return collect($signs)
            ->reject(fn (array $other): bool => $other['slug'] === $slug)
            ->map(function (array $other) use ($sign, $types): array {
                $type = $this->compatibilityType($sign['element'], $other['element'], false);

                return [
                    'sign' => $other,
                    'type' => $type,
                    'title' => $types[$type]['title'],
                    'score' => $types[$type]['score'],
                    'is_best' => in_array($other['slug'], $sign['best_matches'], true),
                ];
            })
            // Sorted by score, then by whether the sign is one of the three the
            // reference copy calls a best match, so equal scores break in the
            // order a reader would expect rather than alphabetically.
            ->sortByDesc(fn (array $match): string => sprintf('%03d%d', $match['score'], $match['is_best'] ? 1 : 0))
            ->values()
            ->all();
    }

    /**
     * Score for every pair of signs, for the compatibility table.
     *
     * @return array<string, array<string, int>>
     */
    public function compatibilityMatrix(): array
    {
        $types = $this->compatibilityTypes();
        $signs = $this->signs();
        $matrix = [];

        foreach ($signs as $slug1 => $first) {
            foreach ($signs as $slug2 => $second) {
                $type = $this->compatibilityType($first['element'], $second['element'], $slug1 === $slug2);
                $matrix[$slug1][$slug2] = $types[$type]['score'];
            }
        }

        return $matrix;
    }

    /**
     * The sign a birth date falls in.
     *
     * Capricorn is the one range that wraps the year end, so it is matched by
     * either half rather than by a single from/to comparison.
     */
    public function signForBirthday(Carbon $date): ?array
    {
        $needle = $date->format('m-d');

        foreach ($this->signs() as $sign) {
            [$from, $to] = $sign['range'];

            $inRange = $from <= $to
                ? ($needle >= $from && $needle <= $to)
                : ($needle >= $from || $needle <= $to);

            if ($inRange) {
                return $sign;
            }
        }

        return null;
    }
}
