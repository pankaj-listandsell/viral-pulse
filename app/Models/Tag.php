<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_trending'];

    protected function casts(): array
    {
        return [
            'is_trending' => 'boolean',
            'posts_count' => 'integer',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('posts_count');
    }
}
