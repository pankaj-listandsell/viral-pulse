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

    public function __construct(private readonly MediaService $media) {}

    public function name(): string
    {
        return 'Stock photo';
    }

    public function generate(Post $post): ?Media
    {
        $config = config('site.media.stock');

        if (blank($config['key'] ?? null)) {
            return null;
        }

        $query = $this->query($post);

        if ($query === '') {
            return null;
        }

        $photo = $this->search($query, $config);

        if (! $photo) {
            return null;
        }

        return $this->download($photo, $post);
    }

    /**
     * The subject of the story, in at most three words.
     *
     * Tags first: an editor tagging a post has already decided what it is
     * about, which is a better signal than anything derived from a headline.
     */
    private function query(Post $post): string
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

        $minimum = (int) ($config['min_width'] ?? 1200);

        return collect($response->json('photos', []))
            ->first(fn (array $photo) => ($photo['width'] ?? 0) >= $minimum);
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
