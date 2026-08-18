<?php

namespace App\Models;

use App\Enums\PostSourceType;
use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_alt',
        'status',
        'published_at',
        'scheduled_at',
        'source_type',
        'ai_generated',
        'language',
        'reading_time',
        'is_featured',
        'is_trending',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_image',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'source_type' => PostSourceType::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'ai_generated' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'reading_time' => 'integer',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->approved();
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(PostDailyStat::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function scheduledPost(): HasMany
    {
        return $this->hasMany(ScheduledPost::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function trendingTopic(): HasMany
    {
        return $this->hasMany(TrendingTopic::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'slug', $value)
            ->when(is_numeric($value), fn ($query) => $query->orWhere('id', (int) $value))
            ->first();
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::get(fn (): string => $this->seo_title ?: $this->title);
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::get(fn (): string => $this->seo_description ?: Str::limit(strip_tags($this->content), 155));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Scheduled);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Archived);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    public function scopeAiGenerated(Builder $query): Builder
    {
        return $query->where('ai_generated', true);
    }

    public function scopeInCategory(Builder $query, Category|int $category): Builder
    {
        return $query->where('category_id', $category instanceof Category ? $category->id : $category);
    }

    /**
     * Uses the FULLTEXT index when the driver supports it and the term has at
     * least one indexable token, and falls back to LIKE otherwise (short terms
     * fall below innodb_ft_min_token_size and would silently return nothing).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        if ($this->canUseFullText($term)) {
            return $query->whereRaw(
                'MATCH(title, excerpt, content) AGAINST (? IN BOOLEAN MODE)',
                [$this->toBooleanModeTerm($term)]
            );
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('content', 'like', $like);
        });
    }

    private function canUseFullText(string $term): bool
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        foreach (preg_split('/\s+/', $term) ?: [] as $token) {
            if (mb_strlen($token) >= 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strip boolean-mode operators out of user input, then require every
     * indexable token and allow a trailing wildcard on each.
     */
    private function toBooleanModeTerm(string $term): string
    {
        $cleaned = preg_replace('/[+\-><()~*"@]+/', ' ', $term) ?? '';

        $tokens = array_filter(
            preg_split('/\s+/', trim($cleaned)) ?: [],
            fn (string $token): bool => mb_strlen($token) >= 3
        );

        return implode(' ', array_map(fn (string $token): string => '+'.$token.'*', $tokens));
    }
}
