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
 * Draws an illustration of the story's subject with FLUX on Cloudflare
 * Workers AI.
 *
 * Chosen because its free daily allowance covers a site's whole output with
 * room to spare, where the Gemini and OpenAI image tiers ran dry within a day.
 *
 * The prompt asks for an illustration of the subject, never a scene from the
 * event: no faces, no real people, nothing photographic. A drawing of a gas
 * cylinder beside a fingerprint illustrates an LPG story; a "photo" of the
 * summit would show something that never happened. The caption labels it as
 * generated, like every other AI picture on the site.
 */
class CloudflareImageGenerator implements FeaturedImageGenerator
{
    /**
     * FLUX has no negative prompt, and naming a thing - "no text" - tends to
     * summon it. So the style describes what the picture is instead: a few
     * plain, unlabelled objects on an empty background.
     */
    private const STYLE = 'Modern flat vector editorial illustration in the style of a magazine spot graphic. '
        .'Close-up of two or three large, simple, iconic objects that represent the subject, '
        .'centred and filling most of the frame. '
        .'Plain smooth background in one soft colour. Blank, unlabelled surfaces. '
        .'Bold geometric shapes, rich harmonious colours, subtle shadows, crisp and minimal. '
        .'Objects and symbols only, faceless and anonymous.';

    /**
     * What to draw for sections whose headlines name nothing drawable.
     * "Daily Horoscope Predictions for All 12 Signs" alone came back as a
     * shelf of vases; the section has to say what the picture is of.
     */
    private const SECTION_HINTS = [
        'news' => 'world globe, press microphones on a podium and official documents',
        'trending' => 'world globe, press microphones and a smartphone with notifications',
        'business' => 'rising bar charts, coins and a briefcase',
        'technology' => 'smartphone, laptop and glowing circuit lines',
        'sports' => 'ball, trophy and stadium floodlights',
        'entertainment' => 'film reel, clapperboard and stage spotlights',
        'lifestyle' => 'coffee cup, plants and everyday home objects',
        'travel' => 'suitcase, airplane and landmark silhouettes',
        'astrology' => 'zodiac wheel, glowing constellations and planets in a deep night sky',
        'devotional' => 'temple bells, diya oil lamps and marigold flowers',
        'quiz-fun' => 'puzzle pieces, light bulb and question shapes in playful colours',
        'health' => 'stethoscope, heart shape and fresh fruit',
        'education' => 'books, graduation cap and pencil',
    ];

    public function __construct(private readonly MediaService $media) {}

    public function name(): string
    {
        return 'Cloudflare AI illustration';
    }

    public function generate(Post $post): ?Media
    {
        $config = config('site.media.cloudflare');
        // A token saved on the admin keys screen reaches the text provider's
        // config; the illustrations use the same Cloudflare account.
        $config['token'] = ($config['token'] ?? null) ?: config('ai.providers.cloudflare.key');

        if (blank($config['account_id'] ?? null) || blank($config['token'] ?? null)) {
            return null;
        }

        $post->loadMissing(['tags', 'category', 'author']);

        $image = $this->request($post, $config);

        return $image ? $this->store($image, $post) : null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function request(Post $post, array $config): ?string
    {
        $url = "https://api.cloudflare.com/client/v4/accounts/{$config['account_id']}/ai/run/{$config['model']}";

        try {
            $response = Http::timeout(90)
                // Workers AI now and then rejects a prompt it accepts a second
                // later, so one retry before handing over to the next strategy.
                ->retry(2, 1500, throw: false)
                ->withToken($config['token'])
                ->asJson()
                ->post($url, [
                    'prompt' => $this->prompt($post),
                    // schnell is distilled for few steps; past 4 the picture
                    // barely changes and the free allowance goes faster.
                    'steps' => (int) ($config['steps'] ?? 4),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Cloudflare Workers AI could not be reached', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Cloudflare image request failed', [
                'status' => $response->status(),
                'post' => $post->id,
                'body' => Str::limit((string) $response->body(), 300),
            ]);

            return null;
        }

        $encoded = $response->json('result.image');

        return $encoded ? (base64_decode($encoded, true) ?: null) : null;
    }

    public function prompt(Post $post): string
    {
        $subject = $this->subject($post->title);

        $tags = $post->tags->pluck('name')->take(4)->implode(', ');
        $section = $post->category?->name ?? 'general interest';
        $hint = self::SECTION_HINTS[$post->category?->slug] ?? null;

        $prompt = "Concept illustration for a {$section} story: {$subject}.";

        if ($show = implode(', ', array_filter([$hint, $tags]))) {
            $prompt .= " Show: {$show}.";
        }

        return self::STYLE.' '.$prompt;
    }

    /**
     * The headline's main clause, without the wording that makes FLUX draw a
     * poster: "Namibia vs South Africa: Head-to-Head Record" came back as a
     * match poster with an invented caption. What is left names the subject.
     */
    private function subject(string $title): string
    {
        $main = trim(Str::before($title, ':')) ?: $title;

        $main = preg_replace('/\s+(vs\.?|v\.?|versus)\s+/i', ' and ', $main);

        // Lower case, so it reads as a topic rather than a title to typeset:
        // a capitalised headline came back printed under the picture, misspelt.
        return Str::lower(Str::limit(trim($main), 120, ''));
    }

    private function store(string $binary, Post $post): ?Media
    {
        $temporary = null;

        try {
            $temporary = tempnam(sys_get_temp_dir(), 'cfai').'.jpg';
            file_put_contents($temporary, $binary);

            $media = $this->media->store(
                new UploadedFile($temporary, Str::slug(Str::limit($post->title, 60, '')).'.jpg', 'image/jpeg', null, true),
                $post->author,
                'illustrations',
            );

            $media->forceFill([
                'caption' => AiIllustrationGenerator::CREDIT,
                'alt_text' => 'Illustration for '.Str::limit($post->title, 100, ''),
            ])->save();

            return $media;
        } catch (\Throwable $e) {
            Log::warning('Cloudflare illustration could not be stored', ['post' => $post->id, 'error' => $e->getMessage()]);

            return null;
        } finally {
            if ($temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
