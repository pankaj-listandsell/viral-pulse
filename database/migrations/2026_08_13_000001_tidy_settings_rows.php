<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rows nothing reads, and the tuning values that moved out of .env.
     *
     * A settings screen is only trustworthy if every control on it does
     * something. These seven did not: the comments feature was dropped before
     * it was built, the timezone row duplicated APP_TIMEZONE without being
     * wired to it, and the rest were written by the form and read by nobody.
     */
    private const REMOVED = [
        'comments_enabled',
        'comments_require_approval',
        'timezone',
        'ai_default_language',
        'ai_default_tone',
        'seo_default_title',
        'site_tagline',
    ];

    public function up(): void
    {
        DB::table('settings')->whereIn('key', self::REMOVED)->delete();
    }

    public function down(): void
    {
        // Deliberately not restored. They held no behaviour to bring back, and
        // SettingSeeder is the place that decides which settings exist.
    }
};
