<?php

namespace App\Services\AI;

use App\Enums\AiGenerationStatus;
use App\Enums\PostSourceType;
use App\Enums\PostStatus;
use App\Models\AiGeneration;
use App\Models\Post;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\HtmlSanitizer;
use App\Services\PostService;
use App\Services\SlugService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiContentService
{
    public function __construct(
        private readonly AiProviderManager $providers,
        private readonly PromptBuilder $prompts,
        private readonly ContentValidator $validator,
        private readonly HtmlSanitizer $sanitizer,
        private readonly SlugService $slugs,
        private readonly PostService $posts,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Records the intent before any API call, so a crash mid-generation still
     * leaves a row explaining what was attempted.
     */
    public function queue(GenerationRequest $request, ?User $user): AiGeneration
    {
        $provider = $this->providers->current();

        return AiGeneration::create([
            'user_id' => $user?->id,
            'trending_topic_id' => $request->trendingTopicId,
            'provider' => $provider,
            'model' => config("ai.providers.{$provider}.model", 'unknown'),
            'content_type' => $request->contentType->value,
            'topic' => $request->topic,
            'language' => $request->language,
            'tone' => $request->tone->value,
            'target_audience' => $request->audience,
            'target_length' => $request->targetWords,
            'prompt' => $this->prompts->user($request),
            'status' => AiGenerationStatus::Pending,
        ]);
    }

    /**
     * Runs one generation and records the outcome either way.
     *
     * @throws AiGenerationException
     */
    public function run(AiGeneration $generation, GenerationRequest $request): GenerationResult
    {
        $this->assertWithinDailyLimit();

        $generation->update(['status' => AiGenerationStatus::Processing]);

        $provider = $this->providers->resolve();
        $system = $this->prompts->system();
        $user = $this->prompts->user($request);
        $startedAt = microtime(true);

        try {
            $response = $provider->generate($request, $system, $user);
        } catch (AiGenerationException $e) {
            $fallback = $this->providers->fallback($this->providers->current());

            if ($fallback) {
                try {
                    Log::info("Primary AI provider ({$provider->name()}) failed, attempting fallback to {$fallback->name()}", [
                        'error' => $e->getMessage(),
                    ]);

                    $response = $fallback->generate($request, $system, $user);

                    $generation->update([
                        'provider' => $fallback->name(),
                        'model' => $fallback->model(),
                    ]);
                } catch (AiGenerationException $fallbackException) {
                    $generation->update([
                        'status' => AiGenerationStatus::Failed,
                        'error_message' => "{$e->getMessage()} | Fallback ({$fallback->name()}): {$fallbackException->getMessage()}",
                        'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    ]);

                    throw $fallbackException;
                }
            } else {
                $generation->update([
                    'status' => AiGenerationStatus::Failed,
                    'error_message' => $e->getMessage(),
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]);

                throw $e;
            }
        }

        $payload = $response['payload'];

        // Sanitised before anything else touches it. The model's HTML is
        // untrusted input no matter how well the prompt behaved.
        $payload['content'] = $this->sanitizer->clean($payload['content'] ?? '');
        $payload['excerpt'] = $this->sanitizer->plain($payload['excerpt'] ?? '', 500);
        $payload['title'] = $this->sanitizer->plain($payload['title'] ?? '', 255);
        $payload['seo_title'] = $this->sanitizer->plain($payload['seo_title'] ?? '', 255);
        // Models routinely overshoot the 160-character guidance. Trimming at a
        // word boundary is mechanical cleanup, not an editorial judgement, so
        // it happens here rather than being flagged on every single article.
        $payload['seo_description'] = $this->trimToLimit(
            $this->sanitizer->plain($payload['seo_description'] ?? '', 500),
            158
        );
        $payload['seo_keywords'] = $this->sanitizer->plain($payload['seo_keywords'] ?? '', 500);

        $quality = $this->validator->check($payload);

        $result = new GenerationResult(
            title: $payload['title'],
            slug: $this->slugs->normalise($payload['slug'] ?? $payload['title']),
            excerpt: $payload['excerpt'],
            content: $payload['content'],
            seoTitle: $payload['seo_title'],
            seoDescription: $payload['seo_description'],
            seoKeywords: $payload['seo_keywords'],
            tags: $payload['tags'] ?? [],
            imagePrompt: $payload['image_prompt'] ?? null,
            provider: $provider->name(),
            model: $response['model'],
            promptTokens: $response['prompt_tokens'],
            completionTokens: $response['completion_tokens'],
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            raw: $payload,
        );

        $generation->update([
            'provider' => $result->provider,
            'model' => $result->model,
            'raw_response' => Str::limit($response['raw'], 60000, ''),
            'parsed_output' => [...$result->toArray(), 'quality' => $quality],
            'status' => AiGenerationStatus::Completed,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'cost' => $result->estimatedCost(),
            'duration_ms' => $result->durationMs,
            'quality_score' => $quality['score'],
        ]);

        return $result;
    }

    /**
     * Turns a completed generation into a post.
     *
     * The default is a draft for review. Auto-publish is only honoured when it
     * is switched on AND the content cleared the quality gate - a setting alone
     * is never enough to put unreviewed text on the site.
     */
    public function createPost(AiGeneration $generation, User $author, int $categoryId, bool $allowAutoPublish = false): Post
    {
        $payload = $generation->parsed_output ?? [];
        $quality = $payload['quality'] ?? ['publishable' => false, 'issues' => []];

        $autoPublish = $allowAutoPublish
            && (bool) config('site.content.auto_publish', false)
            && ($quality['publishable'] ?? false);

        $post = $this->posts->create([
            'title' => $payload['title'],
            'slug' => $payload['slug'] ?? null,
            'excerpt' => $payload['excerpt'] ?? null,
            'content' => $payload['content'],
            'category_id' => $categoryId,
            'tags' => $payload['tags'] ?? [],
            'status' => $autoPublish ? PostStatus::Published->value : PostStatus::Draft->value,
            'language' => $generation->language,
            'seo_title' => $payload['seo_title'] ?? null,
            'seo_description' => $payload['seo_description'] ?? null,
            'seo_keywords' => $payload['seo_keywords'] ?? null,
        ], $author);

        $post->forceFill([
            'ai_generated' => true,
            'source_type' => $generation->trending_topic_id ? PostSourceType::Trending : PostSourceType::Ai,
        ])->save();

        $generation->update(['post_id' => $post->id]);

        $this->logger->log(
            'ai.post_created',
            $post,
            $autoPublish
                ? "Auto-published \"{$post->title}\" (quality {$generation->quality_score})"
                : "Created draft \"{$post->title}\" (quality {$generation->quality_score})"
        );

        if (! $autoPublish && ! ($quality['publishable'] ?? true)) {
            Log::info('AI content held for review', [
                'generation' => $generation->id,
                'issues' => $quality['issues'] ?? [],
            ]);
        }

        return $post;
    }

    /**
     * Puts a generated draft into the publishing queue for a given time.
     *
     * Same gate as an immediate publish: the auto-publish switch must be on and
     * the article must have cleared the quality floor. Anything else stays a
     * draft, because a scheduled post is still an unreviewed post going live.
     */
    public function schedulePublication(AiGeneration $generation, Post $post, Carbon $at): Post
    {
        $quality = ($generation->parsed_output ?? [])['quality'] ?? [];

        if (! (bool) config('site.content.auto_publish', false) || ! ($quality['publishable'] ?? false)) {
            Log::info('Generated post held as a draft instead of being scheduled', [
                'generation' => $generation->id,
                'post' => $post->id,
                'issues' => $quality['issues'] ?? [],
            ]);

            return $post;
        }

        $post = $this->posts->schedule($post, $at);

        $this->logger->log(
            'ai.post_scheduled',
            $post,
            "Scheduled \"{$post->title}\" for {$at->toDayDateTimeString()} (quality {$generation->quality_score})"
        );

        return $post;
    }

    /**
     * Cuts at the last whole word inside the limit, so a meta description
     * never ends mid-word.
     */
    private function trimToLimit(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace ? mb_substr($cut, 0, $lastSpace) : $cut, ' ,;:-');
    }

    /**
     * A stuck scheduler or a retry storm is the realistic way this runs up a
     * bill, so the ceiling is checked before every call rather than trusted to
     * the caller.
     */
    private function assertWithinDailyLimit(): void
    {
        $limit = (int) config('ai.daily_limit', 50);

        if ($limit <= 0) {
            return;
        }

        $used = AiGeneration::whereDate('created_at', today())
            ->whereIn('status', [AiGenerationStatus::Completed, AiGenerationStatus::Processing])
            ->count();

        if ($used >= $limit) {
            throw AiGenerationException::permanent(
                "The daily generation limit of {$limit} has been reached. Raise AI_DAILY_GENERATION_LIMIT to continue."
            );
        }
    }
}
