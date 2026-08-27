<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PostController extends Controller
{
    /**
     * Columns the list may be ordered by, and which way each one starts.
     *
     * A whitelist rather than a passthrough: the key arrives in the query
     * string and ends up in an ORDER BY, and anything not named here is
     * ignored rather than trusted.
     *
     * The starting direction is the one a reader means by "sort by this" -
     * newest first for a date, most-read first for a view count, A to Z for a
     * name. Getting that wrong costs a second click every time.
     */
    private const SORTS = [
        'title' => ['column' => 'title', 'default' => 'asc'],
        'category' => ['column' => 'category', 'default' => 'asc'],
        'status' => ['column' => 'status', 'default' => 'asc'],
        'views' => ['column' => 'views_count', 'default' => 'desc'],
        'created' => ['column' => 'created_at', 'default' => 'desc'],
        'updated' => ['column' => 'updated_at', 'default' => 'desc'],
    ];

    /**
     * Newest first, by the date the post was written.
     *
     * The list used to open on updated_at, which meant editing a typo in a post
     * from March pushed it above everything published since. Creation order is
     * the one that matches how an editor thinks about their own archive.
     */
    private const DEFAULT_SORT = 'created';

    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request);

        $posts = Post::query()
            ->with(['author:id,name', 'category:id,name,color'])
            ->when($request->filled('search'), fn ($query) => $query->search($request->string('search')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('source'), function ($query) use ($request) {
                return $request->string('source')->toString() === 'ai'
                    ? $query->where('ai_generated', true)
                    : $query->where('ai_generated', false);
            })
            ->when($request->boolean('trashed'), fn ($query) => $query->onlyTrashed())
            // Written on or after this date, and on or before that one. Both
            // ends are inclusive and compared as dates, so "to 20 August"
            // includes everything written that day rather than stopping at
            // midnight and hiding the whole day's work.
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->tap(fn ($query) => $this->applySort($query, $sort, $direction))
            ->paginate(20)
            ->withQueryString();

        app(\App\Services\MediaResolver::class)->preloadForPosts($posts);

        return view('admin.posts.index', [
            'posts' => $posts,
            'categories' => Category::ordered()->get(['id', 'name']),
            'statusCounts' => Post::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'trashedCount' => Post::onlyTrashed()->count(),
            'filters' => $request->only('search', 'status', 'category', 'source', 'trashed', 'from', 'to'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * The column and direction to order by, taken from the request and
     * clamped to what is actually sortable.
     *
     * @return array{0: string, 1: string}
     */
    private function sortFrom(Request $request): array
    {
        $sort = $request->string('sort')->toString();
        $sort = isset(self::SORTS[$sort]) ? $sort : self::DEFAULT_SORT;

        $direction = strtolower($request->string('direction')->toString());

        // An unrecognised direction falls back to the column's own default
        // rather than to a fixed one, so a bare ?sort=title still opens A-Z.
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = self::SORTS[$sort]['default'];
        }

        return [$sort, $direction];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Post>  $query
     */
    private function applySort($query, string $sort, string $direction): void
    {
        // Category lives on another table. A correlated subquery sorts by its
        // name without a join, which would otherwise have to be a left join to
        // keep uncategorised posts in the list at all.
        if ($sort === 'category') {
            $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'posts.category_id')->limit(1),
                $direction,
            );
        } else {
            $query->orderBy(self::SORTS[$sort]['column'], $direction);
        }

        // A stable tiebreaker. Without it two posts written in the same second
        // - which the seeder and the bulk importer both produce - can swap
        // places between page 1 and page 2 and appear twice or not at all.
        $query->orderBy('id', 'desc');
    }

    public function create(): View
    {
        return view('admin.posts.create', [
            'post' => new Post(['status' => PostStatus::Draft, 'language' => 'en']),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'selectedTags' => [],
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $post = $this->posts->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Post created.');
    }

    public function edit(Post $post): View
    {
        $post->load('tags:id,name');

        return view('admin.posts.edit', [
            'post' => $post,
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'selectedTags' => $post->tags->pluck('name')->all(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->posts->update($post, $request->validated());

        return back()->with('success', 'Post saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->posts->delete($post);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post moved to trash.');
    }

    public function restore(int $post): RedirectResponse
    {
        Post::onlyTrashed()->findOrFail($post)->restore();

        return back()->with('success', 'Post restored.');
    }

    public function forceDelete(int $post): RedirectResponse
    {
        Post::onlyTrashed()->findOrFail($post)->forceDelete();

        return back()->with('success', 'Post permanently deleted.');
    }

    public function duplicate(Post $post): RedirectResponse
    {
        $copy = $this->posts->duplicate($post);

        return redirect()
            ->route('admin.posts.edit', $copy)
            ->with('success', 'Duplicated. This copy is a draft.');
    }

    public function publish(Post $post): RedirectResponse
    {
        $this->posts->publish($post);

        return back()->with('success', 'Post published.');
    }

    public function unpublish(Post $post): RedirectResponse
    {
        $this->posts->unpublish($post);

        return back()->with('success', 'Post moved back to draft.');
    }

    public function archive(Post $post): RedirectResponse
    {
        $this->posts->archive($post);

        return back()->with('success', 'Post archived.');
    }

    public function schedule(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $this->posts->schedule($post, Carbon::parse($validated['scheduled_at']));

        return back()->with('success', 'Post scheduled.');
    }

    /**
     * Bulk actions from the index checkboxes. Deliberately limited to the
     * reversible ones - permanent deletion is never a bulk operation.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:publish,unpublish,archive,delete'],
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $posts = Post::whereIn('id', $validated['ids'])->get();

        foreach ($posts as $post) {
            match ($validated['action']) {
                'publish' => $this->posts->publish($post),
                'unpublish' => $this->posts->unpublish($post),
                'archive' => $this->posts->archive($post),
                'delete' => $this->posts->delete($post),
            };
        }

        $count = $posts->count();

        return back()->with('success', "{$count} ".str('post')->plural($count).' updated.');
    }

    public function generateImage(Request $request, Post $post): \Illuminate\Http\JsonResponse
    {
        if ($request->filled('title')) {
            $post->title = $request->string('title')->toString();
        }

        if ($request->has('tags')) {
            $tags = $request->input('tags');
            if (is_array($tags)) {
                $post->setRelation('tags', collect($tags)->map(fn ($name) => new \App\Models\Tag(['name' => $name])));
            }
        }

        $media = app(\App\Services\Images\AiIllustrationGenerator::class)->generate($post);

        if ($media) {
            $post->forceFill([
                'featured_image' => $media->path,
                'featured_image_alt' => $media->alt_text ?: \Illuminate\Support\Str::limit($post->title, 120, ''),
            ])->save();

            app(\App\Services\ContentFeedService::class)->flush();

            return response()->json([
                'success' => true,
                'path' => $media->path,
                'url' => $media->conversionUrl('thumbnail') ?? $media->url,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate AI image. Please verify your OpenAI or Gemini API keys are configured correctly.',
        ], 422);
    }

    public function searchPexels(Request $request, Post $post): \Illuminate\Http\JsonResponse
    {
        $config = config('site.media.stock');

        if (blank($config['key'] ?? null)) {
            return response()->json([
                'success' => false,
                'error' => 'Pexels API Key is not configured.',
            ], 422);
        }

        $query = $request->string('query')->trim()->toString();
        if ($query === '') {
            if ($request->filled('title')) {
                $post->title = $request->string('title')->toString();
            }
            if ($request->has('tags')) {
                $tags = $request->input('tags');
                if (is_array($tags)) {
                    $post->setRelation('tags', collect($tags)->map(fn ($name) => new \App\Models\Tag(['name' => $name])));
                }
            }
            $query = app(\App\Services\Images\StockPhotoGenerator::class)->query($post);
        }

        if ($query === '') {
            return response()->json([
                'success' => true,
                'query' => '',
                'photos' => [],
            ]);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders(['Authorization' => $config['key']])
                ->get($config['endpoint'], [
                    'query' => $query,
                    'orientation' => 'landscape',
                    'per_page' => 15,
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Pexels API request failed: ' . $e->getMessage(),
            ], 500);
        }

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'error' => 'Pexels API returned status code ' . $response->status(),
            ], 422);
        }

        $photos = collect($response->json('photos', []))->map(function ($photo) {
            return [
                'id' => $photo['id'],
                'url' => $photo['src']['medium'] ?? $photo['src']['large'] ?? '',
                'original' => $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? '',
                'photographer' => $photo['photographer'] ?? 'Pexels',
                'alt' => $photo['alt'] ?: '',
            ];
        });

        return response()->json([
            'success' => true,
            'query' => $query,
            'photos' => $photos,
        ]);
    }

    public function selectPexels(Request $request, Post $post): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'photo' => 'required|array',
            'photo.original' => 'required|string',
            'photo.photographer' => 'nullable|string',
            'photo.alt' => 'nullable|string',
        ]);

        $photo = $request->input('photo');

        $temporary = null;
        try {
            $body = \Illuminate\Support\Facades\Http::timeout(30)->get($photo['original'])->throw()->body();

            $temporary = tempnam(sys_get_temp_dir(), 'stock').'.jpg';
            file_put_contents($temporary, $body);

            $media = app(\App\Services\MediaService::class)->store(
                new \Illuminate\Http\UploadedFile($temporary, \Illuminate\Support\Str::slug(\Illuminate\Support\Str::limit($post->title, 60, '')).'.jpg', 'image/jpeg', null, true),
                $post->author ?? auth()->user(),
                'stock',
            );

            $media->forceFill([
                'caption' => 'Photo by '.($photo['photographer'] ?? 'Pexels').' on Pexels',
                'alt_text' => \Illuminate\Support\Str::limit($photo['alt'] ?: $post->title, 120, ''),
            ])->save();

            $post->forceFill([
                'featured_image' => $media->path,
                'featured_image_alt' => $media->alt_text,
            ])->save();

            app(\App\Services\ContentFeedService::class)->flush();

            return response()->json([
                'success' => true,
                'path' => $media->path,
                'url' => $media->conversionUrl('thumbnail') ?? $media->url,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Stock photo could not be selected', ['post' => $post->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to download and select Pexels image: ' . $e->getMessage(),
            ], 422);
        } finally {
            if ($temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
