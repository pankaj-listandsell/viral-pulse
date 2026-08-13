<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Images\FeaturedImageService;
use Illuminate\Console\Command;

class GenerateFeaturedImages extends Command
{
    protected $signature = 'posts:generate-cards
                            {--limit=50 : Maximum posts to process}
                            {--force : Replace images that already exist}';

    protected $description = 'Draw a branded featured image for posts that have none';

    public function handle(FeaturedImageService $images): int
    {
        $force = (bool) $this->option('force');

        $posts = Post::query()
            ->with('category:id,name,color', 'author:id,name')
            ->when(! $force, fn ($query) => $query->whereNull('featured_image'))
            ->latest('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Every post already has an image.');

            return self::SUCCESS;
        }

        $made = 0;

        foreach ($posts as $post) {
            if ($images->ensure($post, force: $force)) {
                $made++;
                $this->line("  {$post->title}");

                continue;
            }

            $this->warn("  skipped: {$post->title}");
        }

        $this->info("{$made} card(s) generated.");

        return self::SUCCESS;
    }
}
