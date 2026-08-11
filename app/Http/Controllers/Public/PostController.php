<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ContentFeedService;
use App\Services\SeoService;
use App\Services\ViewRecorder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SeoService $seo,
        private readonly ViewRecorder $views,
    ) {}

    public function show(Request $request, Post $post): View
    {
        $isLive = $post->status->isPubliclyVisible() && $post->published_at?->isPast();

        // Drafts and scheduled posts are 404 for visitors, but an admin can
        // still open one to preview it before it goes live.
        abort_unless($isLive || $request->user()?->canAccessAdminPanel(), 404);

        $post->load(['author:id,name,bio,avatar', 'category:id,name,slug,color', 'tags:id,name,slug']);

        $this->feed->withImages([$post]);
        $this->views->record($post, $request);

        return view('public.posts.show', [
            'post' => $post,
            'related' => $this->feed->related($post),
            'popular' => $this->feed->popular(),
            'seo' => $this->seo->forPost($post),
        ]);
    }
}
