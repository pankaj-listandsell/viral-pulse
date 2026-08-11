<?php

namespace App\Services\Trending;

use App\Enums\TrendingTopicStatus;
use App\Models\TrendingTopic;
use App\Services\SlugService;
use Illuminate\Support\Facades\Log;

class TrendingTopicService
{
    public function __construct(
        private readonly SourceRegistry $sources,
        private readonly TopicScorer $scorer,
        private readonly CategoryGuesser $categories,
        private readonly SlugService $slugs,
    ) {}

    /**
     * Pull every enabled source. A source that fails is logged and skipped, so
     * one dead feed never costs the whole run.
     *
     * @return array<int, TopicCandidate>
     */
    public function fetchAll(): array
    {
        $candidates = [];

        foreach ($this->sources->drivers() as $driver) {
            try {
                $found = $driver->fetch();
            } catch (\Throwable $e) {
                Log::warning('Trending source threw', [
                    'source' => $driver->name(),
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            foreach ($found as $candidate) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * Store what the sources returned.
     *
     * Candidates are grouped by the normalised topic hash first, so the same
     * story from four feeds becomes one row with a corroboration score rather
     * than four near-identical rows.
     *
     * @param  array<int, TopicCandidate>  $candidates
     * @return array{created: int, updated: int, blocked: int}
     */
    public function ingest(array $candidates): array
    {
        $grouped = [];

        foreach ($candidates as $candidate) {
            $grouped[TrendingTopic::hashTopic($candidate->topic)][] = $candidate;
        }

        $created = 0;
        $updated = 0;
        $blocked = 0;

        foreach ($grouped as $hash => $group) {
            $best = $this->pickRepresentative($group);
            $distinctSources = count(array_unique(array_map(
                fn (TopicCandidate $c) => $c->source->value.'|'.($c->raw['feed'] ?? ''),
                $group
            )));

            $categoryId = $this->categories->guess($best->topic, $best->description, $best->categorySlug);
            $matchedCategory = $categoryId !== null
                && $this->categories->slugFor($categoryId) !== config('trending.fallback_category');

            $score = $this->scorer->score($best, $distinctSources, $matchedCategory);
            $isBlocked = $this->isBlocked($best);

            $existing = TrendingTopic::where('topic_hash', $hash)->first();

            if ($existing) {
                $updated += $this->refresh($existing, $best, $score) ? 1 : 0;

                continue;
            }

            TrendingTopic::create([
                'topic' => $best->topic,
                'topic_hash' => $hash,
                'slug' => $this->slugs->normalise($best->topic),
                'description' => $best->description,
                'source' => $best->source,
                'source_url' => $best->sourceUrl,
                'category_id' => $categoryId,
                'trend_score' => $score,
                'region' => config('trending.region'),
                'language' => config('trending.language', 'en'),
                'raw_payload' => [
                    'sources' => $distinctSources,
                    'search_volume' => $best->searchVolume,
                    'feeds' => array_values(array_unique(array_filter(array_map(
                        fn (TopicCandidate $c) => $c->raw['feed'] ?? $c->source->value,
                        $group
                    )))),
                ],
                'detected_at' => $best->detectedAt(),
                // Blocked topics are stored rather than dropped: the admin can
                // still see what was filtered and generate one by hand if it
                // genuinely belongs on the site.
                'status' => $isBlocked ? TrendingTopicStatus::Ignored : TrendingTopicStatus::New,
            ]);

            $created++;
            $blocked += $isBlocked ? 1 : 0;
        }

        return ['created' => $created, 'updated' => $updated, 'blocked' => $blocked];
    }

    /**
     * A topic already written about is never re-scored - only ones still
     * waiting for a decision can change.
     */
    private function refresh(TrendingTopic $topic, TopicCandidate $candidate, int $score): bool
    {
        if (! $topic->status->isAvailableForGeneration()) {
            return false;
        }

        $changed = false;

        if ($score > $topic->trend_score) {
            $topic->trend_score = $score;
            $changed = true;
        }

        if (! $topic->source_url && $candidate->sourceUrl) {
            $topic->source_url = $candidate->sourceUrl;
            $changed = true;
        }

        if (! $topic->description && $candidate->description) {
            $topic->description = $candidate->description;
            $changed = true;
        }

        if ($changed) {
            $topic->save();
        }

        return $changed;
    }

    /**
     * The longest description wins: a topic seen by four feeds should keep the
     * one with the most context for the model to work from.
     *
     * @param  array<int, TopicCandidate>  $group
     */
    private function pickRepresentative(array $group): TopicCandidate
    {
        usort($group, function (TopicCandidate $a, TopicCandidate $b): int {
            return [$b->sourceWeight, mb_strlen($b->description ?? '')]
                <=> [$a->sourceWeight, mb_strlen($a->description ?? '')];
        });

        return $group[0];
    }

    /**
     * Tragedy, crime and adult topics are filtered out of the automated path.
     * AdSense demonetises them, and an AI writing unverified copy about a death
     * or an arrest is how a site ends up publishing something defamatory.
     */
    public function isBlocked(TopicCandidate|TrendingTopic $topic): bool
    {
        $haystack = mb_strtolower(
            $topic instanceof TopicCandidate
                ? $topic->topic.' '.($topic->description ?? '')
                : $topic->topic.' '.($topic->description ?? '')
        );

        foreach ((array) config('trending.blocklist', []) as $term) {
            $term = mb_strtolower(trim((string) $term));

            if ($term === '') {
                continue;
            }

            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/u', $haystack)) {
                return true;
            }
        }

        return false;
    }
}
