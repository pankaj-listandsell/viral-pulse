<?php

namespace App\Jobs;

use App\Enums\AiGenerationStatus;
use App\Enums\PostStatus;
use App\Enums\TrendingTopicStatus;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\Post;
use App\Models\TrendingTopic;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;
use App\Services\Images\FeaturedImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
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
     * @param  string|null  $publishAt  ISO time this article should go live; null
     *                                  publishes immediately or leaves a draft,
     *                                  depending on the auto-publish setting
     */
    public function __construct(
        public readonly int $generationId,
        public readonly array $requestData,
        public readonly ?int $userId = null,
        public readonly ?int $categoryId = null,
        public readonly bool $createPost = true,
        public readonly ?string $publishAt = null,
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
            $this->releaseTopic($request, TrendingTopicStatus::Generated);

            return;
        }

        $author = User::find($this->userId) ?? User::admins()->first();
        $categoryId = $this->categoryId ?? $this->fallbackCategoryId($request);

        if (! $author || ! $categoryId) {
            Log::warning('Generated content could not be turned into a post', [
                'generation' => $generation->id,
                'reason' => $author ? 'no category available' : 'no author available',
            ]);

            $this->releaseTopic($request, TrendingTopicStatus::Failed);

            return;
        }

        $generation = $generation->refresh();

        // A drip-published article is created as a draft and then scheduled, so
        // the quality gate is applied once, in one place, either way.
        $post = $service->createPost(
            $generation,
            $author,
            $categoryId,
            allowAutoPublish: $this->publishAt === null,
        );

        // Before scheduling, so the card exists by the time the post is public.
        app(FeaturedImageService::class)->ensure($post);

        if ($this->publishAt) {
            $service->schedulePublication($generation, $post->refresh(), Carbon::parse($this->publishAt));
        }

        $this->linkTopic($request, $post);
    }

    public function failed(?Throwable $exception): void
    {
        AiGeneration::whereKey($this->generationId)
            ->where('status', '!=', AiGenerationStatus::Completed)
            ->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $exception?->getMessage() ?? 'The job failed without an error message.',
            ]);

        // Back to "failed" rather than left "generating", so the next automated
        // run can pick the topic up again instead of it being stuck forever.
        if ($topicId = $this->requestData['trending_topic_id'] ?? null) {
            TrendingTopic::whereKey($topicId)->update(['status' => TrendingTopicStatus::Failed]);
        }

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

    private function releaseTopic(GenerationRequest $request, TrendingTopicStatus $status): void
    {
        if ($request->trendingTopicId) {
            TrendingTopic::whereKey($request->trendingTopicId)->update(['status' => $status]);
        }
    }

    private function linkTopic(GenerationRequest $request, Post $post): void
    {
        if (! $request->trendingTopicId) {
            return;
        }

        TrendingTopic::whereKey($request->trendingTopicId)->update([
            'post_id' => $post->id,
            'status' => $post->status === PostStatus::Scheduled
                ? TrendingTopicStatus::Scheduled
                : TrendingTopicStatus::Generated,
        ]);
    }
}
