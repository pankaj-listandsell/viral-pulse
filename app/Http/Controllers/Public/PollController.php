<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostPoll;
use App\Support\Fingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollController extends Controller
{
    private const DEFAULT_OPTIONS = ['yes', 'no', 'neutral'];

    public function index(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()?->id;
        $ipHash = $userId ? null : Fingerprint::ip($request->ip());

        $userVote = PostPoll::query()
            ->where('post_id', $post->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->whereNull('user_id')->where('ip_hash', $ipHash))
            ->value('option');

        return response()->json($this->getPollData($post, $userVote));
    }

    public function vote(Request $request, Post $post): JsonResponse
    {
        $option = $request->string('option')->toString();
        if (! in_array($option, self::DEFAULT_OPTIONS, true)) {
            return response()->json(['error' => 'Invalid option'], 422);
        }

        $userId = $request->user()?->id;
        $ipHash = $userId ? null : Fingerprint::ip($request->ip());

        $existing = PostPoll::query()
            ->where('post_id', $post->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->whereNull('user_id')->where('ip_hash', $ipHash))
            ->first();

        if (! $existing) {
            PostPoll::create([
                'post_id' => $post->id,
                'option' => $option,
                'user_id' => $userId,
                'ip_hash' => $ipHash,
            ]);
            $userVote = $option;
        } else {
            $userVote = $existing->option;
        }

        return response()->json($this->getPollData($post, $userVote));
    }

    private function getPollData(Post $post, ?string $userVote): array
    {
        $counts = PostPoll::query()
            ->where('post_id', $post->id)
            ->selectRaw('`option`, count(*) as count')
            ->groupBy('option')
            ->pluck('count', 'option')
            ->all();

        $total = array_sum($counts);

        $optionsData = [
            [
                'id' => 'yes',
                'text' => '👍 Yes, Agree',
                'count' => (int) ($counts['yes'] ?? 0),
                'percent' => $total > 0 ? (int) round((($counts['yes'] ?? 0) / $total) * 100) : 0,
            ],
            [
                'id' => 'no',
                'text' => '👎 No, Disagree',
                'count' => (int) ($counts['no'] ?? 0),
                'percent' => $total > 0 ? (int) round((($counts['no'] ?? 0) / $total) * 100) : 0,
            ],
            [
                'id' => 'neutral',
                'text' => '🤔 Neutral / Need More Info',
                'count' => (int) ($counts['neutral'] ?? 0),
                'percent' => $total > 0 ? (int) round((($counts['neutral'] ?? 0) / $total) * 100) : 0,
            ],
        ];

        return [
            'total' => $total,
            'options' => $optionsData,
            'userVote' => $userVote,
        ];
    }
}
