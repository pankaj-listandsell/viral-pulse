<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Support\Collection;

/**
 * Looks up media rows by storage path.
 *
 * posts.featured_image stores a path rather than a foreign key, so there is no
 * relation to eager load. Without this, rendering a twelve-card grid would run
 * twelve queries just to find the thumbnail for each image. Paths are resolved
 * in one batch per request and memoised.
 */
class MediaResolver
{
    /** @var array<string, Media|null> */
    private array $cache = [];

    /**
     * @param  iterable<int, string|null>  $paths
     */
    public function preload(iterable $paths): void
    {
        $missing = collect($paths)
            ->filter()
            ->unique()
            ->reject(fn (string $path): bool => array_key_exists($path, $this->cache))
            ->values();

        if ($missing->isEmpty()) {
            return;
        }

        $found = Media::query()
            ->whereIn('path', $missing)
            ->get(['id', 'disk', 'path', 'width', 'height', 'conversions'])
            ->keyBy('path');

        foreach ($missing as $path) {
            $this->cache[$path] = $found->get($path);
        }
    }

    /**
     * @param  Collection<int, Post>|iterable<int, Post>  $posts
     */
    public function preloadForPosts(iterable $posts): void
    {
        $this->preload(collect($posts)->pluck('featured_image'));
    }

    public function find(?string $path): ?Media
    {
        if (blank($path)) {
            return null;
        }

        if (! array_key_exists($path, $this->cache)) {
            $this->preload([$path]);
        }

        return $this->cache[$path] ?? null;
    }
}
