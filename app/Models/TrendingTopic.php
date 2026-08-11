<?php

namespace App\Models;

use App\Enums\TrendingSource;
use App\Enums\TrendingTopicStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrendingTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'topic_hash',
        'slug',
        'description',
        'source',
        'source_url',
        'category_id',
        'trend_score',
        'region',
        'language',
        'raw_payload',
        'detected_at',
        'status',
        'post_id',
    ];

    protected function casts(): array
    {
        return [
            'source' => TrendingSource::class,
            'status' => TrendingTopicStatus::class,
            'raw_payload' => 'array',
            'detected_at' => 'datetime',
            'trend_score' => 'integer',
        ];
    }

    /**
     * The dedupe key. Normalising before hashing means "IPL Final 2026" and
     * "ipl  final 2026" collapse to the same topic.
     */
    public static function hashTopic(string $topic): string
    {
        $normalised = preg_replace('/\s+/', ' ', mb_strtolower(trim($topic))) ?? '';

        return sha1($normalised);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function scopeAvailableForGeneration(Builder $query): Builder
    {
        return $query->whereIn('status', [TrendingTopicStatus::New, TrendingTopicStatus::Failed]);
    }

    public function scopeHighestScoring(Builder $query): Builder
    {
        return $query->orderByDesc('trend_score')->orderByDesc('detected_at');
    }

    public function scopeFromSource(Builder $query, TrendingSource $source): Builder
    {
        return $query->where('source', $source);
    }
}
