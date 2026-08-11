<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'admin';

    public const EDITOR = 'editor';

    public const AUTHOR = 'author';

    public const USER = 'user';

    protected $fillable = ['name', 'slug', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
