<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            SettingSeeder::class,
            // After SettingSeeder: it fills the rows this one writes into.
            BrandSeeder::class,
            PostSeeder::class,
        ]);
    }
}
