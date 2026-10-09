<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

/**
 * Chooses the desk that bylines an automatically written article.
 *
 * The desk that covers the article's section, picked at random when more
 * than one does; any desk at random when none does, so a new section still
 * gets a byline. Null only when no desk exists at all, and the caller then
 * falls back to the admin account as before.
 */
class AuthorPicker
{
    public function forCategory(?int $categoryId): ?User
    {
        $desks = User::authors()->active()->get(['id', 'name', 'username', 'is_author', 'author_categories']);

        if ($desks->isEmpty()) {
            return null;
        }

        $slug = $categoryId ? Category::whereKey($categoryId)->value('slug') : null;

        $covering = $slug
            ? $desks->filter(fn (User $desk) => in_array($slug, (array) $desk->author_categories, true))
            : collect();

        return ($covering->isNotEmpty() ? $covering : $desks)->random();
    }
}
