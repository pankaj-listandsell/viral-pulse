<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'subject', 'message', 'ip_hash', 'status', 'read_at'];

    protected $hidden = ['ip_hash'];

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'read_at' => 'datetime',
        ];
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', ContactMessageStatus::New);
    }

    /**
     * The sidebar badge is cached for a minute. Dropping the key here means a
     * message that is opened, marked or deleted updates the count immediately -
     * a stale unread number is the sort of small wrongness that makes an admin
     * stop trusting the rest of the screen.
     */
    protected static function booted(): void
    {
        $forget = fn () => Cache::forget('admin.unread-messages');

        static::created($forget);
        static::updated($forget);
        static::deleted($forget);
    }
}
