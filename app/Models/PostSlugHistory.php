<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostSlugHistory extends Model
{
    protected $table = 'post_slug_history';

    /** Only ever written once, so there is nothing for updated_at to record. */
    public const UPDATED_AT = null;

    protected $fillable = ['post_id', 'slug'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
