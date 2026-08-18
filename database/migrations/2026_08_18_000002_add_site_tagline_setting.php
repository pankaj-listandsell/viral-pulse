<?php

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'site_tagline'],
            [
                'group' => 'general',
                'value' => 'Trending Stories, Explained Fast.',
                'type' => SettingType::String,
                'is_public' => true,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', 'site_tagline')->delete();
    }
};
