<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Turns visitor identifiers into salted hashes.
 *
 * Raw IP addresses are never written to the database. Hashing with the app key
 * means the values are still stable enough to deduplicate views, rate-limit
 * likes and trace abuse, without storing personal data.
 */
class Fingerprint
{
    public static function ip(?string $ip): string
    {
        return hash_hmac('sha256', (string) $ip, config('app.key'));
    }

    public static function userAgent(?string $userAgent): ?string
    {
        return $userAgent === null ? null : hash_hmac('sha256', $userAgent, config('app.key'));
    }

    public static function fromRequest(Request $request): string
    {
        return self::ip($request->ip());
    }
}
