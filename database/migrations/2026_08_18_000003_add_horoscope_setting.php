<?php

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'horoscope_enabled'],
            [
                'group' => 'features',
                'value' => '1',
                'type' => SettingType::Boolean,
                'is_public' => true,
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', 'horoscope_enabled')->delete();
    }
};
