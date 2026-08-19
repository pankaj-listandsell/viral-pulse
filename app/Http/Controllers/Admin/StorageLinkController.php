<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Repairs the public/storage link from the admin panel.
 *
 * `php artisan storage:link` is the normal way to do this and it needs a
 * shell. On shared hosting the terminal is often disabled, and the symptom is
 * unpleasant: uploads succeed, the media library lists them, and every new
 * picture 404s - because an FTP copy turned the symlink into a real directory
 * holding a stale copy of whatever was in it at the time.
 *
 * Opening the page repairs the link if it is broken, so there is nothing to
 * click. That is a GET doing work, which is normally wrong; it is acceptable
 * here because the operation is idempotent, never deletes anything, and sits
 * behind the admin login.
 *
 * Deliberately not a general command runner. A route that executes arbitrary
 * artisan input is a remote shell with a login form in front of it; this one
 * does a single, known, reversible thing.
 */
class StorageLinkController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function show(): View
    {
        $before = $this->diagnose();

        // Already correct: look, report, change nothing.
        $result = $before['points_correctly'] ? null : $this->createLink();

        return view('admin.maintenance.storage-link', [
            'report' => $this->diagnose(),
            'result' => $result,
        ]);
    }

    /**
     * Forces the link to be rebuilt even when it looks fine.
     */
    public function repair(): RedirectResponse
    {
        $result = $this->createLink();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('moved', $result['moved']);
    }

    /**
     * @return array{ok: bool, message: string, moved: string|null}
     */
    private function createLink(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        $moved = null;

        if (! is_dir($target) && ! @mkdir($target, 0755, true) && ! is_dir($target)) {
            return ['ok' => false, 'message' => "The target folder does not exist and could not be created: {$target}", 'moved' => null];
        }

        // A real directory is moved aside rather than deleted. It may hold the
        // only copy of files uploaded before the link broke, and no maintenance
        // page should be able to destroy those.
        if (is_link($link)) {
            @unlink($link);
        } elseif (is_dir($link)) {
            $moved = 'storage-backup-'.now()->format('YmdHis');

            if (! @rename($link, public_path($moved))) {
                return ['ok' => false, 'message' => "Could not move the existing public/storage folder aside. Check its permissions.", 'moved' => null];
            }
        }

        if (! function_exists('symlink')) {
            return ['ok' => false, 'message' => 'PHP has symlink() disabled on this server. Ask the host to run: php artisan storage:link', 'moved' => $moved];
        }

        if (! @symlink($target, $link)) {
            return ['ok' => false, 'message' => 'symlink() failed - the host may forbid it. Ask them to run: php artisan storage:link', 'moved' => $moved];
        }

        $this->logger->log('storage.link_repaired', null, 'Recreated the public/storage link');

        return [
            'ok' => true,
            'message' => 'The link was created. Upload a picture to confirm it now displays.',
            'moved' => $moved,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnose(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        return [
            'link_path' => $link,
            'target_path' => $target,
            'exists' => file_exists($link),
            'is_symlink' => is_link($link),
            'points_to' => is_link($link) ? (readlink($link) ?: '?') : null,
            'points_correctly' => is_link($link) && realpath(readlink($link)) === realpath($target),
            'target_exists' => is_dir($target),
            'target_writable' => is_dir($target) && is_writable($target),
            'disk' => config('site.media.disk'),
            'disk_root' => config('filesystems.disks.'.config('site.media.disk').'.root'),
            'free_space' => @disk_free_space(base_path()),
            // Proves the whole path end to end: write a file through the disk
            // the uploader uses, then offer its URL to open.
            'probe_url' => $this->probe(),
        ];
    }

    private function probe(): ?string
    {
        try {
            $disk = Storage::disk(config('site.media.disk'));
            $path = 'health/link-probe.txt';

            $disk->put($path, 'ok '.now()->toDateTimeString(), 'public');

            return $disk->exists($path) ? $disk->url($path) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
