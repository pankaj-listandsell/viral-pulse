<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;
use App\Services\AI\ResponseParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Text generation on Cloudflare Workers AI, through its OpenAI-compatible
 * chat endpoint.
 *
 * The backup writer: Gemini's free tier runs out early in the day, and
 * Workers AI has its own free daily allowance on the same Cloudflare account
 * that already draws the illustrations, so when Gemini says no the article
 * still gets written.
 */
class CloudflareProvider implements AiProvider
{
    public function __construct(
        private readonly ResponseParser $parser,
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return 'cloudflare';
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

        return [...$result, 'payload' => $this->parser->parse($this->withExcerpt($result['raw']))];
    }

    /**
     * Llama follows the schema loosely and now and then leaves the excerpt
     * out of an otherwise complete article. The opening paragraph is what an
     * excerpt would say anyway, so it stands in rather than the whole article
     * being thrown away.
     */
    private function withExcerpt(string $raw): string
    {
        $data = $this->parser->decode($raw);

        if (filled($data['excerpt'] ?? null) || blank($data['content'] ?? null)) {
            return $raw;
        }

        preg_match('/<p[^>]*>(.*?)<\/p>/is', (string) $data['content'], $match);
        $opening = trim(strip_tags($match[1] ?? (string) $data['content']));

        $data['excerpt'] = Str::limit(preg_replace('/\s+/u', ' ', $opening), 280);

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE);
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
     * @param  array<string, mixed>  $schema
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     */
    private function call(string $systemPrompt, string $userPrompt, array $schema, string $name): array
    {
        if (blank($this->config['account_id'] ?? null)) {
            throw AiGenerationException::permanent('No Cloudflare account id configured. Set CLOUDFLARE_ACCOUNT_ID.');
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->config['account_id']}/ai/v1/chat/completions";

        try {
            $response = Http::timeout(config('ai.timeout'))
                ->withToken($this->config['key'])
                ->asJson()
                ->post($url, [
                    'model' => $this->model(),
                    // The site-wide limit is sized for Gemini's long output;
                    // these models have a smaller window, and asking for more
                    // than it holds is rejected outright.
                    'max_tokens' => min((int) config('ai.max_tokens'), (int) ($this->config['max_tokens'] ?? 8000)),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => ['name' => $name, 'schema' => $schema],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw AiGenerationException::retryable("Could not reach Cloudflare Workers AI: {$e->getMessage()}", $e);
        }

        $this->assertOk($response->status(), $response->json());

        $body = $response->json();
        $choice = $body['choices'][0] ?? null;

        if (! $choice) {
            throw AiGenerationException::retryable('Cloudflare Workers AI returned no choices.');
        }

        if (($choice['finish_reason'] ?? null) === 'length') {
            throw AiGenerationException::retryable(
                'The response was cut off before it finished. Try asking for less in one call.'
            );
        }

        $content = $choice['message']['content'] ?? '';

        // In JSON mode some models hand back the object itself rather than a
        // string holding it.
        $text = is_array($content) ? (string) json_encode($content, JSON_UNESCAPED_UNICODE) : (string) $content;

        if (trim($text) === '') {
            throw AiGenerationException::retryable('Cloudflare Workers AI returned an empty response.');
        }

        $usage = $body['usage'] ?? [];

        return [
            'payload' => [],
            'model' => $this->model(),
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

        $message = $body['errors'][0]['message'] ?? $body['error']['message'] ?? 'Unknown error';

        throw match (true) {
            $status === 429 => AiGenerationException::retryable("Cloudflare Workers AI rate limit reached: {$message}"),
            $status >= 500 => AiGenerationException::retryable("Cloudflare Workers AI is unavailable ({$status}): {$message}"),
            in_array($status, [401, 403], true) => AiGenerationException::permanent(
                'The Cloudflare token was rejected. Check CLOUDFLARE_AI_TOKEN and its Workers AI permission.'
            ),
            default => AiGenerationException::permanent("Cloudflare Workers AI rejected the request ({$status}): {$message}"),
        };
    }
}
