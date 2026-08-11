<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Build a slug that is unique for the given table, appending -2, -3 and so
     * on rather than a random suffix so URLs stay readable.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function unique(string $modelClass, string $source, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = $this->normalise($source);
        $slug = $base;
        $suffix = 1;

        while ($this->exists($modelClass, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    /**
     * Str::slug strips non-Latin scripts entirely, which would turn a Hindi
     * title into an empty slug. Fall back to a transliterated stub in that case
     * so the URL is still usable.
     */
    public function normalise(string $source): string
    {
        $slug = Str::slug($source);

        if ($slug !== '') {
            return Str::limit($slug, 180, '');
        }

        $slug = Str::slug(Str::ascii($source));

        return $slug !== '' ? Str::limit($slug, 180, '') : 'post-'.Str::lower(Str::random(8));
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function exists(string $modelClass, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = $modelClass::query()->where($column, $slug);

        if (method_exists($modelClass, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
