<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;
use App\Services\AI\ResponseParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProvider
{
    public function __construct(
        private readonly ResponseParser $parser,
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return 'openai';
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
        $result = $this->call($systemPrompt, $userPrompt, ResponseParser::schema(), 'article');

        return [...$result, 'payload' => $this->parser->parse($result['raw'])];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     */
    public function generateJson(string $systemPrompt, string $userPrompt, array $schema, string $name = 'result'): array
    {
        $result = $this->call($systemPrompt, $userPrompt, $schema, $name);

        return [...$result, 'payload' => $this->parser->decode($result['raw'])];
    }

    /**
     * The single round trip, shared by both entry points above.
     *
     * @param  array<string, mixed>  $schema
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     */
    private function call(string $systemPrompt, string $userPrompt, array $schema, string $name): array
    {
        try {
            $response = Http::timeout(config('ai.timeout'))
                ->withToken($this->config['key'])
                ->asJson()
                ->post(rtrim($this->config['endpoint'], '/').'/chat/completions', [
                    'model' => $this->model(),
                    'max_completion_tokens' => config('ai.max_tokens'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    // Strict schema mode: the response is guaranteed to match,
                    // so there is no prose to strip and no reparse loop.
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => $name,
                            'strict' => true,
                            'schema' => $schema,
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw AiGenerationException::retryable("Could not reach OpenAI: {$e->getMessage()}", $e);
        }

        $this->assertOk($response->status(), $response->json());

        $body = $response->json();
        $choice = $body['choices'][0] ?? null;

        if (! $choice) {
            throw AiGenerationException::retryable('OpenAI returned no choices.');
        }

        if (($choice['finish_reason'] ?? null) === 'length') {
            throw AiGenerationException::retryable(
                'The response was cut off before it finished. Try asking for less in one call.'
            );
        }

        if (! empty($choice['message']['refusal'])) {
            throw AiGenerationException::permanent(
                'The model declined this topic: '.$choice['message']['refusal']
            );
        }

        $text = (string) ($choice['message']['content'] ?? '');

        if (trim($text) === '') {
            throw AiGenerationException::retryable('OpenAI returned an empty response.');
        }

        $usage = $body['usage'] ?? [];

        return [
            // The caller decodes: an article and a horoscope are validated
            // very differently, and this method knows about neither.
            'payload' => [],
            'model' => $body['model'] ?? $this->model(),
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
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

        throw match (true) {
            $status === 429 => AiGenerationException::retryable("OpenAI rate limit reached: {$message}"),
            $status >= 500 => AiGenerationException::retryable("OpenAI is unavailable ({$status}): {$message}"),
            $status === 401 => AiGenerationException::permanent(
                'The OpenAI API key was rejected. Check OPENAI_API_KEY.'
            ),
            default => AiGenerationException::permanent("OpenAI rejected the request ({$status}): {$message}"),
        };
    }
}
