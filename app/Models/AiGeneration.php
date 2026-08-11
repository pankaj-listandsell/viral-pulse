<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'trending_topic_id',
        'provider',
        'model',
        'content_type',
        'topic',
        'language',
        'tone',
        'target_audience',
        'target_length',
        'prompt',
        'raw_response',
        'parsed_output',
        'status',
        'error_message',
        'prompt_tokens',
        'completion_tokens',
        'cost',
        'duration_ms',
        'quality_score',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiGenerationStatus::class,
            'parsed_output' => 'array',
            'target_length' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'cost' => 'decimal:6',
            'duration_ms' => 'integer',
            'quality_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function trendingTopic(): BelongsTo
    {
        return $this->belongsTo(TrendingTopic::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AiGenerationStatus::Completed);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', AiGenerationStatus::Failed);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function totalTokens(): int
    {
        return (int) $this->prompt_tokens + (int) $this->completion_tokens;
    }
}
