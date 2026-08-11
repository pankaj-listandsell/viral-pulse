<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'value', 'type', 'is_public', 'description'];

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_public' => 'boolean',
        ];
    }

    /**
     * The stored string decoded into its declared type.
     */
    protected function typedValue(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->type->cast($this->value));
    }

    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
