<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'token',
        'status',
        'confirmed_at',
        'unsubscribed_at',
        'ip_hash',
        'source',
    ];

    protected $hidden = ['token', 'ip_hash'];

    protected function casts(): array
    {
        return [
            'status' => SubscriberStatus::class,
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscriber): void {
            $subscriber->token ??= Str::random(64);
        });
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::Subscribed);
    }
}
