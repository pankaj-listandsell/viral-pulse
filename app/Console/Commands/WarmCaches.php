<?php

namespace App\Console\Commands;

use App\Services\ContentFeedService;
use App\Services\FeedService;
use App\Services\SettingsService;
use App\Services\SitemapService;
use Illuminate\Console\Command;

class WarmCaches extends Command
{
    protected $signature = 'cache:warm';

    protected $description = 'Rebuild the feed, sitemap and RSS caches so the first visitor after a deploy does not pay for it';

    public function handle(
        SettingsService $settings,
        ContentFeedService $feed,
        SitemapService $sitemap,
        FeedService $rss,
    ): int {
        // Whoever arrives first after a deploy would otherwise rebuild all of
        // this on their own request, which is the slowest page view the site
        // ever serves - and often a crawler's first impression of it.
        $steps = [
            'settings' => fn () => $settings->all(),
            'home feed' => function () use ($feed) {
                $feed->hero();
                $feed->latest(9);
                $feed->trending(5);
                $feed->featured(4);
                $feed->popular();
                $feed->popularCategories();
                $feed->navigation();
            },
            'sitemap' => function () use ($sitemap) {
                $sitemap->index();
                $sitemap->posts(1);
                $sitemap->categories();
                $sitemap->tags();
                $sitemap->pages();
            },
            'rss' => fn () => $rss->site(),
        ];

        foreach ($steps as $label => $step) {
            $started = microtime(true);
            $step();
            $this->line(sprintf('  %-12s %sms', $label, (int) round((microtime(true) - $started) * 1000)));
        }

        $this->info('Caches warmed.');

        return self::SUCCESS;
    }
}
