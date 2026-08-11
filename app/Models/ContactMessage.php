<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
