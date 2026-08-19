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
 * Draws a decorative illustration with Imagen.
 *
 * Reachable only from the sections that opt in, and those sections are the
 * ones where nothing factual is being depicted - a horoscope, a quiz, a
 * devotional piece. An AI picture attached to a news event depicts something
 * that never happened, which is misinformation however good the intent, so
 * news never reaches this class.
 *
 * The prompt forbids text, logos, real people and anything photographic. What
 * comes back should read as a drawing at a glance, and the page labels it as
 * one, so no reader can mistake it for a photograph of something.
 */
class AiIllustrationGenerator implements FeaturedImageGenerator
{
    public const CREDIT = 'Illustration generated with AI';

    private const STYLE = 'Flat vector editorial illustration, bold simple shapes, generous negative space, '
        .'limited palette, soft gradients, subtle grain. No text, no letters, no numbers, no watermarks, '
        .'no logos, no real or recognisable people, no faces, not photographic, not photorealistic.';

    public function __construct(private readonly MediaService $media) {}

    public function name(): string
    {
        return 'AI illustration';
    }

    public function generate(Post $post): ?Media
    {
        $config = config('site.media.illustration');

        if (blank($config['key'] ?? null)) {
            return null;
        }

        $image = $this->request($post, $config);

        return $image ? $this->store($image, $post) : null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function request(Post $post, array $config): ?string
    {
        $url = rtrim($config['endpoint'], '/')."/models/{$config['model']}:predict";

        try {
            $response = Http::timeout(60)
                // Key in a header, not the query string: URLs end up in proxy
                // logs and browser history.
                ->withHeaders(['x-goog-api-key' => $config['key']])
                ->asJson()
                ->post($url, [
                    'instances' => [['prompt' => $this->prompt($post)]],
                    'parameters' => [
                        'sampleCount' => 1,
                        'aspectRatio' => '16:9',
                        // Refuses anything depicting a real, identifiable
                        // person, which is the failure mode that matters here.
                        'personGeneration' => 'dont_allow',
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Imagen could not be reached', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Imagen request failed', [
                'status' => $response->status(),
                'post' => $post->id,
                'body' => Str::limit((string) $response->body(), 300),
            ]);

            return null;
        }

        $encoded = $response->json('predictions.0.bytesBase64Encoded');

        return $encoded ? (base64_decode($encoded, true) ?: null) : null;
    }

    private function prompt(Post $post): string
    {
        $subject = $post->tags->isNotEmpty()
            ? $post->tags->pluck('name')->take(3)->implode(', ')
            : Str::limit($post->title, 90, '');

        $section = $post->category?->name ?? 'general interest';

        return "An illustration for a {$section} article about: {$subject}. ".self::STYLE;
    }

    private function store(string $binary, Post $post): ?Media
    {
        $temporary = null;

        try {
            $temporary = tempnam(sys_get_temp_dir(), 'illus').'.png';
            file_put_contents($temporary, $binary);

            $media = $this->media->store(
                new UploadedFile($temporary, Str::slug(Str::limit($post->title, 60, '')).'.png', 'image/png', null, true),
                $post->author,
                'illustrations',
            );

            // Disclosure travels with the file. A label the page forgets to
            // print is not a disclosure.
            $media->forceFill([
                'caption' => self::CREDIT,
                'alt_text' => 'Illustration for '.Str::limit($post->title, 100, ''),
            ])->save();

            return $media;
        } catch (\Throwable $e) {
            Log::warning('AI illustration could not be stored', ['post' => $post->id, 'error' => $e->getMessage()]);

            return null;
        } finally {
            if ($temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
