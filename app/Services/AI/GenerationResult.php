<?php

namespace App\Services\AI;

/**
 * The normalised shape every provider returns, so nothing downstream has to
 * know which model produced the article.
 */
final readonly class GenerationResult
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $content,
        public string $seoTitle,
        public string $seoDescription,
        public string $seoKeywords,
        public array $tags,
        public ?string $imagePrompt,
        public string $provider,
        public string $model,
        public int $promptTokens,
        public int $completionTokens,
        public int $durationMs,
        public array $raw = [],
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    /**
     * Indicative cost from the configured price table - the provider's own
     * billing is authoritative.
     */
    public function estimatedCost(): float
    {
        // Indexed, not dot-notated: config() splits on dots, and model ids
        // contain them ("gemini-2.5-flash" would resolve as ai.pricing.gemini-2
        // then 5-flash, and silently return null).
        $pricing = config('ai.pricing')[$this->model] ?? null;

        if (! $pricing) {
            return 0.0;
        }

        return round(
            ($this->promptTokens / 1_000_000) * $pricing['input']
            + ($this->completionTokens / 1_000_000) * $pricing['output'],
            6
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_keywords' => $this->seoKeywords,
            'tags' => $this->tags,
            'image_prompt' => $this->imagePrompt,
        ];
    }
}
