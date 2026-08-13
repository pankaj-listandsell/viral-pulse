<?php

namespace App\Enums;

enum SettingType: string
{
    case String = 'string';
    case Text = 'text';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Json = 'json';
    case File = 'file';

    /**
     * Convert a stored string back into its real PHP type.
     */
    public function cast(?string $value): mixed
    {
        // An empty string is "not configured", the same as null. Without this
        // an empty integer setting casts to 0 and an empty boolean to false,
        // so clearing a field reads as "set it to zero" - which is how an
        // untouched daily limit could switch generation off entirely.
        if ($value === null || trim($value) === '') {
            return null;
        }

        return match ($this) {
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::Integer => (int) $value,
            self::Json => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Convert a PHP value into its stored string form.
     */
    public function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Boolean => $value ? '1' : '0',
            self::Json => json_encode($value),
            default => (string) $value,
        };
    }
}
