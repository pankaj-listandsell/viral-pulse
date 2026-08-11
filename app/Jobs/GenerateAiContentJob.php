<?php

namespace App\Jobs;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiContentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Three attempts, backing off 30s then 2m then 5m. Rate limits and
     * overloads are the common failures and both clear on that timescale.
     */
    public int $tries = 3;

    public int $timeout = 300;

    /** Two failed generations in a row means the provider is down, not flaky. */
    public int $maxExceptions = 2;

    /**
     * @param  array<string, mixed>  $requestData
     */
    public function __construct(
        public readonly int $generationId,
        public readonly array $requestData,
        public readonly ?int $userId = null,
        public readonly ?int $categoryId = null,
        public readonly bool $createPost = true,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(AiContentService $service): void
    {
        $generation = AiGeneration::find($this->generationId);

        if (! $generation) {
            return;
        }

        // A retry of an attempt that already succeeded would bill twice and
        // create a duplicate post.
        if ($generation->status === AiGenerationStatus::Completed) {
            return;
        }

        $request = GenerationRequest::fromArray($this->requestData);

        try {
            $service->run($generation, $request);
        } catch (AiGenerationException $e) {
            // A rejected key, a refused topic or a breached spend cap fails the
            // same way every time - retrying only burns queue workers.
            if (! $e->retryable) {
                // Marked here rather than left to the failed() hook: a limit
                // breach throws before the service has touched the row, and a
                // generation stuck on "pending" with no reason is worse than a
                // clear failure.
                $generation->update([
                    'status' => AiGenerationStatus::Failed,
                    'error_message' => $e->getMessage(),
                ]);

                $this->fail($e);

                return;
            }

            throw $e;
        }

        if (! $this->createPost) {
            return;
        }

        $author = User::find($this->userId) ?? User::admins()->first();
        $categoryId = $this->categoryId ?? $this->fallbackCategoryId($request);

        if (! $author || ! $categoryId) {
            Log::warning('Generated content could not be turned into a post', [
                'generation' => $generation->id,
                'reason' => $author ? 'no category available' : 'no author available',
            ]);

            return;
        }

        $service->createPost($generation->refresh(), $author, $categoryId, allowAutoPublish: true);
    }

    public function failed(?Throwable $exception): void
    {
        AiGeneration::whereKey($this->generationId)
            ->where('status', '!=', AiGenerationStatus::Completed)
            ->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $exception?->getMessage() ?? 'The job failed without an error message.',
            ]);

        Log::error('AI content generation failed', [
            'generation' => $this->generationId,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function fallbackCategoryId(GenerationRequest $request): ?int
    {
        return $request->category?->id
            ?? Category::active()->ordered()->value('id');
    }
}
