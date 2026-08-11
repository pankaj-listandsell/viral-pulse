<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'Viral', 'Breaking', 'Explained', 'Listicle', 'How To', 'Facts',
            'AI', 'Gadgets', 'Bollywood', 'Cricket', 'Festival', 'Recipes',
            'Fitness', 'Startups', 'Space', 'History',
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => Str::slug($tag)],
                ['name' => $tag, 'slug' => Str::slug($tag)]
            );
        }
    }
}
