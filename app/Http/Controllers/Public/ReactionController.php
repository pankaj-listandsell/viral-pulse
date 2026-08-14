<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostReaction;
use App\Support\Fingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReactionController extends Controller
{
    private const ALLOWED = ['fire', 'insight', 'shock', 'love'];

    public function index(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()?->id;
        $ipHash = $userId ? null : Fingerprint::ip($request->ip());

        $userReaction = PostReaction::query()
            ->where('post_id', $post->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->whereNull('user_id')->where('ip_hash', $ipHash))
            ->value('reaction');

        $counts = PostReaction::query()
            ->where('post_id', $post->id)
            ->select('reaction', DB::raw('count(*) as count'))
            ->groupBy('reaction')
            ->pluck('count', 'reaction')
            ->all();

        $result = [];
        foreach (self::ALLOWED as $r) {
            $result[$r] = (int) ($counts[$r] ?? 0);
        }

        return response()->json([
            'reactions' => $result,
            'userReaction' => $userReaction,
        ]);
    }

    public function toggle(Request $request, Post $post): JsonResponse
    {
        $reaction = $request->string('reaction')->toString();
        if (! in_array($reaction, self::ALLOWED, true)) {
            return response()->json(['error' => 'Invalid reaction'], 422);
        }

        $userId = $request->user()?->id;
        $ipHash = $userId ? null : Fingerprint::ip($request->ip());

        $existing = PostReaction::query()
            ->where('post_id', $post->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->whereNull('user_id')->where('ip_hash', $ipHash))
            ->first();

        $activeReaction = null;

        DB::transaction(function () use ($existing, $post, $reaction, $userId, $ipHash, &$activeReaction): void {
            if ($existing) {
                if ($existing->reaction === $reaction) {
                    $existing->delete();
                    $activeReaction = null;
                } else {
                    $existing->update(['reaction' => $reaction]);
                    $activeReaction = $reaction;
                }
            } else {
                PostReaction::create([
                    'post_id' => $post->id,
                    'reaction' => $reaction,
                    'user_id' => $userId,
                    'ip_hash' => $ipHash,
                ]);
                $activeReaction = $reaction;
            }
        });

        $counts = PostReaction::query()
            ->where('post_id', $post->id)
            ->select('reaction', DB::raw('count(*) as count'))
            ->groupBy('reaction')
            ->pluck('count', 'reaction')
            ->all();

        $result = [];
        foreach (self::ALLOWED as $r) {
            $result[$r] = (int) ($counts[$r] ?? 0);
        }

        return response()->json([
            'reactions' => $result,
            'userReaction' => $activeReaction,
        ]);
    }
}
