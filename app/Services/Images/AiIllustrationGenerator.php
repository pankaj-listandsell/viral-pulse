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
        $openAiKey = config('ai.providers.openai.key') ?: env('OPENAI_API_KEY');
        if (filled($openAiKey)) {
            $image = $this->requestOpenAi($post, $openAiKey);
            if ($image) {
                return $this->store($image, $post);
            }
        }

        $config = config('site.media.illustration');
        $key = config('ai.providers.gemini.key') ?: ($config['key'] ?? null);

        if (blank($key)) {
            return null;
        }

        $config['key'] = $key;

        $image = $this->request($post, $config);

        return $image ? $this->store($image, $post) : null;
    }

    private function requestOpenAi(Post $post, string $key): ?string
    {
        $url = 'https://api.openai.com/v1/images/generations';

        try {
            $response = Http::timeout(60)
                ->withToken($key)
                ->asJson()
                ->post($url, [
                    'model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1-mini'),
                    'prompt' => $this->prompt($post),
                    'n' => 1,
                    'size' => '1024x1024',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenAI DALL-E could not be reached', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('OpenAI DALL-E request failed', [
                'status' => $response->status(),
                'post' => $post->id,
                'body' => Str::limit((string) $response->body(), 300),
            ]);

            return null;
        }

        $encoded = $response->json('data.0.b64_json');
        if (filled($encoded)) {
            return base64_decode($encoded, true) ?: null;
        }

        $imageUrl = $response->json('data.0.url');
        if (filled($imageUrl)) {
            try {
                $imageResponse = Http::timeout(30)->get($imageUrl);
                return $imageResponse->successful() ? $imageResponse->body() : null;
            } catch (\Throwable $e) {
                Log::warning('Could not download generated DALL-E image', ['url' => $imageUrl, 'error' => $e->getMessage()]);
                return null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function request(Post $post, array $config): ?string
    {
        $url = rtrim($config['endpoint'], '/')."/models/{$config['model']}:generateContent";

        try {
            $response = Http::timeout(60)
                // Key in a header, not the query string: URLs end up in proxy
                // logs and browser history.
                ->withHeaders(['x-goog-api-key' => $config['key']])
                ->asJson()
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $this->prompt($post)]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['IMAGE']
                    ]
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

        $encoded = $response->json('candidates.0.content.parts.0.inlineData.data');

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
