<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'width',
        'height',
        'alt_text',
        'caption',
        'folder',
        'conversions',
    ];

    protected function casts(): array
    {
        return [
            'conversions' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk($this->disk)->url($this->path));
    }

    protected function isImage(): Attribute
    {
        return Attribute::get(fn (): bool => str_starts_with($this->mime_type, 'image/'));
    }

    public function conversionUrl(string $name): ?string
    {
        $path = $this->conversions[$name]['path'] ?? null;

        return $path ? Storage::disk($this->disk)->url($path) : null;
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeInFolder(Builder $query, ?string $folder): Builder
    {
        return $folder === null
            ? $query->whereNull('folder')
            : $query->where('folder', $folder);
    }
}
