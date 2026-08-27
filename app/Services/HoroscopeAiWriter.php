<?php

namespace App\Services;

use App\Models\HoroscopeReading;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Exceptions\AiGenerationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Writes the horoscope readings the pages prefer over their static pool.
 *
 * This exists for one reason: twelve sign pages built from one shared pool of
 * sentences are twelve near-identical pages, and Google treats a set of those
 * as thin however good any one of them is. A reading written for Aries, about
 * Aries, using what Aries is actually like, is what makes the twelfth page
 * worth indexing as well as the first.
 *
 * Everything here is best-effort. HoroscopeService falls back to its static
 * pool whenever a row is missing, so a failed run costs a day of freshness and
 * never a blank page - which is what makes it safe to run unattended.
 */
class HoroscopeAiWriter
{
    /**
     * Signs per request.
     *
     * All twelve in one call is roughly 2 000 words of output and runs into
     * the token ceiling; one call per sign is twelve round trips for work that
     * batches perfectly well. Four is comfortably inside the limit and keeps a
     * failure to a quarter of the run.
     */
    private const BATCH = 4;

    public function __construct(
        private readonly AiProviderManager $providers,
        private readonly HoroscopeService $horoscope,
    ) {}

    /**
     * Write every sign's reading for one language and one period.
     *
     * @param  array<int, string>|null  $only  Restrict to these signs.
     * @return array<string, array<string, mixed>>  Keyed by sign.
     */
    public function write(string $locale, string $period, Carbon $date, ?array $only = null): array
    {
        $signs = $this->signsIn($locale, $only);
        $written = [];

        foreach (array_chunk($signs, self::BATCH, true) as $batch) {
            try {
                $written = [...$written, ...$this->writeBatch($locale, $period, $date, $batch)];
            } catch (AiGenerationException $e) {
                // One bad batch must not cost the other eight signs their
                // reading, so the failure is recorded and the run continues.
                Log::warning('Horoscope batch failed; those signs keep the static pool.', [
                    'locale' => $locale,
                    'period' => $period,
                    'signs' => array_keys($batch),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $written;
    }

    /**
     * Write a batch and persist it.
     *
     * @param  array<string, array<string, mixed>>  $batch
     * @return array<string, array<string, mixed>>
     */
    private function writeBatch(string $locale, string $period, Carbon $date, array $batch): array
    {
        $provider = $this->providers->resolve();

        $result = $provider->generateJson(
            $this->systemPrompt($locale, $period),
            $this->userPrompt($locale, $period, $date, $batch),
            $this->schema(array_keys($batch)),
            'horoscope_readings',
        );

        $start = HoroscopeReading::periodStart($period, $date);
        $written = [];

        foreach ($result['payload']['readings'] ?? [] as $reading) {
            $sign = $reading['sign'] ?? null;

            // The model is asked for a fixed set of signs and told to use the
            // exact keys. Anything else it returns - a name instead of a key,
            // a thirteenth sign - is dropped rather than stored under a slug
            // no page will ever ask for.
            if (! is_string($sign) || ! isset($batch[$sign])) {
                continue;
            }

            $row = $this->store($sign, $locale, $period, $start, $reading, $result['model']);

            if ($row !== null) {
                $written[$sign] = $reading;
            }
        }

        return $written;
    }

    /**
     * @param  array<string, mixed>  $reading
     */
    private function store(
        string $sign,
        string $locale,
        string $period,
        Carbon $start,
        array $reading,
        string $model,
    ): ?HoroscopeReading {
        $existing = HoroscopeReading::query()
            ->where('sign', $sign)
            ->where('locale', $locale)
            ->where('period', $period)
            ->whereDate('period_start', $start)
            ->first();

        // An editor's rewrite outranks the generator. Overwriting it on the
        // next scheduled run would quietly undo someone's work.
        if ($existing && ! $existing->isEditable()) {
            return null;
        }

        $text = fn (string $key): ?string => is_string($reading[$key] ?? null) && trim($reading[$key]) !== ''
            ? trim($reading[$key])
            : null;

        $score = fn (string $key): ?int => is_numeric($reading[$key] ?? null)
            ? max(1, min(100, (int) $reading[$key]))
            : null;

        // A reading with no overview is not a reading. Rejecting it here leaves
        // the static pool in place, which is a better page than a card with an
        // empty paragraph in it.
        if ($text('overview') === null) {
            return null;
        }

        return HoroscopeReading::updateOrCreate(
            ['sign' => $sign, 'locale' => $locale, 'period' => $period, 'period_start' => $start],
            [
                'overview' => $text('overview'),
                'love' => $text('love'),
                'career' => $text('career'),
                'health' => $text('health'),
                'money' => $text('money'),
                'mantra' => $text('mantra'),
                'lucky_number' => $score('lucky_number'),
                'lucky_color' => $text('lucky_color'),
                'lucky_time' => $text('lucky_time'),
                'lucky_direction' => $text('lucky_direction'),
                'mood' => $text('mood'),
                'score' => $score('score'),
                'scores' => array_filter([
                    'love' => $score('love_score'),
                    'career' => $score('career_score'),
                    'health' => $score('health_score'),
                    'money' => $score('money_score'),
                ], fn (?int $value): bool => $value !== null) ?: null,
                'source' => HoroscopeReading::SOURCE_AI,
                'model' => $model,
            ],
        );
    }

    /**
     * The signs to write, with the copy that describes them in this language.
     *
     * @param  array<int, string>|null  $only
     * @return array<string, array<string, mixed>>
     */
    private function signsIn(string $locale, ?array $only): array
    {
        // The service reads whatever locale the app is in, and this runs from
        // a scheduler that has no request to take one from.
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            $signs = $this->horoscope->signs();
        } finally {
            app()->setLocale($previous);
        }

        return $only === null
            ? $signs
            : array_intersect_key($signs, array_flip($only));
    }

    private function systemPrompt(string $locale, string $period): string
    {
        $language = $locale === 'hi' ? 'Hindi (Devanagari script)' : 'English';

        $register = $locale === 'hi'
            ? <<<'HI'
            Write natural, idiomatic Hindi in the register an Indian newspaper rashifal column uses.
            Do NOT translate English sentences into Hindi - write in Hindi from the start. A literal
            rendering of a phrase like "cosmic planetary alignments favour decisive action" reads as
            machine output to a rashifal reader and loses them immediately.

            Use Devanagari throughout. Numbers may be Arabic numerals. Use everyday Hindi vocabulary,
            not Sanskritised prose. Address the reader as "आप".
            HI
            : <<<'EN'
            Write clear, warm, plain English. No purple astrology filler, no "the universe has a plan
            for you". Concrete and specific reads as insight; vague and cosmic reads as filler.

            Address the reader as "you".
            EN;

        $window = match ($period) {
            HoroscopeReading::PERIOD_WEEKLY => 'a whole week, so name which part of the week each thing lands in',
            HoroscopeReading::PERIOD_MONTHLY => 'a whole month, so talk in terms of weeks and phases rather than a single day',
            default => 'a single day',
        };

        return <<<PROMPT
        You are an experienced astrologer writing daily readings for a large Indian audience.

        You write in {$language}.

        {$register}

        Each reading covers {$window}.

        Rules that matter most:

        1. Every sign gets a genuinely different reading. The whole point of this task is that a
           reader comparing two signs sees two different pieces of writing, not one paragraph with
           the names swapped. Vary the sentence shapes, not just the nouns.

        2. Write each sign in character. Use the traits, ruling planet and element you are given for
           that sign. An Aries reading should be recognisably about Aries even with the name removed.

        3. Be specific and actionable. "Review one recurring expense today" beats "financial matters
           look favourable". Prefer one concrete suggestion over three vague ones.

        4. Keep every prediction positive or neutral in outcome. Never predict illness, death,
           accident, bereavement, job loss or financial ruin. Where a reading warns, it warns about
           something the reader can act on.

        5. No medical, legal or financial advice. No claims of certainty about the future.

        6. Length: overview 2 sentences, each life area 1-2 sentences, mantra one short line.

        Return only the JSON structure you were given. No preamble, no markdown.
        PROMPT;
    }

    /**
     * @param  array<string, array<string, mixed>>  $batch
     */
    private function userPrompt(string $locale, string $period, Carbon $date, array $batch): string
    {
        $start = HoroscopeReading::periodStart($period, $date);

        $when = match ($period) {
            HoroscopeReading::PERIOD_WEEKLY => 'the week of '.$start->format('j F Y').' to '.$start->copy()->addDays(6)->format('j F Y'),
            HoroscopeReading::PERIOD_MONTHLY => 'the month of '.$start->format('F Y'),
            default => $start->format('l, j F Y'),
        };

        $profiles = collect($batch)
            ->map(function (array $sign): string {
                $strengths = implode(', ', $sign['strengths']);
                $weaknesses = implode(', ', $sign['weaknesses']);

                return <<<SIGN
                - key: {$sign['slug']}
                  name in this language: {$sign['name']}
                  dates: {$sign['dates']}
                  element: {$sign['element_label']} | quality: {$sign['quality_label']} | ruling planet: {$sign['planet_label']}
                  traits: {$sign['traits']}
                  strengths: {$strengths}
                  watch out for: {$weaknesses}
                SIGN;
            })
            ->implode("\n");

        $keys = implode(', ', array_keys($batch));
        $count = count($batch);

        // The lucky fields are asked for in the target language because they
        // are printed straight onto the page - a Hindi page showing "Crimson
        // Red" beside "गहरा लाल" elsewhere reads as half-finished.
        return <<<PROMPT
        Write the horoscope for {$when}.

        Write one reading for each of these {$count} signs, and only these:

        {$profiles}

        Use the exact `key` value in the `sign` field of each reading: {$keys}.
        Everything else you write - overview, love, career, health, money, mantra, lucky colour,
        lucky time, lucky direction, mood - must be written in the language you were told to write
        in, including the colour and direction names.

        lucky_number is 1-99. score and the four *_score fields are 60-99 and should differ from
        each other and between signs; a sign whose day is strong in love and quiet in money should
        show that in the numbers.
        PROMPT;
    }

    /**
     * Plain JSON Schema. Each provider translates it into its own dialect.
     *
     * Every property is required and additionalProperties is false, because
     * OpenAI's strict mode rejects a schema that is not exhaustive - and a
     * strict schema is what removes the reparse loop entirely.
     *
     * @param  array<int, string>  $signs  The exact keys this batch may answer with.
     * @return array<string, mixed>
     */
    private function schema(array $signs): array
    {
        $string = ['type' => 'string'];
        $integer = ['type' => 'integer'];

        $properties = [
            // Constrained to the batch, so the model cannot answer with a sign
            // name, a translated name, or a thirteenth sign - all of which it
            // will otherwise occasionally do, and all of which would be dropped
            // on arrival for nothing.
            'sign' => ['type' => 'string', 'enum' => $signs],
            'overview' => $string,
            'love' => $string,
            'career' => $string,
            'health' => $string,
            'money' => $string,
            'mantra' => $string,
            'lucky_number' => $integer,
            'lucky_color' => $string,
            'lucky_time' => $string,
            'lucky_direction' => $string,
            'mood' => $string,
            'score' => $integer,
            'love_score' => $integer,
            'career_score' => $integer,
            'health_score' => $integer,
            'money_score' => $integer,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'readings' => [
                    'type' => 'array',
                    // One per sign, no more and no fewer.
                    'minItems' => count($signs),
                    'maxItems' => count($signs),
                    'items' => [
                        'type' => 'object',
                        'properties' => $properties,
                        'required' => array_keys($properties),
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['readings'],
            'additionalProperties' => false,
        ];
    }
}
