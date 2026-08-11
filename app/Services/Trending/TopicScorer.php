<?php

namespace App\Services\Trending;

/**
 * A 0-100 estimate of how much traffic a topic is worth chasing.
 *
 * This is a heuristic, not a measurement. It only has to rank topics against
 * each other well enough that the next few articles are written about the ones
 * most likely to be searched for.
 */
class TopicScorer
{
    /**
     * @param  int  $corroboration  how many distinct sources reported this topic
     */
    public function score(TopicCandidate $candidate, int $corroboration = 1, bool $categoryMatched = false): int
    {
        $weights = (array) config('trending.scoring', []);

        $score = $this->freshness($candidate) * (float) ($weights['freshness_weight'] ?? 35)
            + $this->corroboration($corroboration) * (float) ($weights['corroboration_weight'] ?? 20)
            + $this->volume($candidate) * (float) ($weights['volume_weight'] ?? 15)
            + $this->headline($candidate) * (float) ($weights['headline_weight'] ?? 10)
            + ($categoryMatched ? 1.0 : 0.4) * (float) ($weights['category_weight'] ?? 10)
            + $this->sourceQuality($candidate) * 10;

        return max(0, min(100, (int) round($score)));
    }

    /**
     * Decays linearly to zero. Yesterday's trend is already covered by every
     * newsroom with a head start, so it is worth close to nothing.
     */
    private function freshness(TopicCandidate $candidate): float
    {
        $window = max(1, (int) config('trending.scoring.freshness_hours', 24));
        $ageHours = $candidate->detectedAt()->diffInMinutes(now()) / 60;

        return max(0.0, 1.0 - ($ageHours / $window));
    }

    /**
     * One source is an editor's choice; three sources is a trend.
     */
    private function corroboration(int $sources): float
    {
        return min(1.0, max(0, $sources - 1) / 2);
    }

    /**
     * Google Trends volumes span 1k to 1M+, so they are scored on a log scale -
     * linearly, everything below 100k would round to zero.
     */
    private function volume(TopicCandidate $candidate): float
    {
        if (! $candidate->searchVolume || $candidate->searchVolume < 100) {
            // No published volume is not evidence of low demand, so this falls
            // back to a neutral half rather than a zero.
            return 0.5;
        }

        return min(1.0, log10($candidate->searchVolume) / 6);
    }

    private function headline(TopicCandidate $candidate): float
    {
        $haystack = mb_strtolower($candidate->topic);
        $matches = 0;

        foreach ((array) config('trending.headline_signals', []) as $signal) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote((string) $signal, '/').'(?![\p{L}\p{N}])/u', $haystack)) {
                $matches++;
            }
        }

        // A digit in a headline usually means a date, a price or a result -
        // all things people search for by name.
        if (preg_match('/\d/', $haystack)) {
            $matches++;
        }

        return min(1.0, $matches / 2);
    }

    private function sourceQuality(TopicCandidate $candidate): float
    {
        return min(1.0, $candidate->sourceWeight / 30);
    }
}
