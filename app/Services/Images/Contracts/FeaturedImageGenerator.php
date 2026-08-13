<?php

namespace App\Services\Images\Contracts;

use App\Models\Media;
use App\Models\Post;

interface FeaturedImageGenerator
{
    /**
     * Produce a featured image for a post.
     *
     * Returns null when no image could be made. Implementations never throw:
     * a missing picture is a cosmetic problem, and it must not take an article
     * down with it.
     */
    public function generate(Post $post): ?Media;

    public function name(): string;
}
