<?php

namespace App\Models;

use App\Enums\ScheduledPostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'scheduled_at',
        'status',
        'attempts',
        'last_error',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScheduledPostStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', ScheduledPostStatus::Pending)
            ->where('scheduled_at', '<=', now());
    }
}
