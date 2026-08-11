<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostDailyStat extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'date', 'views', 'unique_views', 'likes'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'views' => 'integer',
            'unique_views' => 'integer',
            'likes' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeBetween(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }
}
