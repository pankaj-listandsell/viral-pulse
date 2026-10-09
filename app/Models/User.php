<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * `is_admin` is deliberately absent: admin rights are granted explicitly,
     * never through a request payload.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_author' => 'boolean',
            'author_categories' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Desk authors are found by username, which is unique and URL-safe.
     */
    public function authorUrl(): ?string
    {
        return $this->is_author && $this->username ? route('authors.show', $this->username) : null;
    }

    /**
     * Initials for the avatar badge: "ViralPulse News Desk" gives "ND", the
     * site prefix being the same on every desk and telling them apart not at all.
     */
    public function initials(): string
    {
        $words = collect(preg_split('/\s+/', trim(str_replace('ViralPulse', '', $this->name))))
            ->filter(fn (string $word) => ctype_alpha($word[0] ?? ''));

        return strtoupper(implode('', $words->take(2)->map(fn (string $word) => $word[0])->all())) ?: 'VP';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * A deactivated account keeps no access, whatever its admin flag says.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this->is_admin && $this->is_active;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    public function scopeAuthors(Builder $query): Builder
    {
        return $query->where('is_author', true);
    }
}
