<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * RSS 2.0 for the site and for each category.
 *
 * Items carry the excerpt rather than the full article: a full-text feed is
 * scraped and republished within minutes, and the duplicate then competes with
 * the original in search results.
 */
class FeedService
{
    private const ITEMS = 25;

    private const TTL_MINUTES = 30;

    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SettingsService $settings,
    ) {}

    public function site(): string
    {
        return Cache::remember('feed.rss.site', now()->addMinutes(self::TTL_MINUTES), fn (): string => $this->render(
            title: $this->siteName(),
            description: $this->settings->get('site_description') ?: 'The latest stories.',
            link: route('home'),
            self: route('feed.index'),
            posts: $this->feed->base()->orderByDesc('published_at')->limit(self::ITEMS)->get(),
        ));
    }

    public function category(Category $category): string
    {
        return Cache::remember(
            "feed.rss.category.{$category->id}",
            now()->addMinutes(self::TTL_MINUTES),
            fn (): string => $this->render(
                title: "{$category->name} · {$this->siteName()}",
                description: $category->description ?: "The latest {$category->name} stories.",
                link: route('categories.show', $category->slug),
                self: route('feed.category', $category->slug),
                posts: $this->feed->base()
                    ->where('category_id', $category->id)
                    ->orderByDesc('published_at')
                    ->limit(self::ITEMS)
                    ->get(),
            )
        );
    }

    public function flush(): void
    {
        Cache::forget('feed.rss.site');

        foreach (Category::pluck('id') as $id) {
            Cache::forget("feed.rss.category.{$id}");
        }
    }

    /**
     * @param  Collection<int, Post>  $posts
     */
    private function render(string $title, string $description, string $link, string $self, $posts): string
    {
        $built = $posts->first()?->published_at ?? now();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
        $xml .= '  <channel>'."\n";
        $xml .= '    <title>'.$this->escape($title).'</title>'."\n";
        $xml .= '    <link>'.$this->escape($link).'</link>'."\n";
        $xml .= '    <description>'.$this->escape($description).'</description>'."\n";
        $xml .= '    <language>'.$this->escape(str_replace('_', '-', app()->getLocale())).'</language>'."\n";
        $xml .= '    <lastBuildDate>'.$built->toRfc2822String().'</lastBuildDate>'."\n";
        $xml .= '    <atom:link href="'.$this->escape($self).'" rel="self" type="application/rss+xml" />'."\n";

        foreach ($posts as $post) {
            $url = route('posts.show', $post->slug);

            $xml .= '    <item>'."\n";
            $xml .= '      <title>'.$this->escape($post->title).'</title>'."\n";
            $xml .= '      <link>'.$this->escape($url).'</link>'."\n";
            $xml .= '      <guid isPermaLink="true">'.$this->escape($url).'</guid>'."\n";
            $xml .= '      <description>'.$this->escape($this->summary($post)).'</description>'."\n";

            if ($post->published_at) {
                $xml .= '      <pubDate>'.Carbon::parse($post->published_at)->toRfc2822String().'</pubDate>'."\n";
            }

            $xml .= '    </item>'."\n";
        }

        return $xml.'  </channel>'."\n".'</rss>'."\n";
    }

    private function summary(Post $post): string
    {
        return Str::limit(trim(strip_tags((string) $post->excerpt)), 300) ?: $post->title;
    }

    private function siteName(): string
    {
        return $this->settings->get('site_name') ?: config('app.name');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
