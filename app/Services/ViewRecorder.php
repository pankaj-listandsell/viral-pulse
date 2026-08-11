<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use App\Support\Fingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ViewRecorder
{
    /** How long before the same visitor counts again for the same post. */
    private const DEDUPE_MINUTES = 360;

    /**
     * Counts a read.
     *
     * Bots are skipped so the numbers mean something, repeat reads inside the
     * dedupe window are ignored, and a failure here is logged rather than
     * allowed to break the article page.
     */
    public function record(Post $post, Request $request): void
    {
        $device = $this->device($request);

        if ($device === 'bot' || $request->user()?->canAccessAdminPanel()) {
            return;
        }

        $ipHash = Fingerprint::ip($request->ip());
        $cacheKey = "view.{$post->id}.{$ipHash}";

        // add() is atomic, so two concurrent requests cannot both pass.
        if (! Cache::add($cacheKey, true, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return;
        }

        try {
            PostView::create([
                'post_id' => $post->id,
                'user_id' => $request->user()?->id,
                'ip_hash' => $ipHash,
                'user_agent_hash' => Fingerprint::userAgent($request->userAgent()),
                'referrer' => Str::limit($request->headers->get('referer') ?? '', 250, '') ?: null,
                'device' => $device,
                'viewed_at' => now(),
            ]);

            // Denormalised counter, reconciled from post_views by the nightly
            // command so a lost increment cannot drift forever.
            $post->newQuery()->whereKey($post->id)->increment('views_count');
        } catch (\Throwable $e) {
            Log::warning('View recording failed', ['post' => $post->id, 'error' => $e->getMessage()]);
        }
    }

    private function device(Request $request): string
    {
        $agent = Str::lower($request->userAgent() ?? '');

        if ($agent === '' || Str::contains($agent, [
            'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'embedly',
            'preview', 'headless', 'lighthouse', 'pingdom', 'curl', 'wget', 'python-requests',
        ])) {
            return 'bot';
        }

        if (Str::contains($agent, ['ipad', 'tablet', 'kindle', 'playbook'])) {
            return 'tablet';
        }

        if (Str::contains($agent, ['mobi', 'android', 'iphone', 'ipod'])) {
            return 'mobile';
        }

        return 'desktop';
    }
}
