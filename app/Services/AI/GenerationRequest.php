<?php

namespace App\Services\AI;

use App\Enums\ContentTone;
use App\Enums\ContentType;
use App\Models\Category;

/**
 * Everything a provider needs to write one article. Immutable so a retry can
 * never run against a half-mutated request.
 */
final readonly class GenerationRequest
{
    public function __construct(
        public string $topic,
        public ContentType $contentType,
        public ContentTone $tone,
        public ?Category $category = null,
        public string $language = 'en',
        public ?string $audience = null,
        public int $targetWords = 900,
        public ?string $extraContext = null,
        public ?int $trendingTopicId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            topic: trim($data['topic']),
            contentType: ContentType::from($data['content_type']),
            tone: ContentTone::from($data['tone'] ?? ContentTone::Informative->value),
            category: isset($data['category_id']) ? Category::find($data['category_id']) : null,
            language: $data['language'] ?? 'en',
            audience: $data['audience'] ?? null,
            targetWords: (int) ($data['target_words'] ?? 900),
            extraContext: $data['extra_context'] ?? null,
            trendingTopicId: isset($data['trending_topic_id']) ? (int) $data['trending_topic_id'] : null,
        );
    }

    public function languageName(): string
    {
        return match ($this->language) {
            'hi' => 'Hindi',
            default => 'English',
        };
    }
}
