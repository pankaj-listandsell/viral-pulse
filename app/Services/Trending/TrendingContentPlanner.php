<?php

namespace App\Services\Trending;

use App\Enums\ContentTone;
use App\Enums\ContentType;
use App\Enums\TrendingTopicStatus;
use App\Jobs\GenerateAiContentJob;
use App\Models\AiGeneration;
use App\Models\TrendingTopic;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\GenerationRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Decides which trending topics become articles, and when they go live.
 */
class TrendingContentPlanner
{
    public function __construct(
        private readonly AiContentService $content,
        private readonly PublishWindow $window,
        private readonly TrendingTopicService $topics,
    ) {}

    /**
     * Topics worth spending an API call on: still undecided, scoring above the
     * floor, and recent enough that the article can still rank.
     *
     * @return Collection<int, TrendingTopic>
     */
    public function eligible(int $limit): Collection
    {
        $minScore = (int) config('trending.automation.min_score', 45);
        $maxAge = max(1, (int) config('trending.automation.max_age_hours', 36));

        return TrendingTopic::query()
            ->with('category')
            ->availableForGeneration()
            ->where('trend_score', '>=', $minScore)
            ->where('detected_at', '>=', now()->subHours($maxAge))
            ->highestScoring()
            ->limit($limit)
            ->get()
            ->reject(fn (TrendingTopic $topic) => $this->topics->isBlocked($topic))
            ->values();
    }

    /**
     * Queue one article per eligible topic, each with its own publishing slot.
     *
     * @return array{queued: int, slots: array<int, string>, reason: ?string}
     */
    public function run(?int $limit = null): array
    {
        $limit ??= (int) config('trending.automation.per_run', 2);
        $limit = max(0, $limit);

        $author = User::admins()->where('is_active', true)->first();

        if (! $author) {
            return ['queued' => 0, 'slots' => [], 'reason' => 'No active admin account to attribute posts to.'];
        }

        $topics = $this->eligible($limit);

        if ($topics->isEmpty()) {
            return ['queued' => 0, 'slots' => [], 'reason' => 'No eligible topics.'];
        }

        // Immediate mode writes the article at the moment its slot arrives and
        // puts it live as soon as it is ready, with no future date attached.
        // Simpler to reason about, and the article is minutes old rather than
        // hours - at the cost of the slot being empty if the model fails.
        if ($this->window->publishesImmediately()) {
            $queued = 0;

            foreach ($topics as $topic) {
                $this->dispatchFor($topic, $author, publishAt: null);
                $queued++;
            }

            return ['queued' => $queued, 'slots' => ['as soon as written'], 'reason' => null];
        }

        $slots = $this->window->nextSlots($topics->count());

        if ($slots === []) {
            // The schedule being full is a legitimate stop condition: writing
            // more articles now would only pile up unpublished drafts and spend
            // money doing it.
            return ['queued' => 0, 'slots' => [], 'reason' => 'The publishing schedule is full.'];
        }

        $queued = 0;
        $used = [];

        foreach ($topics as $index => $topic) {
            if (! isset($slots[$index])) {
                break;
            }

            $this->dispatchFor($topic, $author, $slots[$index]);

            $used[] = $slots[$index]->toDateTimeString();
            $queued++;
        }

        return ['queued' => $queued, 'slots' => $used, 'reason' => null];
    }

    /**
     * Start one generation for a topic.
     *
     * @param  Carbon|null  $publishAt  null means "leave the outcome to the
     *                                  auto-publish setting" - used by the
     *                                  admin's manual button
     */
    public function dispatchFor(
        TrendingTopic $topic,
        User $author,
        ?Carbon $publishAt = null,
        bool $createPost = true,
    ): AiGeneration {
        $topic->loadMissing('category');

        $request = new GenerationRequest(
            topic: $topic->topic,
            contentType: ContentType::tryFrom((string) config('trending.automation.content_type', 'news')) ?? ContentType::News,
            tone: ContentTone::tryFrom((string) config('trending.automation.tone', 'informative')) ?? ContentTone::Informative,
            category: $topic->category,
            language: $topic->language ?: 'en',
            targetWords: (int) config('trending.automation.target_words', 900),
            extraContext: $this->context($topic),
            trendingTopicId: $topic->id,
        );

        $generation = $this->content->queue($request, $author);

        // Marked before dispatch: a worker picking the job up immediately must
        // not find the topic still available for a second run.
        $topic->forceFill(['status' => TrendingTopicStatus::Generating])->save();

        GenerateAiContentJob::dispatch(
            generationId: $generation->id,
            requestData: [
                'topic' => $request->topic,
                'content_type' => $request->contentType->value,
                'tone' => $request->tone->value,
                'category_id' => $topic->category_id,
                'language' => $request->language,
                'target_words' => $request->targetWords,
                'extra_context' => $request->extraContext,
                'trending_topic_id' => $topic->id,
            ],
            userId: $author->id,
            categoryId: $topic->category_id,
            createPost: $createPost,
            publishAt: $publishAt?->toIso8601String(),
        );

        return $generation;
    }

    /**
     * What the feed said about the topic. The model is told elsewhere not to
     * invent facts, so this is the only grounding it gets.
     */
    private function context(TrendingTopic $topic): ?string
    {
        $parts = array_filter([
            $topic->description,
            $topic->source_url ? "Reference: {$topic->source_url}" : null,
            'Source: '.$topic->source->label().'.',
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }
}
