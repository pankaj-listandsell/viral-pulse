<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;
use App\Services\AI\ResponseParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProvider
{
    public function __construct(
        private readonly ResponseParser $parser,
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function model(): string
    {
        return $this->config['model'];
    }

    /**
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     */
    public function generate(GenerationRequest $request, string $systemPrompt, string $userPrompt): array
    {
        $url = rtrim($this->config['endpoint'], '/')."/models/{$this->model()}:generateContent";

        try {
            $response = Http::timeout(config('ai.timeout'))
                // Key travels in a header, never in the query string - URLs end
                // up in proxy logs and browser history.
                ->withHeaders(['x-goog-api-key' => $this->config['key']])
                ->asJson()
                ->post($url, [
                    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
                    'generationConfig' => [
                        'maxOutputTokens' => config('ai.max_tokens'),
                        // Constrains the model to the schema, so the response
                        // is parseable JSON rather than prose wrapping JSON.
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->schema(),
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw AiGenerationException::retryable("Could not reach Gemini: {$e->getMessage()}", $e);
        }

        $this->assertOk($response->status(), $response->json());

        $body = $response->json();
        $candidate = $body['candidates'][0] ?? null;

        if (! $candidate) {
            throw AiGenerationException::permanent('Gemini returned no candidates. The prompt may have been blocked.');
        }

        $finish = $candidate['finishReason'] ?? null;

        if ($finish === 'SAFETY' || $finish === 'PROHIBITED_CONTENT') {
            throw AiGenerationException::permanent('Gemini declined this topic on safety grounds. Rewrite the topic and try again.');
        }

        if ($finish === 'MAX_TOKENS') {
            throw AiGenerationException::retryable('The article was cut off before it finished. Try a shorter target length.');
        }

        $text = collect($candidate['content']['parts'] ?? [])
            ->pluck('text')
            ->filter()
            ->implode('');

        if ($text === '') {
            throw AiGenerationException::retryable('Gemini returned an empty response.');
        }

        $usage = $body['usageMetadata'] ?? [];

        return [
            'payload' => $this->parser->parse($text),
            'model' => $body['modelVersion'] ?? $this->model(),
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'raw' => $text,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function assertOk(int $status, ?array $body): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        $message = $body['error']['message'] ?? 'Unknown error';

        // 429 and 5xx clear on their own; 401/403/400 will fail identically
        // on every retry, so they must not be re-queued.
        throw match (true) {
            $status === 429 => AiGenerationException::retryable("Gemini rate limit reached: {$message}"),
            $status >= 500 => AiGenerationException::retryable("Gemini is unavailable ({$status}): {$message}"),
            $status === 401 || $status === 403 => AiGenerationException::permanent(
                'The Gemini API key was rejected. Check GEMINI_API_KEY.'
            ),
            default => AiGenerationException::permanent("Gemini rejected the request ({$status}): {$message}"),
        };
    }

    /**
     * Gemini uses its own schema dialect - uppercase types, `propertyOrdering`
     * instead of relying on key order, and no `additionalProperties`.
     *
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'slug' => ['type' => 'STRING'],
                'excerpt' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'seo_title' => ['type' => 'STRING'],
                'seo_description' => ['type' => 'STRING'],
                'seo_keywords' => ['type' => 'STRING'],
                'tags' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'image_prompt' => ['type' => 'STRING'],
            ],
            'required' => [
                'title', 'slug', 'excerpt', 'content',
                'seo_title', 'seo_description', 'seo_keywords', 'tags', 'image_prompt',
            ],
            'propertyOrdering' => [
                'title', 'slug', 'excerpt', 'content',
                'seo_title', 'seo_description', 'seo_keywords', 'tags', 'image_prompt',
            ],
        ];
    }
}
