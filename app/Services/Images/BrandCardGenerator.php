<?php

namespace App\Services\Images;

use App\Models\Media;
use App\Models\Post;
use App\Services\Images\Contracts\FeaturedImageGenerator;
use App\Services\MediaService;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Draws a branded card carrying the article's own headline.
 *
 * Chosen over generated photography on purpose. An AI picture attached to a
 * real event depicts something that never happened, which is misinformation
 * however good the intent - and Google watermarks its own output, so it is
 * detectable. A card states the headline and the section and claims nothing
 * else, costs nothing to produce, and cannot be wrong.
 */
class BrandCardGenerator implements FeaturedImageGenerator
{
    /** The size every social network crops to, so one image serves both jobs. */
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    /**
     * Horizontal margin, sized so nothing important is lost to a crop.
     *
     * This card is 1.9:1, and it is displayed in containers that are not: the
     * hero is 16:10 and the listing thumbnails are 16:9, both narrower, so
     * object-cover shaves the sides off. At the narrowest of those the visible
     * strip is 630 x 1.6 = 1008px, leaving 96px cut from each edge - which is
     * why the headline was arriving with its first letters missing.
     *
     * 96 would only just survive; the extra 40 is so the text still has a
     * margin once cropped rather than sitting flat against the edge.
     */
    private const MARGIN = 136;

    private const CANDIDATE_BLACK_FONTS = [
        'C:/Windows/Fonts/seguibl.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/ubuntu/Ubuntu-B.ttf',
    ];

    private const CANDIDATE_BOLD_FONTS = [
        'C:/Windows/Fonts/segoeuib.ttf',
        'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf',
    ];

    public function __construct(private readonly MediaService $media) {}

    public function name(): string
    {
        return 'Branded card';
    }

    public function generate(Post $post): ?Media
    {
        $blackFont = $this->blackFont();

        if (! function_exists('imagettftext') || ! $blackFont || ! is_file($blackFont)) {
            Log::warning('Cannot draw a brand card: GD FreeType or a compatible font is unavailable.');

            return null;
        }

        $temporary = null;

        try {
            $png = $this->draw($post);
            $temporary = tempnam(sys_get_temp_dir(), 'card').'.png';
            file_put_contents($temporary, $png);

            // Routed through MediaService rather than written straight to disk:
            // it re-encodes, derives the thumbnail sizes the cards need, and
            // records the row the media library and MediaResolver read.
            return $this->media->store(
                new UploadedFile($temporary, Str::slug(Str::limit($post->title, 60, '')).'.png', 'image/png', null, true),
                $post->author,
                'cards',
            );
        } catch (\Throwable $e) {
            Log::warning('Brand card generation failed', [
                'post' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            if ($temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function draw(Post $post): string
    {
        $accent = $this->rgb($post->category?->color ?? '#ef4444');

        $im = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagesavealpha($im, true);

        $this->background($im, $accent);

        // A bar in the section's colour, so categories are distinguishable at a
        // glance in a feed of cards.
        imagefilledrectangle($im, 0, 0, self::WIDTH, 9, imagecolorallocate($im, ...$accent));

        $this->brand($im);
        $this->category($im, $post, $accent);
        $this->headline($im, $post);
        $this->footer($im, $post);

        ob_start();
        imagepng($im, null, 9);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    /**
     * @param  array{0:int,1:int,2:int}  $accent
     */
    private function background($im, array $accent): void
    {
        // Near-black, warmed very slightly towards the section colour so the
        // card feels related to it without being a block of saturated hue.
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / (self::HEIGHT - 1);

            imagefilledrectangle($im, 0, $y, self::WIDTH, $y, imagecolorallocate(
                $im,
                (int) round(15 + $t * ($accent[0] * 0.13)),
                (int) round(20 + $t * ($accent[1] * 0.13)),
                (int) round(33 + $t * ($accent[2] * 0.13)),
            ));
        }
    }

    private function brand($im): void
    {
        $name = app(SettingsService::class)->get('site_name') ?: config('app.name');
        $logo = resource_path('brand/mark.png');

        $x = self::MARGIN;

        if (is_file($logo) && ($mark = @imagecreatefrompng($logo))) {
            imagealphablending($im, true);
            imagecopyresampled($im, $mark, $x, 62, 0, 0, 48, 48, imagesx($mark), imagesy($mark));
            imagedestroy($mark);
            $x += 64;
        }

        $this->text($im, $this->boldFont(), 27, $x, 95, [255, 255, 255], $name);
    }

    /**
     * @param  array{0:int,1:int,2:int}  $accent
     */
    private function category($im, Post $post, array $accent): void
    {
        $label = Str::upper($post->category?->name ?? 'Latest');
        $width = $this->width($this->boldFont(), 19, $label);
        $right = self::WIDTH - self::MARGIN;

        imagefilledrectangle($im, $right - $width - 28, 64, $right, 108, imagecolorallocate($im, ...$accent));
        $this->text($im, $this->boldFont(), 19, $right - $width - 14, 93, [255, 255, 255], $label);
    }

    /**
     * The headline, wrapped and shrunk until it fits the space it is given.
     */
    private function headline($im, Post $post): void
    {
        $maxWidth = self::WIDTH - (self::MARGIN * 2);
        $title = trim($post->title);

        // Three lines is the target, not the limit. A long headline set at the
        // largest size that merely fits ends up crowding the date; shrinking it
        // one step buys the whitespace back and still reads from a thumbnail.
        $size = 40;
        $blackFont = $this->blackFont();
        $lines = $this->wrap($blackFont, $size, $title, $maxWidth);

        foreach ([64, 58, 52, 46, 40] as $candidate) {
            $wrapped = $this->wrap($blackFont, $candidate, $title, $maxWidth);

            if (count($wrapped) <= 3) {
                $size = $candidate;
                $lines = $wrapped;

                break;
            }
        }

        $lines = array_slice($lines, 0, 4);
        $leading = (int) round($size * 1.24);
        $y = (int) round((self::HEIGHT / 2) - ((count($lines) - 1) * $leading / 2) + $size * 0.34);

        foreach ($lines as $line) {
            $this->text($im, $blackFont, $size, self::MARGIN, $y, [255, 255, 255], $line);
            $y += $leading;
        }
    }

    private function footer($im, Post $post): void
    {
        $date = ($post->published_at ?? $post->created_at ?? now())->format('j F Y');
        $reading = $post->reading_time ? " · {$post->reading_time} min read" : '';

        $this->text($im, $this->boldFont(), 22, self::MARGIN, self::HEIGHT - 62, [148, 163, 184], $date.$reading);
    }

    /**
     * @return array<int, string>
     */
    private function wrap(string $font, float $size, string $text, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if ($current !== '' && $this->width($font, $size, $candidate) > $maxWidth) {
                $lines[] = $current;
                $current = $word;

                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [$text];
    }

    private function width(string $font, float $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return (int) abs($box[2] - $box[0]);
    }

    /**
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function text($im, string $font, float $size, int $x, int $y, array $rgb, string $text): void
    {
        imagettftext($im, $size, 0, $x, $y, imagecolorallocate($im, ...$rgb), $font, $text);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = 'ef4444';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function blackFont(): ?string
    {
        foreach (self::CANDIDATE_BLACK_FONTS as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function boldFont(): string
    {
        foreach (self::CANDIDATE_BOLD_FONTS as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return $this->blackFont() ?? self::CANDIDATE_BOLD_FONTS[0];
    }
}
