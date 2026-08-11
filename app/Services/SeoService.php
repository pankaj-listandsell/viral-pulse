<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the head metadata and structured data for public pages.
 *
 * Everything here is rendered server-side into the HTML. A crawler that runs
 * no JavaScript still receives the complete title, description, canonical,
 * social cards and JSON-LD.
 */
class SeoService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function forPost(Post $post): array
    {
        return [
            'title' => $post->seo_title ?: $post->title,
            'description' => $post->seo_description ?: Str::limit(strip_tags($post->excerpt ?: $post->content), 155),
            'keywords' => $post->seo_keywords,
            // A canonical pointing elsewhere means the article was first
            // published there; otherwise it points at this page.
            'canonical' => $post->canonical_url ?: route('posts.show', $post),
            'image' => $this->imageUrl($post->og_image ?: $post->featured_image),
            'type' => 'article',
            'published_at' => $post->published_at,
            'modified_at' => $post->updated_at,
            'author' => $post->author?->name,
            'schemas' => [
                $this->articleSchema($post),
                $this->breadcrumbSchema([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => $post->category->name, 'url' => route('categories.show', $post->category)],
                    ['name' => $post->title, 'url' => route('posts.show', $post)],
                ]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forCategory(Category $category, int $page = 1): array
    {
        return [
            'title' => $category->seo_title ?: $category->name,
            'description' => $category->seo_description
                ?: Str::limit($category->description ?: "The latest {$category->name} stories.", 155),
            'canonical' => route('categories.show', $category),
            'type' => 'website',
            // Page 2 onwards is thin, near-duplicate content. Keeping it out of
            // the index protects the pages that actually rank.
            'robots' => $page > 1 ? 'noindex, follow' : null,
            'schemas' => [
                $this->breadcrumbSchema([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => $category->name, 'url' => route('categories.show', $category)],
                ]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forTag(Tag $tag, int $page = 1): array
    {
        return [
            'title' => "#{$tag->name}",
            'description' => Str::limit($tag->description ?: "Stories tagged {$tag->name}.", 155),
            'canonical' => route('tags.show', $tag),
            'robots' => $page > 1 ? 'noindex, follow' : null,
            'schemas' => [
                $this->breadcrumbSchema([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => $tag->name, 'url' => route('tags.show', $tag)],
                ]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forPage(?string $title = null, ?string $description = null, ?string $canonical = null, ?string $robots = null): array
    {
        return [
            'title' => $title,
            'description' => $description ?: $this->settings->get('seo_default_description'),
            'canonical' => $canonical ?: url()->current(),
            'robots' => $robots,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function articleSchema(Post $post): array
    {
        $image = $this->imageUrl($post->og_image ?: $post->featured_image);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => Str::limit($post->title, 110, ''),
            'description' => $post->seo_description ?: Str::limit(strip_tags($post->excerpt ?? ''), 155),
            'image' => $image ? [$image] : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'inLanguage' => $post->language,
            'wordCount' => str_word_count(strip_tags($post->content)),
            'articleSection' => $post->category?->name,
            'keywords' => $post->seo_keywords,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('posts.show', $post)],
            // A real account, never an invented byline.
            'author' => $post->author ? [
                '@type' => 'Person',
                'name' => $post->author->name,
            ] : null,
            'publisher' => $this->organizationSchema(),
        ]);
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /**
     * An archive is a list of articles, and saying so lets a crawler read the
     * order without parsing the markup.
     *
     * @param  iterable<int, Post>  $posts
     * @return array<string, mixed>
     */
    public function itemListSchema(iterable $posts, string $name): array
    {
        $elements = [];

        foreach ($posts as $post) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'url' => route('posts.show', $post->slug),
                'name' => $post->title,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => count($elements),
            'itemListElement' => $elements,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationSchema(): array
    {
        $logo = $this->imageUrl($this->settings->get('site_logo'));

        return array_filter([
            '@type' => 'Organization',
            'name' => $this->siteName(),
            'url' => url('/'),
            'logo' => $logo ? ['@type' => 'ImageObject', 'url' => $logo] : null,
            'sameAs' => $this->socialProfiles() ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteName(),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function siteName(): string
    {
        return $this->settings->get('site_name') ?: config('app.name');
    }

    /**
     * @return array<int, string>
     */
    private function socialProfiles(): array
    {
        return collect(['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube'])
            ->map(fn (string $key) => $this->settings->get($key))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Social cards and structured data need absolute URLs; a relative path is
     * silently ignored by every crawler that reads them.
     */
    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return $this->settings->get('seo_default_og_image')
                ? Storage::disk(config('site.media.disk'))->url($this->settings->get('seo_default_og_image'))
                : null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::disk(config('site.media.disk'))->url($path);
    }
}
