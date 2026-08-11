<?php

namespace App\Services\AI;

use App\Services\AI\Exceptions\AiGenerationException;

/**
 * Turns a model's raw text into the article payload.
 *
 * Structured output makes the response valid JSON in the normal case, but this
 * still tolerates a fenced code block or surrounding prose - a provider that
 * ignores the schema should degrade to a parse, not to a crash.
 */
class ResponseParser
{
    /**
     * The JSON Schema providers are constrained to.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Headline, 40-70 characters, no clickbait.'],
                'slug' => ['type' => 'string', 'description' => 'Lowercase, hyphenated, ASCII only.'],
                'excerpt' => ['type' => 'string', 'description' => 'One or two sentences, under 300 characters.'],
                'content' => ['type' => 'string', 'description' => 'The article as HTML using only p, h2, h3, ul, ol, li, strong, em, blockquote and a.'],
                'seo_title' => ['type' => 'string', 'description' => 'Under 60 characters.'],
                'seo_description' => ['type' => 'string', 'description' => 'Under 160 characters.'],
                'seo_keywords' => ['type' => 'string', 'description' => 'Comma separated, 5-8 keywords.'],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => '3-6 short topic tags.',
                ],
                'image_prompt' => ['type' => 'string', 'description' => 'A prompt for generating a featured image.'],
            ],
            'required' => [
                'title', 'slug', 'excerpt', 'content',
                'seo_title', 'seo_description', 'seo_keywords', 'tags', 'image_prompt',
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $text): array
    {
        $json = $this->extractJson($text);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw AiGenerationException::retryable(
                'The model did not return usable JSON: '.json_last_error_msg()
            );
        }

        $missing = array_diff(
            ['title', 'content', 'excerpt'],
            array_keys(array_filter($data, fn ($value) => filled($value)))
        );

        if ($missing !== []) {
            throw AiGenerationException::retryable(
                'The response was missing required fields: '.implode(', ', $missing)
            );
        }

        $data['tags'] = collect($data['tags'] ?? [])
            ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn (string $tag) => trim($tag))
            ->take(8)
            ->values()
            ->all();

        return $data;
    }

    /**
     * Peels off a ```json fence or leading prose, then takes the outermost
     * JSON object.
     */
    private function extractJson(string $text): string
    {
        $text = trim($text);

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $text, $matches)) {
            return trim($matches[1]);
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($text, $start, $end - $start + 1);
        }

        return $text;
    }
}
