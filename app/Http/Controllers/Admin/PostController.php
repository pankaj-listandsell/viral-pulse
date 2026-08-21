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
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request): View
    {
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
            ->latest('updated_at')
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
            'filters' => $request->only('search', 'status', 'category', 'source', 'trashed'),
        ]);
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
