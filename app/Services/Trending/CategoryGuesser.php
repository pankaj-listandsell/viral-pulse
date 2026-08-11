<?php

namespace App\Services\Trending;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Routes a topic to a category by keyword. Deliberately simple: the alternative
 * is a second AI call per topic, which costs money on every feed pull to decide
 * something a word list gets right most of the time.
 */
class CategoryGuesser
{
    /** @var array<string, int>|null */
    private ?array $slugMap = null;

    public function guess(string $topic, ?string $description = null, ?string $preferredSlug = null): ?int
    {
        // A feed pinned to a category in config always wins - it is a human
        // decision and better than any keyword match.
        if ($preferredSlug && $id = $this->idForSlug($preferredSlug)) {
            return $id;
        }

        // The headline first, and only then the description. A feed summary is
        // a paragraph of arbitrary prose - matched together with the title it
        // reliably finds some stray keyword, which is how "Google Pixel 11"
        // ended up filed under Sports.
        foreach ([$topic, $description] as $haystack) {
            if (blank($haystack)) {
                continue;
            }

            $id = $this->match(mb_strtolower($haystack));

            if ($id !== null) {
                return $id;
            }
        }

        return $this->idForSlug((string) config('trending.fallback_category', 'trending'));
    }

    /**
     * The category with the most keyword hits wins, rather than whichever one
     * happens to be listed first. A headline that mentions both a wedding and
     * an election should not be decided by config ordering.
     */
    private function match(string $haystack): ?int
    {
        $best = null;
        $bestHits = 0;

        foreach ((array) config('trending.category_keywords', []) as $slug => $keywords) {
            $hits = 0;

            foreach ((array) $keywords as $keyword) {
                if ($this->contains($haystack, (string) $keyword)) {
                    $hits++;
                }
            }

            if ($hits > $bestHits && ($id = $this->idForSlug((string) $slug))) {
                $best = $id;
                $bestHits = $hits;
            }
        }

        return $best;
    }

    public function slugFor(?int $categoryId): ?string
    {
        if (! $categoryId) {
            return null;
        }

        return array_search($categoryId, $this->map(), true) ?: null;
    }

    /**
     * Word-boundary match, so "ai" does not fire on "said" and "ram" does not
     * fire on "program".
     */
    private function contains(string $haystack, string $keyword): bool
    {
        $keyword = mb_strtolower(trim($keyword));

        if ($keyword === '') {
            return false;
        }

        return (bool) preg_match('/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}])/u', $haystack);
    }

    private function idForSlug(string $slug): ?int
    {
        return $this->map()[$slug] ?? null;
    }

    /**
     * @return array<string, int>
     */
    private function map(): array
    {
        return $this->slugMap ??= Cache::remember(
            'trending:category-map',
            now()->addMinutes(30),
            fn () => Category::query()->pluck('id', 'slug')->all()
        );
    }
}
