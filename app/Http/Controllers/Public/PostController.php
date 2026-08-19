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

        // The author relation is deliberately not loaded: nothing public names
        // a person any more, so fetching one is a query for nobody.
        $post->load(['category:id,name,slug,color', 'tags:id,name,slug']);

        $this->feed->withImages([$post]);
        $this->views->record($post, $request);

        return view('public.posts.show', [
            'post' => $post,
            'related' => $this->feed->related($post),
            'popular' => $this->feed->popular(),
            // Reading order, so an article is never a leaf: every story links
            // on to the one before and after it, which is how a crawler walks
            // the whole archive from any single entry point.
            'previousPost' => $this->neighbour($post, 'previous'),
            'nextPost' => $this->neighbour($post, 'next'),
            'seo' => $this->seo->forPost($post),
        ]);
    }

    /**
     * The story published just before or just after this one.
     */
    private function neighbour(Post $post, string $direction): ?Post
    {
        if (! $post->published_at) {
            return null;
        }

        return $this->feed->base()
            ->whereKeyNot($post->id)
            ->when(
                $direction === 'previous',
                fn ($query) => $query->where('published_at', '<', $post->published_at)->orderByDesc('published_at'),
                fn ($query) => $query->where('published_at', '>', $post->published_at)->orderBy('published_at'),
            )
            ->first();
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
