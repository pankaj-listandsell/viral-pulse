<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Sample content for local development only. Guarded so it can never
     * create placeholder articles on a production database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('PostSeeder skipped: sample content is for local development only.');

            return;
        }

        $authors = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', [Role::ADMIN, Role::EDITOR, Role::AUTHOR]))
            ->get();

        if ($authors->isEmpty()) {
            $authors = collect([User::factory()->admin()->create()]);
        }

        $categories = Category::all();
        $tags = Tag::all();

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(3)->create();
        }

        Post::factory()
            ->count(40)
            ->sequence(fn ($sequence) => [
                'author_id' => $authors->random()->id,
                'category_id' => $categories->random()->id,
            ])
            ->create()
            ->each(function (Post $post) use ($tags): void {
                if ($tags->isNotEmpty()) {
                    $post->tags()->sync($tags->random(min(3, $tags->count()))->pluck('id'));
                }
            });

        Post::factory()->count(6)->draft()->create([
            'author_id' => $authors->random()->id,
            'category_id' => $categories->random()->id,
        ]);

        Post::factory()->count(4)->scheduled()->create([
            'author_id' => $authors->random()->id,
            'category_id' => $categories->random()->id,
        ]);

        Post::factory()->count(5)->aiGenerated()->create([
            'author_id' => $authors->random()->id,
            'category_id' => $categories->random()->id,
        ]);

        Post::published()->inRandomOrder()->limit(8)->get()->each(function (Post $post): void {
            Comment::factory()->count(3)->approved()->create(['post_id' => $post->id]);
            Comment::factory()->count(1)->create(['post_id' => $post->id]);
        });

        $this->command?->info('Sample posts, tags and comments seeded.');
    }
}
