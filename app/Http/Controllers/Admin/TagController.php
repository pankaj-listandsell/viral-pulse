<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.tags.index', [
            'tags' => Tag::query()
                ->withCount('posts')
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugs->unique(Tag::class, $data['slug'] ?? $data['name']);

        $tag = Tag::create($data);

        $this->logger->log('tag.created', $tag, "Created tag \"{$tag->name}\"");

        return back()->with('success', 'Tag created.');
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['slug']) && $data['slug'] !== $tag->slug) {
            $data['slug'] = $this->slugs->unique(Tag::class, $data['slug'], ignoreId: $tag->id);
        } else {
            unset($data['slug']);
        }

        $tag->update($data);

        $this->logger->log('tag.updated', $tag, "Updated tag \"{$tag->name}\"");

        return back()->with('success', 'Tag saved.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $name = $tag->name;

        // post_tag cascades, so the posts themselves are untouched.
        $tag->delete();

        $this->logger->log('tag.deleted', $tag, "Deleted tag \"{$name}\"");

        return back()->with('success', 'Tag deleted.');
    }
}
