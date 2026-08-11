<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostLike;
use App\Services\SettingsService;
use App\Support\Fingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Toggles a like. Guests are identified by a salted IP hash and members by
     * their id; the unique indexes on post_likes make a double-like impossible
     * even if two requests race.
     */
    public function toggle(Request $request, Post $post): JsonResponse
    {
        abort_unless($this->settings->bool('likes_enabled', true), 404);
        abort_unless($post->status->isPubliclyVisible(), 404);

        $userId = $request->user()?->id;
        $ipHash = $userId ? null : Fingerprint::ip($request->ip());

        $existing = PostLike::query()
            ->where('post_id', $post->id)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when(! $userId, fn ($query) => $query->whereNull('user_id')->where('ip_hash', $ipHash))
            ->first();

        DB::transaction(function () use ($existing, $post, $userId, $ipHash): void {
            if ($existing) {
                $existing->delete();
            } else {
                // created_at is managed by Eloquent; UPDATED_AT is null on
                // this model because a like is never edited.
                PostLike::create([
                    'post_id' => $post->id,
                    'user_id' => $userId,
                    'ip_hash' => $ipHash,
                ]);
            }

            // Recounted rather than incremented so the column cannot drift.
            $post->newQuery()->whereKey($post->id)->update([
                'likes_count' => PostLike::where('post_id', $post->id)->count(),
            ]);
        });

        return response()->json([
            'liked' => ! $existing,
            'count' => (int) $post->newQuery()->whereKey($post->id)->value('likes_count'),
        ]);
    }
}
