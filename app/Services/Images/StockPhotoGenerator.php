<?php

namespace App\Services\Images;

use App\Models\Media;
use App\Models\Post;
use App\Services\Images\Contracts\FeaturedImageGenerator;
use App\Services\MediaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Finds a real, licensed photograph for a post on Pexels.
 *
 * A photograph is only fetched for the sections that opt in, and the search
 * runs on the subject rather than the headline: "Sensex drops 150 points" asks
 * for a picture of a stock exchange, not for a picture of that afternoon. The
 * distinction matters - the first illustrates a subject, the second would be
 * passing an unrelated photo off as a record of an event.
 */
class StockPhotoGenerator implements FeaturedImageGenerator
{
    /**
     * Words that carry no visual meaning. Left in the query they push Pexels
     * towards stock photos of people pointing at charts.
     */
    private const NOISE = [
        'the', 'a', 'an', 'and', 'or', 'of', 'for', 'to', 'in', 'on', 'at', 'by', 'with', 'from',
        'as', 'is', 'are', 'was', 'were', 'be', 'been', 'this', 'that', 'these', 'those', 'it',
        'its', 'after', 'before', 'over', 'under', 'amid', 'about', 'into', 'up', 'down', 'out',
        'new', 'latest', 'today', 'now', 'live', 'update', 'updates', 'explained', 'key', 'how',
        'why', 'what', 'when', 'who', 'top', 'best', 'full', 'says', 'said', 'will', 'can',
    ];

    /**
     * Category related contextual themes so that if an exact article query fails,
     * Pexels finds a relevant, high quality topic image instead of returning empty.
     */
    private const CATEGORY_THEMES = [
        'news' => ['breaking news journalism', 'newspaper media headline', 'news broadcast studio'],
        'trending' => ['trending topic concept', 'viral news media', 'technology abstract concept'],
        'technology' => ['modern technology', 'digital computer workspace', 'artificial intelligence future'],
        'sports' => ['stadium arena lights', 'sports match competition', 'athletic action fitness'],
        'business' => ['business finance market', 'stock exchange trading', 'modern corporate office'],
        'entertainment' => ['cinema movie theater stage', 'entertainment spotlight', 'music concert performance'],
        'lifestyle' => ['modern lifestyle daily', 'minimalist home living', 'wellness coffee morning'],
        'health' => ['healthcare medicine doctor', 'wellness nutrition fitness', 'hospital scientific laboratory'],
        'travel' => ['scenic travel landscape', 'mountain nature adventure', 'vacation destination wanderlust'],
        'education' => ['books library university', 'education classroom study', 'student learning desk'],
        'devotional' => ['temple spiritual meditation', 'morning peace sunrise prayer'],
        'astrology' => ['night sky stars cosmos', 'galaxy constellation space', 'zodiac horoscope astrology'],
        'quiz-fun' => ['colorful celebration festival', 'party confetti event', 'creative game puzzle'],
    ];

    public function __construct(private readonly MediaService $media) {}

    public function name(): string
    {
        return 'Stock photo';
    }

    public function generate(Post $post): ?Media
    {
        $post->loadMissing(['tags', 'category', 'author']);

        $config = config('site.media.stock');

        if (blank($config['key'] ?? null)) {
            return null;
        }

        $queries = $this->getSearchQueries($post);

        if (empty($queries)) {
            return null;
        }

        $photo = null;
        foreach ($queries as $query) {
            $photo = $this->search($query, $config);
            if ($photo) {
                break;
            }
        }

        // If specific searches yielded nothing, try the category theme or general fallback
        if (! $photo) {
            $fallbackQueries = $this->getFallbackQueries($post);
            foreach ($fallbackQueries as $fbQuery) {
                $photo = $this->search($fbQuery, $config);
                if ($photo) {
                    break;
                }
            }
        }

        if (! $photo) {
            return null;
        }

        return $this->download($photo, $post);
    }

    /**
     * Get search queries to try in sequence on Pexels, ordered from most specific to broadest fallback.
     *
     * @return array<int, string>
     */
    public function getSearchQueries(Post $post): array
    {
        $queries = [];

        // 1. Specific query (tags + category, or first 3 words of title)
        $primary = $this->query($post);
        if (filled($primary)) {
            $queries[] = $primary;
        }

        // 2. Just the tags (if they exist)
        $tags = $post->tags->pluck('name')->take(2)->implode(' ');
        if (filled($tags)) {
            $queries[] = trim($tags);
        }

        // 3. Category name alone (e.g. "business", "technology")
        if ($post->category?->name) {
            $queries[] = Str::lower($post->category->name);
        }

        // 4. First 2 non-noise words of the title
        $words = collect(preg_split('/[^\p{L}\p{N}]+/u', Str::lower($post->title), -1, PREG_SPLIT_NO_EMPTY))
            ->reject(fn (string $word) => in_array($word, self::NOISE, true) || mb_strlen($word) < 3);

        if ($words->isNotEmpty()) {
            $queries[] = $words->take(2)->implode(' ');
            $queries[] = $words->first();
        }

        return array_values(array_unique(array_filter(array_map('trim', $queries))));
    }

    /**
     * Related topic fallbacks so that if exact title searches fail, any relevant
     * high-quality photo matching the category or general theme is used.
     *
     * @return array<int, string>
     */
    public function getFallbackQueries(Post $post): array
    {
        $fallbacks = [];
        $slug = $post->category?->slug;

        if ($slug && isset(self::CATEGORY_THEMES[$slug])) {
            $fallbacks = array_merge($fallbacks, self::CATEGORY_THEMES[$slug]);
        }

        // Broad general fallbacks
        $fallbacks[] = 'breaking news editorial';
        $fallbacks[] = 'daily trending news';
        $fallbacks[] = 'creative journalism media';

        return array_values(array_unique(array_filter(array_map('trim', $fallbacks))));
    }

    /**
     * The subject of the story, in at most three words.
     *
     * Tags first: an editor tagging a post has already decided what it is
     * about, which is a better signal than anything derived from a headline.
     */
    public function query(Post $post): string
    {
        $tags = $post->tags->pluck('name')->take(2)->implode(' ');

        if (filled($tags)) {
            return trim($tags.' '.($post->category?->name ?? ''));
        }

        $words = collect(preg_split('/[^\p{L}\p{N}]+/u', Str::lower($post->title), -1, PREG_SPLIT_NO_EMPTY))
            ->reject(fn (string $word) => in_array($word, self::NOISE, true) || mb_strlen($word) < 3)
            ->take(3);

        return $words->isEmpty()
            ? Str::lower((string) $post->category?->name)
            : $words->implode(' ');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    private function search(string $query, array $config): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $config['key']])
                ->get($config['endpoint'], [
                    'query' => $query,
                    'orientation' => $config['orientation'] ?? 'landscape',
                    'per_page' => 10,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Pexels could not be reached', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Pexels search failed', ['status' => $response->status(), 'query' => $query]);

            return null;
        }

        $photos = collect($response->json('photos', []));
        if ($photos->isEmpty()) {
            return null;
        }

        $minimum = (int) ($config['min_width'] ?? 1200);

        // 1. First priority: photo >= minimum width (e.g. 1200px)
        $best = $photos->first(fn (array $photo) => ($photo['width'] ?? 0) >= $minimum);
        if ($best) {
            return $best;
        }

        // 2. Second priority: photo >= 600px
        $medium = $photos->first(fn (array $photo) => ($photo['width'] ?? 0) >= 600);
        if ($medium) {
            return $medium;
        }

        // 3. Fallback: take the first available photo
        return $photos->first();
    }

    /**
     * @param  array<string, mixed>  $photo
     */
    private function download(array $photo, Post $post): ?Media
    {
        $source = $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? null;

        if (! $source) {
            return null;
        }

        $temporary = null;

        try {
            $body = Http::timeout(30)->get($source)->throw()->body();

            $temporary = tempnam(sys_get_temp_dir(), 'stock').'.jpg';
            file_put_contents($temporary, $body);

            $media = $this->media->store(
                new UploadedFile($temporary, Str::slug(Str::limit($post->title, 60, '')).'.jpg', 'image/jpeg', null, true),
                $post->author,
                'stock',
            );

            // The credit line the page prints under the picture. Stored on the
            // media row so it travels with the file rather than being rebuilt
            // from an API that may not answer next time.
            $media->forceFill([
                'caption' => 'Photo by '.($photo['photographer'] ?? 'Pexels').' on Pexels',
                'alt_text' => Str::limit($photo['alt'] ?: $post->title, 120, ''),
            ])->save();

            return $media;
        } catch (\Throwable $e) {
            Log::warning('Stock photo could not be stored', ['post' => $post->id, 'error' => $e->getMessage()]);

            return null;
        } finally {
            if ($temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
