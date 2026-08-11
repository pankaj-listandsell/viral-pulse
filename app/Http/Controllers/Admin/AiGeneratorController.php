<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiGenerationStatus;
use App\Enums\ContentTone;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateAiContentRequest;
use App\Jobs\GenerateAiContentJob;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Services\AI\AiContentService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\GenerationRequest;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiGeneratorController extends Controller
{
    public function __construct(
        private readonly AiContentService $content,
        private readonly AiProviderManager $providers,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.ai.index', [
            'generations' => AiGeneration::query()
                ->with(['post:id,title,slug,status', 'user:id,name'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'contentTypes' => ContentType::cases(),
            'tones' => ContentTone::cases(),
            'providers' => $this->providers->configured(),
            'currentProvider' => $this->providers->current(),
            'hasProvider' => $this->providers->hasAnyProvider(),
            'usedToday' => AiGeneration::whereDate('created_at', today())->count(),
            'dailyLimit' => (int) config('ai.daily_limit'),
            'autoPublish' => (bool) config('site.content.auto_publish'),
            'filters' => $request->only('status'),
        ]);
    }

    public function store(GenerateAiContentRequest $request): RedirectResponse
    {
        if (! $this->providers->hasAnyProvider()) {
            return back()->with('error', 'No AI provider is configured. Add a key to your .env file first.');
        }

        $data = $request->validated();
        $generationRequest = GenerationRequest::fromArray($data);
        $generation = $this->content->queue($generationRequest, $request->user());

        GenerateAiContentJob::dispatch(
            generationId: $generation->id,
            requestData: $data,
            userId: $request->user()->id,
            categoryId: (int) $data['category_id'],
            // The admin reviews before publishing; only the scheduler may
            // auto-publish, and only when the quality gate passes.
            createPost: false,
        );

        return back()->with('success', 'Generating. This usually takes under a minute — the queue worker must be running.');
    }

    /**
     * Polled by the generator page so the admin sees progress without a reload.
     */
    public function status(AiGeneration $generation): JsonResponse
    {
        return response()->json([
            'id' => $generation->id,
            'status' => $generation->status->value,
            'label' => $generation->status->label(),
            'finished' => $generation->status->isFinished(),
            'error' => $generation->error_message,
            'quality' => $generation->quality_score,
            'title' => $generation->parsed_output['title'] ?? null,
            'issues' => $generation->parsed_output['quality']['issues'] ?? [],
            'post_id' => $generation->post_id,
            'tokens' => $generation->totalTokens(),
            'cost' => $generation->cost,
        ]);
    }

    public function show(AiGeneration $generation): View
    {
        abort_unless($generation->status === AiGenerationStatus::Completed, 404);

        return view('admin.ai.show', [
            'generation' => $generation->load('post:id,title,slug,status'),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'payload' => $generation->parsed_output ?? [],
        ]);
    }

    /**
     * One click from a finished generation to a real post. Keeping this to a
     * single action is what makes reviewing every article practical rather
     * than a bottleneck.
     */
    public function approve(Request $request, AiGeneration $generation): RedirectResponse
    {
        abort_unless($generation->status === AiGenerationStatus::Completed, 404);

        if ($generation->post_id) {
            return redirect()
                ->route('admin.posts.edit', $generation->post_id)
                ->with('info', 'This generation already has a post.');
        }

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'publish' => ['boolean'],
        ]);

        $post = $this->content->createPost(
            $generation,
            $request->user(),
            (int) $validated['category_id'],
            allowAutoPublish: false,
        );

        if ($request->boolean('publish')) {
            app(PostService::class)->publish($post);
        }

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', $request->boolean('publish') ? 'Published.' : 'Draft created. Review it before publishing.');
    }

    public function retry(Request $request, AiGeneration $generation): RedirectResponse
    {
        abort_if($generation->status === AiGenerationStatus::Completed, 404);

        $generation->update([
            'status' => AiGenerationStatus::Pending,
            'error_message' => null,
        ]);

        GenerateAiContentJob::dispatch(
            generationId: $generation->id,
            requestData: [
                'topic' => $generation->topic,
                'content_type' => $generation->content_type,
                'tone' => $generation->tone ?? ContentTone::Informative->value,
                'category_id' => $generation->post?->category_id,
                'language' => $generation->language,
                'audience' => $generation->target_audience,
                'target_words' => $generation->target_length ?? 900,
            ],
            userId: $request->user()->id,
            createPost: false,
        );

        return back()->with('success', 'Retrying.');
    }

    public function destroy(AiGeneration $generation): RedirectResponse
    {
        $generation->delete();

        return back()->with('success', 'Generation record deleted.');
    }
}
