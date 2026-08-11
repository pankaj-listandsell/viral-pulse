<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;
use Illuminate\Support\Str;

/**
 * The provider the test suite binds.
 *
 * Every automated test runs against this, so the suite never spends money and
 * never depends on a third party being reachable. Failures can be scripted, so
 * the retry, refusal and malformed-output paths are all covered by tests.
 */
class FakeProvider implements AiProvider
{
    /** @var array<int, array<string, mixed>> */
    private array $calls = [];

    private ?\Throwable $throw = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    public function name(): string
    {
        return 'fake';
    }

    public function model(): string
    {
        return 'fake-model-1';
    }

    public function failWith(\Throwable $exception): self
    {
        $this->throw = $exception;

        return $this;
    }

    public function failRetryably(string $message = 'Temporary failure'): self
    {
        return $this->failWith(AiGenerationException::retryable($message));
    }

    public function refuse(string $message = 'The model declined this topic.'): self
    {
        return $this->failWith(AiGenerationException::permanent($message));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function willReturn(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    public function callCount(): int
    {
        return count($this->calls);
    }

    /**
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     */
    public function generate(GenerationRequest $request, string $systemPrompt, string $userPrompt): array
    {
        $this->calls[] = [
            'request' => $request,
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];

        if ($this->throw) {
            throw $this->throw;
        }

        $payload = $this->payload ?? $this->defaultPayload($request);

        return [
            'payload' => $payload,
            'model' => $this->model(),
            'prompt_tokens' => 850,
            'completion_tokens' => 1200,
            'raw' => json_encode($payload),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPayload(GenerationRequest $request): array
    {
        $body = collect(range(1, 6))
            ->map(fn (int $i) => '<p>'.str_repeat("Paragraph {$i} about {$request->topic} with enough words to pass the length floor. ", 12).'</p>')
            ->implode("\n");

        return [
            'title' => Str::title($request->topic),
            'slug' => Str::slug($request->topic),
            'excerpt' => "A short summary of {$request->topic} for the listing pages.",
            'content' => "<h2>What happened</h2>\n{$body}",
            'seo_title' => Str::limit(Str::title($request->topic), 55, ''),
            'seo_description' => "Everything worth knowing about {$request->topic}, explained simply.",
            'seo_keywords' => 'example, keywords, for, testing',
            'tags' => ['Example', 'Testing'],
            'image_prompt' => "A wide editorial photograph illustrating {$request->topic}",
        ];
    }
}
