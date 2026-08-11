<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->with('parent:id,name')
                ->withCount('posts')
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
                ->ordered()
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
            'parents' => Category::roots()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->slugs->unique(Category::class, $data['slug'] ?? $data['name']);

        $category = Category::create($data);

        $this->logger->log('category.created', $category, "Created category \"{$category->name}\"");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::roots()
                ->whereKeyNot($category->id)
                ->ordered()
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['slug']) && $data['slug'] !== $category->slug) {
            $data['slug'] = $this->slugs->unique(Category::class, $data['slug'], ignoreId: $category->id);
        } else {
            unset($data['slug']);
        }

        $category->update($data);

        $this->logger->log('category.updated', $category, "Updated category \"{$category->name}\"");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // posts.category_id is restrictOnDelete, so this would fail at the
        // database anyway - catching it here gives a usable message instead.
        if ($category->posts()->exists()) {
            return back()->with('error', 'Move or delete this category\'s posts before removing it.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'This category still has subcategories.');
        }

        $name = $category->name;
        $category->delete();

        $this->logger->log('category.deleted', $category, "Deleted category \"{$name}\"");

        return back()->with('success', 'Category deleted.');
    }
}
