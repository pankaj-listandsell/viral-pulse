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

    /** @var callable|null */
    private $factory = null;

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

        // A fixed payload is the more specific instruction, so it replaces any
        // factory already set rather than losing silently to it.
        $this->factory = null;

        return $this;
    }

    /**
     * Build the structured payload from the call itself.
     *
     * willReturn() is enough when a test knows the exact answer it wants, but
     * a caller that asks for one shape per batch - the horoscope writer asks
     * for a different set of signs each time - needs the answer to depend on
     * the request. The factory receives the prompts, the schema and the name.
     */
    public function willReturnUsing(callable $factory): self
    {
        $this->factory = $factory;

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
     * Structured output, scripted the same way as generate().
     *
     * Without willReturn() this builds a payload from the schema itself, so a
     * test can exercise a caller end to end without having to hand-write a
     * fixture for whatever shape that caller happens to ask for.
     *
     * @param  array<string, mixed>  $schema
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     */
    public function generateJson(string $systemPrompt, string $userPrompt, array $schema, string $name = 'result'): array
    {
        $this->calls[] = [
            'request' => null,
            'system' => $systemPrompt,
            'user' => $userPrompt,
            'schema' => $schema,
            'name' => $name,
        ];

        if ($this->throw) {
            throw $this->throw;
        }

        $payload = match (true) {
            $this->factory !== null => ($this->factory)($systemPrompt, $userPrompt, $schema, $name),
            $this->payload !== null => $this->payload,
            default => $this->payloadFromSchema($schema),
        };

        return [
            'payload' => $payload,
            'model' => $this->model(),
            'prompt_tokens' => 400,
            'completion_tokens' => 900,
            'raw' => json_encode($payload),
        ];
    }

    /**
     * A value for every property the schema declares, typed correctly.
     *
     * @param  array<string, mixed>  $schema
     */
    private function payloadFromSchema(array $schema): mixed
    {
        if (! empty($schema['enum'])) {
            return $schema['enum'][0];
        }

        return match ($schema['type'] ?? 'string') {
            'object' => collect($schema['properties'] ?? [])
                ->map(fn (array $property): mixed => $this->payloadFromSchema($property))
                ->all(),
            'array' => [$this->payloadFromSchema($schema['items'] ?? ['type' => 'string'])],
            'integer', 'number' => 77,
            'boolean' => true,
            default => 'Fake structured value long enough to look like real copy.',
        };
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
