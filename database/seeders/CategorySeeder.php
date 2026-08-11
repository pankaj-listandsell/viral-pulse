<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Trending', 'color' => '#ef4444', 'icon' => 'flame', 'is_featured' => true],
            ['name' => 'News', 'color' => '#3b82f6', 'icon' => 'newspaper', 'is_featured' => true],
            ['name' => 'Technology', 'color' => '#6366f1', 'icon' => 'cpu', 'is_featured' => true],
            ['name' => 'Entertainment', 'color' => '#ec4899', 'icon' => 'clapperboard', 'is_featured' => true],
            ['name' => 'Travel', 'color' => '#14b8a6', 'icon' => 'plane', 'is_featured' => false],
            ['name' => 'Devotional', 'color' => '#f59e0b', 'icon' => 'sparkles', 'is_featured' => false],
            ['name' => 'Education', 'color' => '#22c55e', 'icon' => 'graduation-cap', 'is_featured' => false],
            ['name' => 'Lifestyle', 'color' => '#a855f7', 'icon' => 'heart', 'is_featured' => false],
            ['name' => 'Health', 'color' => '#10b981', 'icon' => 'activity', 'is_featured' => false],
            ['name' => 'Sports', 'color' => '#f97316', 'icon' => 'trophy', 'is_featured' => false],
            ['name' => 'Business', 'color' => '#0ea5e9', 'icon' => 'briefcase', 'is_featured' => false],
            ['name' => 'Quiz & Fun', 'color' => '#eab308', 'icon' => 'puzzle', 'is_featured' => false],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    ...$category,
                    'slug' => Str::slug($category['name']),
                    'sort_order' => $index,
                    'is_active' => true,
                    'description' => "Latest {$category['name']} stories and updates.",
                ]
            );
        }
    }
}
