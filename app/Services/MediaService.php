<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;

class MediaService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Stores an upload and derives its responsive sizes.
     *
     * The file is re-encoded rather than copied: a JPEG carrying a PHP payload
     * in its metadata comes out the other side as a clean image, and EXIF
     * (including GPS coordinates) is dropped along the way.
     */
    public function store(UploadedFile $file, ?User $user = null, ?string $folder = null): Media
    {
        $this->assertAllowed($file);

        $disk = config('site.media.disk', 'public');
        $extension = $this->targetExtension($file);
        $filename = Str::lower(Str::random(24)).'.'.$extension;
        $directory = trim('media/'.date('Y/m').($folder ? '/'.Str::slug($folder) : ''), '/');
        $path = $directory.'/'.$filename;

        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());

        $width = $image->width();
        $height = $image->height();

        // put() returns false rather than throwing on this disk, and the row
        // below would be created anyway: the library then lists a picture that
        // does not exist, with nothing in the log to say why. A full quota and
        // an unwritable storage directory both look exactly like this, so the
        // write is checked and the reason surfaced instead.
        $written = Storage::disk($disk)->put($path, (string) $this->encode($image, $extension), 'public');

        if (! $written || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException(
                "The image could not be saved to storage/app/public/{$directory}. "
                .'Check that the storage directory is writable and that the disk quota is not full.'
            );
        }

        $media = Media::create([
            'user_id' => $user?->id,
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $extension === 'webp' ? 'image/webp' : $file->getMimeType(),
            'extension' => $extension,
            'size' => Storage::disk($disk)->size($path),
            'width' => $width,
            'height' => $height,
            'folder' => $folder,
            'conversions' => $this->makeConversions($manager, $file, $directory, $filename, $disk),
        ]);

        $this->logger->log('media.uploaded', $media, "Uploaded {$media->original_name}");

        return $media;
    }

    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $name = $media->original_name;

        $disk->delete($media->path);

        foreach ($media->conversions ?? [] as $conversion) {
            if (! empty($conversion['path'])) {
                $disk->delete($conversion['path']);
            }
        }

        $media->delete();

        $this->logger->log('media.deleted', $media, "Deleted {$name}");
    }

    /**
     * Validation happens on the sniffed MIME type and the real image
     * dimensions, never on the filename: "payload.php.jpg" and a renamed
     * archive both fail here.
     */
    private function assertAllowed(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The upload did not complete.');
        }

        $mime = $file->getMimeType();

        if (! in_array($mime, config('site.media.allowed_mimes', []), true)) {
            throw new RuntimeException('That file type is not allowed.');
        }

        if (@getimagesize($file->getRealPath()) === false) {
            throw new RuntimeException('That file is not a readable image.');
        }
    }

    private function targetExtension(UploadedFile $file): string
    {
        // Animated GIFs lose their animation when re-encoded, so they keep
        // their original format; everything else becomes WebP when enabled.
        if ($file->getMimeType() === 'image/gif') {
            return 'gif';
        }

        return config('site.media.webp', true) ? 'webp' : 'jpg';
    }

    private function encode(ImageInterface $image, string $extension): mixed
    {
        return match ($extension) {
            'webp' => $image->toWebp(82),
            'gif' => $image->toGif(),
            default => $image->toJpeg(85),
        };
    }

    /**
     * Smaller variants for srcset. A failure here must not lose the original
     * upload, so it is logged and skipped rather than thrown.
     *
     * @return array<string, array{path: string, width: int, height: int}>
     */
    private function makeConversions(
        ImageManager $manager,
        UploadedFile $file,
        string $directory,
        string $filename,
        string $disk,
    ): array {
        $conversions = [];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);

        foreach (config('site.media.conversions', []) as $name => $size) {
            try {
                $variant = $manager->read($file->getRealPath())
                    ->scaleDown(width: $size['width'], height: $size['height']);

                $variantPath = "{$directory}/{$base}-{$name}.{$extension}";

                Storage::disk($disk)->put($variantPath, (string) $this->encode($variant, $extension), 'public');

                $conversions[$name] = [
                    'path' => $variantPath,
                    'width' => $variant->width(),
                    'height' => $variant->height(),
                ];
            } catch (\Throwable $e) {
                Log::warning('Media conversion failed', [
                    'conversion' => $name,
                    'file' => $filename,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $conversions;
    }
}
