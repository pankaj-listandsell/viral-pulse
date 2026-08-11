<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): View|JsonResponse
    {
        $media = Media::query()
            ->with('user:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($q) => $q->where('original_name', 'like', $term)->orWhere('alt_text', 'like', $term));
            })
            ->latest()
            ->paginate(36)
            ->withQueryString();

        // The media picker inside the post editor reads the same list as JSON.
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $media->getCollection()->map(fn (Media $item) => $this->toArray($item)),
                'next_page' => $media->hasMorePages() ? $media->currentPage() + 1 : null,
            ]);
        }

        return view('admin.media.index', [
            'media' => $media,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(UploadMediaRequest $request): JsonResponse|RedirectResponse
    {
        $stored = [];
        $failed = [];

        foreach ($request->file('files') as $file) {
            try {
                $stored[] = $this->media->store($file, $request->user(), $request->input('folder'));
            } catch (RuntimeException $e) {
                $failed[] = $file->getClientOriginalName().': '.$e->getMessage();
            } catch (\Throwable $e) {
                Log::error('Media upload failed', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
                $failed[] = $file->getClientOriginalName().': could not be processed.';
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'uploaded' => collect($stored)->map(fn (Media $item) => $this->toArray($item)),
                'failed' => $failed,
            ], $stored === [] ? 422 : 201);
        }

        $count = count($stored);

        return back()
            ->with($count > 0 ? 'success' : 'error', $count > 0
                ? "{$count} ".str('image')->plural($count).' uploaded.'
                : 'Nothing was uploaded.')
            ->with('warning', $failed ? implode(' ', $failed) : null);
    }

    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());

        return back()->with('success', 'Image details saved.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->media->delete($media);

        return back()->with('success', 'Image deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Media $media): array
    {
        return [
            'id' => $media->id,
            'path' => $media->path,
            'url' => $media->url,
            'thumbnail' => $media->conversionUrl('thumbnail') ?? $media->url,
            'name' => $media->original_name,
            'alt_text' => $media->alt_text,
            'width' => $media->width,
            'height' => $media->height,
            'size' => $media->size,
        ];
    }
}
