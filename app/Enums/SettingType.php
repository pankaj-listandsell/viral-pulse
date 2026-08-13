<?php

namespace App\Enums;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

enum SettingType: string
{
    case String = 'string';
    case Text = 'text';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Json = 'json';
    case File = 'file';

    /**
     * A secret. Encrypted with APP_KEY before it is written, so a leaked
     * database dump - the realistic way these escape - contains ciphertext
     * rather than a working API key.
     */
    case Encrypted = 'encrypted';

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
            self::Encrypted => $this->decrypt($value),
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
            self::Encrypted => $value === '' ? null : Crypt::encryptString((string) $value),
            default => (string) $value,
        };
    }

    public function isSecret(): bool
    {
        return $this === self::Encrypted;
    }

    /**
     * A value that will not decrypt is treated as absent rather than fatal.
     *
     * The usual cause is APP_KEY having changed, which makes every stored
     * secret unreadable. Falling back to "not configured" means the provider
     * is simply not offered and the admin re-enters the key, instead of every
     * page in the site throwing.
     */
    private function decrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            Log::warning('A stored secret could not be decrypted. APP_KEY has probably changed; the value needs entering again.');

            return null;
        }
    }
}
