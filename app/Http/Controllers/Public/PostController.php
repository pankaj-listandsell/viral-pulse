<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostSlugHistory;
use App\Services\ContentFeedService;
use App\Services\SeoService;
use App\Services\ViewRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        private readonly ContentFeedService $feed,
        private readonly SeoService $seo,
        private readonly ViewRecorder $views,
    ) {}

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $post = Post::where('slug', $slug)->first();

        if (! $post) {
            return $this->redirectRenamed($slug);
        }

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

    /**
     * A renamed article keeps its old URL working with a 301.
     *
     * Without this, every link and every ranking the old slug had earned is
     * thrown away the moment an editor tidies up a headline.
     */
    private function redirectRenamed(string $slug): RedirectResponse
    {
        $postId = PostSlugHistory::where('slug', $slug)->value('post_id');
        $post = $postId ? Post::published()->find($postId) : null;

        abort_unless($post, 404);

        return redirect()->route('posts.show', $post->slug, 301);
    }
}
