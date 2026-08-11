<?php

namespace App\Services\Trending\Contracts;

use App\Services\Trending\TopicCandidate;

interface TrendingSourceDriver
{
    /**
     * Pull the current topics from this source.
     *
     * Implementations never throw for an unreachable or malformed feed: one bad
     * source must not stop the rest of the run. They log and return an empty
     * list instead.
     *
     * @return array<int, TopicCandidate>
     */
    public function fetch(): array;

    public function name(): string;

    public function isEnabled(): bool;
}
