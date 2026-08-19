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
    /**
     * Google renders about 155-160 characters of a description. Anything past
     * that is cut mid-word in the results.
     */
    private const DESCRIPTION_LIMIT = 158;

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function forPost(Post $post): array
    {
        $faqs = $this->extractFaqs($post->content);
        $schemas = [
            $this->articleSchema($post),
            $this->breadcrumbSchema([
                ['name' => 'Home', 'url' => route('home')],
                ['name' => $post->category->name, 'url' => route('categories.show', $post->category)],
                ['name' => $post->title, 'url' => route('posts.show', $post)],
            ]),
        ];

        if ($faqSchema = $this->faqSchema($faqs)) {
            $schemas[] = $faqSchema;
        }

        return [
            'title' => $post->seo_title ?: $post->title,
            'description' => $this->description($post->seo_description ?: strip_tags($post->excerpt ?: $post->content)),
            'keywords' => $post->seo_keywords,
            // A canonical pointing elsewhere means the article was first
            // published there; otherwise it points at this page.
            'canonical' => $post->canonical_url ?: route('posts.show', $post),
            'image' => $this->imageUrl($post->og_image ?: $post->featured_image),
            'type' => 'article',
            'published_at' => $post->published_at,
            'modified_at' => $post->updated_at,
            // The masthead, matching the byline on the page and the author in
            // the Article schema. A staff account's name on an AI-drafted
            // article would be a byline nobody earned.
            'author' => $this->siteName(),
            'schemas' => $schemas,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forCategory(Category $category, int $page = 1): array
    {
        return [
            // A bare section name is about twenty characters once the site
            // name is appended, which leaves most of the result line empty and
            // says nothing a searcher typed. The suffix costs nothing and
            // carries the words people actually search a section for.
            'title' => $category->seo_title ?: "{$category->name} — latest news and stories",
            'description' => $this->description(
                $category->seo_description ?: $this->archiveDescription($category->name, $category->description, $category->posts_count)
            ),
            'canonical' => route('categories.show', $category),
            'image' => $this->imageUrl(null),
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
            // "#UPI" is four characters of title for a page that is entirely
            // about UPI. The hash means nothing to a search engine.
            'title' => "{$tag->name}: latest news and stories",
            'description' => $this->description($this->archiveDescription($tag->name, $tag->description, $tag->posts_count)),
            'canonical' => route('tags.show', $tag),
            'image' => $this->imageUrl(null),
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
            'description' => $this->description($description ?: $this->settings->get('seo_default_description')),
            'canonical' => $canonical ?: url()->current(),
            'image' => $this->imageUrl(null),
            'robots' => $robots,
        ];
    }

    /**
     * Clamps a description to what a search engine will actually render, at a
     * word boundary.
     *
     * Applied here rather than trusted to the source: the AI writer already
     * trims its own output, but an editor typing 300 characters into the SEO
     * field would otherwise ship all 300 and have it cut mid-word in the
     * results page.
     */
    private function description(?string $text): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) <= self::DESCRIPTION_LIMIT) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::DESCRIPTION_LIMIT);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace ? mb_substr($cut, 0, $lastSpace) : $cut, " ,;:-\u{2014}").'…';
    }

    /**
     * A fallback with enough substance to be used.
     *
     * "Stories tagged UPI." is nineteen characters; Google discards a snippet
     * that thin and writes its own from the page, which is a worse one.
     */
    private function archiveDescription(string $name, ?string $existing, int $count): string
    {
        if (filled($existing) && mb_strlen($existing) >= 70) {
            return $existing;
        }

        $site = $this->siteName();
        $articles = $count === 1 ? '1 article' : "{$count} articles";

        return trim(($existing ? rtrim($existing, '.').'. ' : ''))
            ."Read {$articles} about {$name} on {$site} — the latest updates, explainers and "
            .'background, kept current as the story develops.';
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
            'description' => $this->description($post->seo_description ?: strip_tags($post->excerpt ?? '')),
            'image' => $image ? [$image] : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'inLanguage' => $post->language,
            'wordCount' => str_word_count(strip_tags($post->content)),
            'articleSection' => $post->category?->name,
            'keywords' => $post->seo_keywords,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('posts.show', $post)],
            // The publication, not a person. Articles are drafted by AI and
            // reviewed by an editor, so naming one staff account on every one
            // of them would be a byline nobody earned - and Google expects the
            // author in the markup to be the author shown on the page, which
            // is now the masthead. schema.org allows an Organization here.
            'author' => $this->organizationSchema(),
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
     * Extracts questions and answers from article content for FAQ Schema.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function extractFaqs(string $content): array
    {
        $faqs = [];

        // Match <details><summary>(Question)</summary>(Answer)</details>
        if (preg_match_all('/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $q = trim(strip_tags($m[1]));
                $a = trim(strip_tags($m[2]));
                if ($q && $a) {
                    $faqs[] = ['question' => $q, 'answer' => $a];
                }
            }
        }

        // Match <h3>(Question?)</h3> <p>(Answer)</p>
        if (empty($faqs) && preg_match_all('/<h[34][^>]*>(.*?\?)<\/h[34]>\s*<p>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $q = trim(strip_tags($m[1]));
                $a = trim(strip_tags($m[2]));
                if ($q && $a) {
                    $faqs[] = ['question' => $q, 'answer' => $a];
                }
            }
        }

        return $faqs;
    }

    /**
     * @param array<int, array{question: string, answer: string}> $faqs
     * @return array<string, mixed>|null
     */
    public function faqSchema(array $faqs): ?array
    {
        if (empty($faqs)) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ], $faqs),
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
        // The site logo is a poor share image but a far better one than none:
        // a link with no picture is visibly smaller in every feed and gets
        // clicked less. A real 1200x630 default belongs in Settings -> SEO.
        $candidate = $path
            ?: $this->settings->get('seo_default_og_image')
            ?: $this->settings->get('site_logo');

        if (blank($candidate)) {
            return null;
        }

        return Str::startsWith($candidate, ['http://', 'https://'])
            ? $candidate
            : Storage::disk(config('site.media.disk'))->url($candidate);
    }
}
